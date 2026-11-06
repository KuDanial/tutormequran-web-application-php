<?php
declare(strict_types=1);
require_once __DIR__ . '/bookings.php';
require_once __DIR__ . '/booking-arrangements.php';

function tmq_change_conflict(string $message): never
{
    http_response_code(409);
    throw new DomainException($message);
}

function tmq_change_participant(array $actor, int $id): array
{
    if (!in_array($actor['role'],['student','tutor'],true)) {
        http_response_code(403); throw new DomainException('Student or Tutor access required.');
    }
    return tmq_booking_record($actor,$id);
}

function tmq_change_history(array $actor, int $id): array
{
    tmq_change_participant($actor,$id);
    return tmq_profile_rows('SELECT c.*,u.role AS requester_role,CONCAT(u.first_name,\' \',u.last_name) AS requester_name,
        o.starts_at AS original_start,o.ends_at AS original_end,n.starts_at AS proposed_start,n.ends_at AS proposed_end
        FROM booking_change_requests c JOIN users u ON u.user_id=c.requested_by_user_id
        JOIN availability_slots o ON o.slot_id=c.original_slot_id JOIN availability_slots n ON n.slot_id=c.requested_slot_id
        WHERE c.booking_id=? ORDER BY c.change_request_id DESC',[$id]);
}

function tmq_change_eligible(array $booking): bool
{
    return $booking['status']==='accepted' && empty($booking['student_completed_at']) && empty($booking['tutor_completed_at'])
        && tmq_slot_local($booking['starts_at'])>new DateTimeImmutable('now',new DateTimeZone('UTC'));
}

// Locator reads happen before BEGIN. All write paths acquire the same parent/slot locks
// before any booking/change locks, then revalidate locator values and participation.
function tmq_change_lock(PDO $db, array $actor, array $before, int $targetId, bool $requireBothActive = true, bool $allowAdmin = false): array
{
    $admin=$allowAdmin && $actor['role']==='admin';
    if (!$admin && !in_array($actor['role'],['student','tutor'],true)) {
        http_response_code(403); throw new DomainException('Student or Tutor access required.');
    }
    $q=$db->prepare('SELECT * FROM students WHERE student_id=? FOR UPDATE');
    $q->execute([$before['student_id']]); $student=$q->fetch();
    $q=$db->prepare('SELECT * FROM tutors WHERE tutor_id=? FOR UPDATE');
    $q->execute([$before['tutor_id']]); $tutor=$q->fetch();
    if (!$student || !$tutor) { tmq_booking_not_found(); }
    $slotIds=array_unique([(int)$before['slot_id'],$targetId]); sort($slotIds,SORT_NUMERIC); $slots=[];
    $q=$db->prepare('SELECT * FROM availability_slots WHERE slot_id=? FOR UPDATE');
    foreach ($slotIds as $slotId) { $q->execute([$slotId]); $slots[$slotId]=$q->fetch(); }
    $q=$db->prepare('SELECT * FROM bookings WHERE booking_id=? FOR UPDATE');
    $q->execute([$before['booking_id']]); $booking=$q->fetch();
    if (!$booking || $booking['student_id']!==$before['student_id'] || $booking['tutor_id']!==$before['tutor_id']
        || (!$admin && (int)($actor['role']==='student'?$student['user_id']:$tutor['user_id'])!==(int)$actor['user_id'])) { tmq_booking_not_found(); }
    if ($booking['slot_id']!==$before['slot_id']) { tmq_change_conflict('This booking has already been updated. Reload its details.'); }
    // First consistent account reads occur after locks, including after lock waits.
    $su=tmq_profile_user((int)$student['user_id']); $tu=tmq_profile_user((int)$tutor['user_id']);
    $actingUser=$admin?tmq_profile_user((int)$actor['user_id']):($actor['role']==='student'?$su:$tu);
    if ($actingUser['role']!==$actor['role'] || $actingUser['account_status']!=='active' || ($requireBothActive && ($su['account_status']!=='active' || $tu['account_status']!=='active'))
        || $su['role']!=='student' || $tu['role']!=='tutor') {
        tmq_change_conflict('Both participant accounts must be active.');
    }
    return [$booking,$tutor,$slots,$student];
}

function tmq_change_validate_target(PDO $db, array $booking, array $tutor, array $slots, int $targetId, string $mode, array $arrangement): string
{
    if (!tmq_change_eligible($booking)) { tmq_change_conflict('Only accepted bookings before the lesson starts can be changed.'); }
    $original=$slots[(int)$booking['slot_id']]??false; $target=$slots[$targetId]??false;
    if (!$original || $original['starts_at']!==$booking['starts_at'] || $original['ends_at']!==$booking['ends_at']) {
        tmq_change_conflict('The booking and lesson slot no longer match.');
    }
    if (!$target || (int)$target['tutor_id']!==(int)$booking['tutor_id'] || $target['availability_status']!=='available'
        || tmq_slot_local($target['starts_at'])<=new DateTimeImmutable('now',new DateTimeZone('UTC'))) {
        tmq_change_conflict('The proposed slot is no longer available. Withdraw this request and choose another slot.');
    }
    $sameSlot=$targetId===(int)$booking['slot_id'];
    if ($sameSlot && $mode===$booking['class_mode'] && !tmq_arrangement_changed($booking,$arrangement)) {
        throw new DomainException('Choose an actual schedule, mode or arrangement change.');
    }
    // Approved design rule 17: mode-only consent requires Tutor capability; never rewrite slot.mode.
    if (!in_array($mode,tmq_booking_modes($tutor['teaching_mode']??'', $sameSlot?'both':$target['mode']),true)) {
        throw new DomainException('The requested mode is not supported by the Tutor or proposed slot.');
    }
    $q=$db->prepare('SELECT booking_id FROM bookings WHERE occupied_slot_id=? AND booking_id<>? FOR UPDATE');
    $q->execute([$targetId,$booking['booking_id']]);
    if ($q->fetchColumn()) { tmq_change_conflict('The proposed slot is no longer available. Withdraw this request and choose another slot.'); }
    if (tmq_booking_student_overlaps($db,(int)$booking['student_id'],$target,(int)$booking['booking_id'])) {
        tmq_change_conflict('The proposed lesson overlaps one of the Student\'s active bookings.');
    }
    $amount=tmq_booking_amount($booking['hourly_rate_snapshot'],$target);
    return $sameSlot ? $booking['expected_amount'] : $amount;
}

function tmq_create_booking_change(array $actor, int $id, array $input): int
{
    $before=tmq_change_participant($actor,$id);
    $arrangementOnly=tmq_input($input,'change_action')==='propose_arrangement';
    $targetId=$arrangementOnly?(int)$before['slot_id']:tmq_booking_id(tmq_input($input,'slotId'));
    $mode=$arrangementOnly?$before['class_mode']:tmq_input($input,'classType');
    $reason=tmq_profile_text(tmq_input($input,'reason'),4000,true);
    $db=tmq_db();
    try {
        $db->beginTransaction();
        [$booking,$tutor,$slots]=tmq_change_lock($db,$actor,$before,$targetId);
        if ($arrangementOnly) {
            // Coordination forms cannot silently modify schedule or mode through forged fields.
            $targetId=(int)$booking['slot_id']; $mode=$booking['class_mode'];
            if (tmq_input($input,'agreement_version')!==(string)$booking['agreement_version']) {
                tmq_change_conflict('The agreement has changed. Review the latest arrangement before proposing details.');
            }
        }
        $q=$db->prepare("SELECT change_request_id FROM booking_change_requests WHERE pending_booking_id=? FOR UPDATE");
        $q->execute([$id]);
        if ($q->fetchColumn()) { tmq_change_conflict('This booking already has a pending change request.'); }
        $includeArrangement=$arrangementOnly || tmq_input($input,'include_arrangement')==='1';
        $arrangement=tmq_arrangement_values($mode,$includeArrangement?[
            'venue_address'=>tmq_input($input,'venue_address'),'meeting_platform'=>tmq_input($input,'meeting_platform'),
            'meeting_url'=>tmq_input($input,'meeting_url')]:$booking,$includeArrangement);
        $amount=tmq_change_validate_target($db,$booking,$tutor,$slots,$targetId,$mode,$arrangement);
        $q=$db->prepare("INSERT INTO booking_change_requests
            (booking_id,requested_by_user_id,base_agreement_version,original_slot_id,requested_slot_id,original_mode,requested_mode,
             original_expected_amount,proposed_expected_amount,reason,original_venue_address,requested_venue_address,
             original_meeting_platform,requested_meeting_platform,original_meeting_url,requested_meeting_url,status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')");
        $q->execute([$id,$actor['user_id'],$booking['agreement_version'],$booking['slot_id'],$targetId,$booking['class_mode'],$mode,
            $booking['expected_amount'],$amount,$reason,$booking['venue_address'],$arrangement['venue_address'],
            $booking['meeting_platform'],$arrangement['meeting_platform'],$booking['meeting_url'],$arrangement['meeting_url']]);
        $changeId=(int)$db->lastInsertId(); $db->commit(); return $changeId;
    } catch (Throwable $error) {
        if ($db->inTransaction()) { $db->rollBack(); }
        if ($error instanceof PDOException && in_array((int)($error->errorInfo[1]??0),[1062,1205,1213],true)) {
            tmq_change_conflict('This booking already has a pending change request or was just updated. Reload its details.');
        }
        throw $error;
    }
}

function tmq_respond_booking_change(array $actor, int $id, int $changeId, string $action, string $note = ''): void
{
    $before=tmq_change_participant($actor,$id);
    if (!in_array($action,['accept','reject','withdraw'],true)) { throw new DomainException('Choose Accept, Reject or Withdraw.'); }
    $note=tmq_profile_text($note,4000);
    $found=tmq_profile_rows('SELECT requested_slot_id FROM booking_change_requests WHERE change_request_id=? AND booking_id=?',[$changeId,$id]);
    if (!$found) { tmq_booking_not_found(); }
    $targetId=(int)$found[0]['requested_slot_id']; $db=tmq_db();
    try {
        $db->beginTransaction();
        [$booking,$tutor,$slots,$student]=tmq_change_lock($db,$actor,$before,$targetId,$action==='accept');
        $q=$db->prepare('SELECT * FROM booking_change_requests WHERE change_request_id=? AND booking_id=? FOR UPDATE');
        $q->execute([$changeId,$id]); $change=$q->fetch();
        if (!$change) { tmq_booking_not_found(); }
        if ($change['status']!=='pending' || (int)$change['requested_slot_id']!==$targetId) {
            tmq_change_conflict('This change request has already been updated. Reload its details.');
        }
        $own=(int)$change['requested_by_user_id']===(int)$actor['user_id'];
        if ($change['is_admin_correction'] || !in_array((int)$change['requested_by_user_id'],[(int)$student['user_id'],(int)$tutor['user_id']],true)
            || ($action==='withdraw'?!$own:$own)) {
            http_response_code(403); throw new DomainException('Only the other participant can respond; only the requester can withdraw.');
        }
        if ($action==='accept') {
            if ($booking['agreement_version']!==$change['base_agreement_version'] || $booking['slot_id']!==$change['original_slot_id']
                || $booking['class_mode']!==$change['original_mode'] || $booking['expected_amount']!==$change['original_expected_amount']
                || $booking['venue_address']!==$change['original_venue_address'] || $booking['meeting_platform']!==$change['original_meeting_platform']
                || $booking['meeting_url']!==$change['original_meeting_url']) { tmq_change_conflict('The booking agreement has changed. Withdraw this request and create a new one.'); }
            $arrangement=tmq_arrangement_values($change['requested_mode'],[
                'venue_address'=>$change['requested_venue_address'],'meeting_platform'=>$change['requested_meeting_platform'],
                'meeting_url'=>$change['requested_meeting_url']]);
            // Legacy schedule/mode requests may leave initial details unset. Any material
            // edit of applicable arrangement fields must itself be a complete proposal.
            $previous=tmq_arrangement_values($change['requested_mode'],$booking);
            if ($arrangement!==$previous) { tmq_arrangement_values($change['requested_mode'],$arrangement,true); }
            $amount=tmq_change_validate_target($db,$booking,$tutor,$slots,$targetId,$change['requested_mode'],$arrangement);
            if ($amount!==$change['proposed_expected_amount']) { tmq_change_conflict('The proposed agreement no longer matches. Withdraw this request and create a new one.'); }
            $target=$slots[$targetId];
            // Arrangement-only acceptance leaves the slot/interval/amount columns untouched.
            $scheduleChanged=$targetId!==(int)$booking['slot_id'];
            $scheduleSql=$scheduleChanged?'slot_id=?,starts_at=?,ends_at=?,expected_amount=?,':'';
            $q=$db->prepare("UPDATE bookings SET {$scheduleSql}class_mode=?,
                venue_address=?,meeting_platform=?,meeting_url=?,student_arranged_at=NULL,tutor_arranged_at=NULL,agreement_version=agreement_version+1
                WHERE booking_id=? AND status='accepted' AND agreement_version=?");
            $params=$scheduleChanged?[$targetId,$target['starts_at'],$target['ends_at'],$amount]:[];
            $q->execute([...$params,$change['requested_mode'],$arrangement['venue_address'],$arrangement['meeting_platform'],$arrangement['meeting_url'],$id,$change['base_agreement_version']]);
            if ($q->rowCount()!==1) { tmq_change_conflict('The booking has already been updated.'); }
        }
        $status=['accept'=>'accepted','reject'=>'rejected','withdraw'=>'withdrawn'][$action];
        $q=$db->prepare("UPDATE booking_change_requests SET status=?,responded_by_user_id=?,response_note=?,responded_at=UTC_TIMESTAMP() WHERE change_request_id=? AND status='pending'");
        // Schema reserves responder identity for the counterpart. Withdrawal actor is the stored requester.
        $q->execute([$status,$action==='withdraw'?null:$actor['user_id'],$note?:null,$changeId]);
        if ($q->rowCount()!==1) { tmq_change_conflict('This change request has already been updated.'); }
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) { $db->rollBack(); }
        if ($error instanceof PDOException && in_array((int)($error->errorInfo[1]??0),[1062,1205,1213],true)) {
            tmq_change_conflict('The proposed slot is no longer available or this booking was just updated. Reload its details.');
        }
        throw $error;
    }
}

function tmq_change_slots(array $actor, array $booking): array
{
    tmq_change_participant($actor,(int)$booking['booking_id']);
    $tutor=tmq_profile_tutor((int)$booking['tutor_id']);
    // Selection prices always use the agreement snapshot, including after a profile rate change.
    $tutor['hourly_rate']=$booking['hourly_rate_snapshot'];
    $slots=tmq_booking_slots($tutor);
    $current=tmq_profile_rows('SELECT * FROM availability_slots WHERE slot_id=?',[$booking['slot_id']]);
    return array_merge($current,$slots);
}
