<?php
declare(strict_types=1);
require_once __DIR__ . '/booking-creation.php';

const TMQ_BOOKING_STATUSES = ['requested','accepted','rejected','completed','cancelled','closed'];

function tmq_booking_not_found(): never
{
    http_response_code(404);
    throw new DomainException('Booking not found.');
}

// All reads use the authenticated user-to-subtype relationship. No browser owner IDs.
function tmq_booking_records(array $actor, ?int $id = null, string $status = ''): array
{
    $where = []; $params = [];
    if ($actor['role'] === 'student') { $where[]='s.user_id=?'; $params[]=$actor['user_id']; }
    elseif ($actor['role'] === 'tutor') { $where[]='t.user_id=?'; $params[]=$actor['user_id']; }
    elseif ($actor['role'] !== 'admin') { tmq_booking_not_found(); }
    if ($id !== null) { $where[]='b.booking_id=?'; $params[]=$id; }
    if ($status !== '') {
        if (!in_array($status,TMQ_BOOKING_STATUSES,true)) { throw new DomainException('Choose a valid booking status.'); }
        $where[]='b.status=?'; $params[]=$status;
    }
    return tmq_profile_rows('SELECT b.booking_id,b.student_id,b.tutor_id,b.slot_id,b.starts_at,b.ends_at,b.class_mode,b.lesson_focus_summary,b.notes,b.hourly_rate_snapshot,b.expected_amount,b.status,b.created_at,b.decision_at,b.rejection_reason,
        b.venue_address,b.meeting_platform,b.meeting_url,b.student_arranged_at,b.tutor_arranged_at,b.agreement_version,
        CONCAT(su.first_name,\' \',su.last_name) AS student_name,
        COALESCE(NULLIF(t.display_name,\'\'),CONCAT(tu.first_name,\' \',tu.last_name)) AS tutor_name
        FROM bookings b JOIN students s ON s.student_id=b.student_id JOIN users su ON su.user_id=s.user_id
        JOIN tutors t ON t.tutor_id=b.tutor_id JOIN users tu ON tu.user_id=t.user_id'
        .($where?' WHERE '.implode(' AND ',$where):'')." ORDER BY (b.status='requested') DESC,b.starts_at DESC,b.booking_id DESC",$params);
}

function tmq_booking_record(array $actor, int $id): array
{
    $rows = tmq_booking_records($actor,$id);
    if (!$rows) { tmq_booking_not_found(); }
    return $rows[0];
}

function tmq_decide_booking(array $actor, int $id, string $action, string $reason = ''): void
{
    if ($actor['role'] !== 'tutor') { http_response_code(403); throw new DomainException('Tutor access required.'); }
    if (!in_array($action,['accept','reject'],true)) { throw new DomainException('Choose Accept or Reject.'); }
    $reason = $action==='reject' ? tmq_profile_text($reason,600,true) : null;
    // Locator read before BEGIN; revalidate it after locking in approved order.
    $before = tmq_booking_record($actor,$id);
    $db = tmq_db();
    try {
        $db->beginTransaction();
        $q=$db->prepare('SELECT student_id FROM students WHERE student_id=? FOR UPDATE');
        $q->execute([$before['student_id']]);
        if (!$q->fetchColumn()) { tmq_booking_not_found(); }
        $q=$db->prepare('SELECT tutor_id FROM tutors WHERE tutor_id=? AND user_id=? FOR UPDATE');
        $q->execute([$before['tutor_id'],$actor['user_id']]);
        if (!$q->fetchColumn()) { tmq_booking_not_found(); }
        $q=$db->prepare('SELECT * FROM availability_slots WHERE slot_id=? AND tutor_id=? FOR UPDATE');
        $q->execute([$before['slot_id'],$before['tutor_id']]); $slot=$q->fetch();
        $q=$db->prepare('SELECT * FROM bookings WHERE booking_id=? AND tutor_id=? FOR UPDATE');
        $q->execute([$id,$before['tutor_id']]); $booking=$q->fetch();
        if (!$booking) { tmq_booking_not_found(); }
        $user=tmq_profile_user((int)$actor['user_id']);
        if ($user['role']!=='tutor' || $user['account_status']!=='active') { http_response_code(403); throw new DomainException('Tutor account unavailable.'); }
        if ($booking['status']!=='requested' || $booking['student_id']!==$before['student_id'] || $booking['slot_id']!==$before['slot_id']) {
            http_response_code(409); throw new DomainException('This booking has already been updated. Reload its details.');
        }
        if (!$slot || $slot['starts_at']!==$booking['starts_at'] || $slot['ends_at']!==$booking['ends_at']) {
            http_response_code(409); throw new DomainException('The booking and lesson slot no longer match.');
        }
        if ($action==='accept' && ($slot['availability_status']!=='available'
            || !in_array($slot['mode'],[$booking['class_mode'],'both'],true)
            || tmq_slot_local($booking['starts_at'])<=new DateTimeImmutable('now',new DateTimeZone('UTC')))) {
            http_response_code(409); throw new DomainException('Only a valid future booking can be accepted.');
        }
        $q=$db->prepare("UPDATE bookings SET status=?,decision_at=UTC_TIMESTAMP(),rejection_reason=? WHERE booking_id=? AND tutor_id=? AND status='requested'");
        $q->execute([$action==='accept'?'accepted':'rejected',$reason,$id,$before['tutor_id']]);
        if ($q->rowCount()!==1) { http_response_code(409); throw new DomainException('This booking has already been updated.'); }
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) { $db->rollBack(); }
        throw $error;
    }
}

function tmq_booking_badge(string $status): string
{
    $class = match($status) { 'accepted','completed','closed'=>'good','requested'=>'pending',default=>'muted' };
    return '<span class="portal-badge '.$class.'">'.tmq_escape(ucfirst($status)).'</span>';
}

function tmq_booking_time(string $utc): string { return tmq_slot_local($utc)->format('d M Y, g:i A').' MYT'; }
