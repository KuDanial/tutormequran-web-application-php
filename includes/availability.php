<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function tmq_slot_local(string $utc): DateTimeImmutable
{
    return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'));
}

function tmq_slot_input(array $input): array
{
    $date = tmq_input($input, 'availabilityDate');
    $time = tmq_input($input, 'startTime');
    $minutes = tmq_input($input, 'durationMinutes');
    $local = DateTimeImmutable::createFromFormat('!Y-m-d H:i', "$date $time", new DateTimeZone('Asia/Kuala_Lumpur'));
    if (!$local || $local->format('Y-m-d H:i') !== "$date $time" || !preg_match('/\A\d{2}:00\z/', $time)) {
        throw new DomainException('Choose a valid date and a start time on the hour.');
    }
    if (!ctype_digit($minutes) || (int)$minutes < 60 || (int)$minutes > 1440 || (int)$minutes % 60 !== 0) {
        throw new DomainException('Choose a duration of 1 to 24 whole hours.');
    }
    if ($local <= new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
        throw new DomainException('Availability must start in the future.');
    }
    $end = $local->modify('+'.(int)$minutes.' minutes');
    // End time is derived, never independently supplied. Reject a tampered preview.
    if (tmq_input($input, 'endTime') !== '' && tmq_input($input, 'endTime') !== $end->format('H:i')) {
        throw new DomainException('The end time must match the selected duration.');
    }
    $mode = tmq_input($input, 'classType');
    $status = tmq_input($input, 'availabilityStatus');
    if (!in_array($mode, ['online','physical','both'], true) || !in_array($status, ['available','unavailable'], true)) {
        throw new DomainException('Choose a valid class mode and availability status.');
    }
    $utc = new DateTimeZone('UTC');
    return [$local->setTimezone($utc)->format('Y-m-d H:i:s'), $end->setTimezone($utc)->format('Y-m-d H:i:s'), $mode, $status];
}

// Call only after locking this tutor row inside the write transaction.
function tmq_slot_overlaps(PDO $db, int $tutorId, string $start, string $end, int $exclude = 0): bool
{
    $q = $db->prepare('SELECT slot_id FROM availability_slots WHERE tutor_id=? AND starts_at < ? AND ends_at > ? AND slot_id<>? LIMIT 1 FOR UPDATE');
    $q->execute([$tutorId, $end, $start, $exclude]);
    return (bool)$q->fetchColumn();
}

function tmq_save_slot(array $actor, ?int $slotId, array $input): int
{
    if ($actor['role'] !== 'tutor') { throw new DomainException('Tutor access required.'); }
    $db = tmq_db();
    try {
        $db->beginTransaction();
        // A stable parent lock also serializes creation when this tutor has no slots.
        $q = $db->prepare('SELECT tutor_id FROM tutors WHERE user_id=? FOR UPDATE');
        $q->execute([$actor['user_id']]);
        $tutorId = (int)$q->fetchColumn();
        if (!$tutorId) { throw new DomainException('Tutor account unavailable.'); }
        if ($slotId !== null) {
            $q = $db->prepare('SELECT slot_id FROM availability_slots WHERE slot_id=? AND tutor_id=? FOR UPDATE');
            $q->execute([$slotId, $tutorId]);
            if (!$q->fetchColumn()) { http_response_code(404); throw new DomainException('Availability slot not found.'); }
            // Preserve slots referenced by future modules; no booking operation is implemented here.
            $q = $db->prepare('SELECT booking_id FROM bookings WHERE slot_id=? LIMIT 1');
            $q->execute([$slotId]);
            $referenced = (bool)$q->fetchColumn();
            $q = $db->prepare('SELECT change_request_id FROM booking_change_requests WHERE original_slot_id=? OR requested_slot_id=? LIMIT 1');
            $q->execute([$slotId, $slotId]);
            if ($referenced || $q->fetchColumn()) { throw new DomainException('This slot is referenced by booking history and cannot be changed.'); }
        }
        [$start, $end, $mode, $status] = tmq_slot_input($input);
        if (tmq_slot_overlaps($db, $tutorId, $start, $end, $slotId ?? 0)) {
            throw new DomainException('This interval overlaps another availability slot, including unavailable slots.');
        }
        if ($slotId === null) {
            $q = $db->prepare('INSERT INTO availability_slots (tutor_id,starts_at,ends_at,mode,availability_status) VALUES (?,?,?,?,?)');
            $q->execute([$tutorId, $start, $end, $mode, $status]);
            $slotId = (int)$db->lastInsertId();
        } else {
            $q = $db->prepare('UPDATE availability_slots SET starts_at=?,ends_at=?,mode=?,availability_status=? WHERE slot_id=? AND tutor_id=?');
            $q->execute([$start, $end, $mode, $status, $slotId, $tutorId]);
        }
        $db->commit();
        return $slotId;
    } catch (Throwable $error) {
        if ($db->inTransaction()) { $db->rollBack(); }
        throw $error;
    }
}
