<?php
declare(strict_types=1);
require_once __DIR__ . '/booking-changes.php';

function tmq_cancellation_timing(array $booking, ?DateTimeImmutable $now = null): string
{
    $zone=new DateTimeZone('Asia/Kuala_Lumpur');
    $today=($now??new DateTimeImmutable('now',$zone))->setTimezone($zone)->format('Y-m-d');
    $lessonDate=tmq_slot_local($booking['starts_at'])->format('Y-m-d');
    return $today<$lessonDate?'early':($today===$lessonDate?'same_day':'after_lesson_date');
}

function tmq_cancellation_allowed(array $booking, string $role, ?DateTimeImmutable $now = null): bool
{
    if (!in_array($role,['student','tutor','admin'],true)) { return false; }
    $statuses=$role==='tutor'?['accepted']:['requested','accepted'];
    return in_array($booking['status'],$statuses,true)
        && ($role==='admin' || tmq_cancellation_timing($booking,$now)!=='after_lesson_date');
}

function tmq_cancel_booking(array $actor, int $id, string $reason): void
{
    $before=tmq_booking_record($actor,$id);
    // TEXT is limited to 65,535 bytes, not characters. Strip markup before storage.
    $reason=tmq_profile_text(strip_tags($reason),65535,true);
    if (strlen($reason)>65535) { throw new DomainException('Cancellation reason is too long (maximum 65,535 UTF-8 bytes).'); }
    $db=tmq_db();
    try {
        $db->beginTransaction();
        [$booking]=tmq_change_lock($db,$actor,$before,(int)$before['slot_id'],false,true);
        $now=new DateTimeImmutable('now',new DateTimeZone('UTC'));
        if (!tmq_cancellation_allowed($booking,$actor['role'],$now)) {
            tmq_change_conflict('This booking can no longer be cancelled through this workflow. Reload its details.');
        }
        // The target of a pending proposal is unheld; only the current slot is relevant.
        // Parent locks serialize proposal creation/acceptance before this locking read.
        $q=$db->prepare('SELECT change_request_id FROM booking_change_requests WHERE pending_booking_id=? FOR UPDATE');
        $q->execute([$id]); $pending=$q->fetchColumn();
        // One captured server instant keeps eligibility, timing and timestamp consistent at midnight.
        $q=$db->prepare("UPDATE bookings SET status='cancelled',cancelled_by_user_id=?,cancellation_reason=?,cancelled_at=?,cancellation_timing=? WHERE booking_id=? AND status=?");
        $q->execute([$actor['user_id'],$reason,$now->format('Y-m-d H:i:s'),tmq_cancellation_timing($booking,$now),$id,$booking['status']]);
        if ($q->rowCount()!==1) { tmq_change_conflict('This booking has already been updated. Reload its details.'); }
        if ($pending) {
            $q=$db->prepare("UPDATE booking_change_requests SET status='withdrawn',responded_at=UTC_TIMESTAMP(),responded_by_user_id=NULL,response_note=? WHERE change_request_id=? AND status='pending'");
            // This is automatic withdrawal due to cancellation, not counterpart consent.
            // Actual cancellation actor/reason are retained on the booking.
            $q->execute(['Automatically withdrawn because the booking was cancelled.',$pending]);
            if ($q->rowCount()!==1) { tmq_change_conflict('This change request has already been updated.'); }
        }
        tmq_notify_booking($db,$id,'booking_cancelled',(int)$actor['user_id']);
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) { $db->rollBack(); }
        if ($error instanceof PDOException && in_array((int)($error->errorInfo[1]??0),[1062,1205,1213],true)) {
            tmq_change_conflict('This booking was just updated. Reload its details and try again.');
        }
        throw $error;
    }
}
