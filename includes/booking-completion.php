<?php
declare(strict_types=1);
require_once __DIR__ . '/booking-changes.php';

function tmq_completion_eligible(array $booking): bool
{
    return $booking['status']==='accepted' && $booking['student_arranged_at']!==null && $booking['tutor_arranged_at']!==null
        && tmq_slot_local($booking['ends_at'])<=new DateTimeImmutable('now',new DateTimeZone('UTC'));
}

function tmq_completion_label(array $booking): string
{
    if ($booking['status']==='cancelled') { return 'Lesson cancelled'; }
    if ($booking['student_completed_at'] && $booking['tutor_completed_at']) { return 'Lesson completed'; }
    if ($booking['student_completed_at']) { return 'Awaiting Tutor completion confirmation'; }
    if ($booking['tutor_completed_at']) { return 'Awaiting Student completion confirmation'; }
    return 'Lesson completion not yet confirmed';
}

function tmq_confirm_completion(array $actor, int $id, int $version): bool
{
    $before=tmq_change_participant($actor,$id); $db=tmq_db();
    try {
        $db->beginTransaction();
        [$booking]=tmq_change_lock($db,$actor,$before,(int)$before['slot_id'],false);
        if ((int)$booking['agreement_version']!==$version) { tmq_change_conflict('The agreement has changed. Review the latest lesson before confirming.'); }
        $column=$actor['role']==='student'?'student_completed_at':'tutor_completed_at';
        $counterpart=$actor['role']==='student'?'tutor_completed_at':'student_completed_at';
        if ($booking['status']==='completed' && $booking[$column]!==null && $booking[$counterpart]!==null) { $db->commit(); return false; }
        if (!tmq_completion_eligible($booking)) { tmq_change_conflict('Completion requires an accepted lesson, both arrangement confirmations and the scheduled end time reached.'); }
        if ($booking[$column]!==null) { $db->commit(); return false; }
        $status=$booking[$counterpart]!==null?'completed':'accepted';
        $q=$db->prepare("UPDATE bookings SET $column=UTC_TIMESTAMP(),status=? WHERE booking_id=? AND status='accepted' AND agreement_version=? AND $column IS NULL");
        $q->execute([$status,$id,$version]);
        if ($q->rowCount()!==1) { tmq_change_conflict('This booking has already been updated. Reload its details.'); }
        tmq_notify_booking($db,$id,$status==='completed'?'booking_completed':'booking_completion_needed',(int)$actor['user_id']);
        $db->commit(); return true;
    } catch (Throwable $error) {
        if ($db->inTransaction()) { $db->rollBack(); }
        if ($error instanceof PDOException && in_array((int)($error->errorInfo[1]??0),[1205,1213],true)) {
            tmq_change_conflict('This booking was just updated. Reload its details and try again.');
        }
        throw $error;
    }
}
