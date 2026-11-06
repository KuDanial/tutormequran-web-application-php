<?php
declare(strict_types=1);
require_once __DIR__ . '/bookings.php';

// Canonical effective values; unrelated fields never become part of the effective mode.
function tmq_arrangement_values(string $mode, array $values, bool $required = false): array
{
    $result=['venue_address'=>null,'meeting_platform'=>null,'meeting_url'=>null];
    $fields=match($mode) { 'physical'=>['venue_address'=>500], 'online'=>['meeting_platform'=>80,'meeting_url'=>2048], default=>throw new DomainException('Choose a valid class mode.') };
    foreach ($fields as $field=>$limit) {
        $value=tmq_profile_text((string)($values[$field]??''),$limit);
        if ($required && $value==='') { throw new DomainException($mode==='physical'?'An agreed physical venue is required.':'An agreed meeting platform and HTTP or HTTPS meeting URL are required.'); }
        if ($field==='meeting_url' && $value!=='' && (!filter_var($value,FILTER_VALIDATE_URL)
            || !in_array(strtolower(parse_url($value,PHP_URL_SCHEME)??''),['http','https'],true))) {
            throw new DomainException('Enter a valid HTTP or HTTPS meeting URL.');
        }
        $result[$field]=$value===''?null:$value;
    }
    return $result;
}

function tmq_arrangement_changed(array $booking, array $values): bool
{
    foreach ($values as $field=>$value) { if ($booking[$field]!==$value) { return true; } }
    return false;
}

function tmq_arrangement_ready(array $booking): bool
{
    try { tmq_arrangement_values($booking['class_mode'],$booking,true); return true; }
    catch (DomainException $error) { return false; }
}

function tmq_arrangement_label(array $booking): string
{
    if ($booking['student_arranged_at'] && $booking['tutor_arranged_at']) { return 'Arrangement confirmed'; }
    if (!tmq_arrangement_ready($booking)) { return 'Awaiting agreed arrangement details'; }
    if ($booking['student_arranged_at']) { return 'Awaiting Tutor confirmation'; }
    if ($booking['tutor_arranged_at']) { return 'Awaiting Student confirmation'; }
    return 'Awaiting Student and Tutor confirmations';
}

// Loaded by booking-changes.php. Shares the same lock order
// with proposal acceptance, so a racing change either resets this confirmation or wins first.
function tmq_confirm_arrangement(array $actor, int $id, int $version): bool
{
    $before=tmq_change_participant($actor,$id); $db=tmq_db();
    try {
        $db->beginTransaction();
        [$booking]=tmq_change_lock($db,$actor,$before,(int)$before['slot_id'],false);
        if (!tmq_change_eligible($booking)) { tmq_change_conflict('Only accepted bookings before the lesson starts can be confirmed.'); }
        if ((int)$booking['agreement_version']!==$version) { tmq_change_conflict('The agreement has changed. Review the latest arrangement before confirming.'); }
        tmq_arrangement_values($booking['class_mode'],$booking,true);
        $column=$actor['role']==='student'?'student_arranged_at':'tutor_arranged_at';
        // Repeated confirmations for the same version are idempotent; never rewrite timestamps.
        if ($booking[$column]!==null) { $db->commit(); return false; }
        $q=$db->prepare("UPDATE bookings SET $column=UTC_TIMESTAMP() WHERE booking_id=? AND status='accepted' AND agreement_version=? AND $column IS NULL");
        $q->execute([$id,$version]);
        if ($q->rowCount()!==1) { tmq_change_conflict('The agreement has changed. Review the latest arrangement before confirming.'); }
        $db->commit(); return true;
    } catch (Throwable $error) {
        if ($db->inTransaction()) { $db->rollBack(); }
        if ($error instanceof PDOException && in_array((int)($error->errorInfo[1]??0),[1205,1213],true)) {
            tmq_change_conflict('The arrangement was just updated. Review its latest details and try again.');
        }
        throw $error;
    }
}
