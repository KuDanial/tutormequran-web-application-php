<?php
declare(strict_types=1);
require_once __DIR__ . '/availability.php';
require_once __DIR__ . '/profiles.php';

function tmq_booking_id(string $value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>4294967295]]);
    if ($id === false) { throw new DomainException('Choose a valid tutor and lesson slot.'); }
    return $id;
}

function tmq_booking_modes(string $tutorMode, string $slotMode): array
{
    return array_values(array_filter(['online','physical'], fn($mode)=>in_array($tutorMode,[$mode,'both'],true) && in_array($slotMode,[$mode,'both'],true)));
}

function tmq_booking_amount(string $rate, array $slot): string
{
    if (!preg_match('/\A(\d{1,8})\.(\d{2})\z/', $rate, $parts)) { throw new DomainException('The lesson price is unavailable.'); }
    $seconds = tmq_slot_local($slot['ends_at'])->getTimestamp() - tmq_slot_local($slot['starts_at'])->getTimestamp();
    if ($seconds < 3600 || $seconds > 86400 || $seconds % 3600 !== 0) { throw new DomainException('The lesson interval is unavailable.'); }
    // Integer sen throughout; DECIMAL(10,2) maximum applies to the final amount.
    $sen = ((int)$parts[1] * 100 + (int)$parts[2]) * intdiv($seconds,3600);
    if ($sen <= 0 || $sen > 9999999999) { throw new DomainException('The lesson amount is outside the supported range.'); }
    return intdiv($sen,100).'.'.str_pad((string)($sen%100),2,'0',STR_PAD_LEFT);
}

function tmq_create_booking(array $actor, array $input): int
{
    if ($actor['role'] !== 'student') { throw new DomainException('Student access required.'); }
    $tutorId = tmq_booking_id(tmq_input($input,'tutor'));
    $slotId = tmq_booking_id(tmq_input($input,'slotId'));
    $mode = tmq_input($input,'classType');
    $notes = tmq_profile_text(tmq_input($input,'bookingNotes'),4000);
    $db = tmq_db();
    try {
        $db->beginTransaction();
        // Approved order: student -> tutor -> slot -> booking records.
        $q = $db->prepare('SELECT student_id FROM students WHERE user_id=? FOR UPDATE');
        $q->execute([$actor['user_id']]); $studentId = (int)$q->fetchColumn();
        if (!$studentId) { throw new DomainException('Student account unavailable.'); }
        $q = $db->prepare('SELECT tutor_id FROM tutors WHERE tutor_id=? FOR UPDATE');
        $q->execute([$tutorId]);
        if (!$q->fetchColumn()) { throw new DomainException('This tutor is unavailable.'); }
        $q = $db->prepare('SELECT * FROM availability_slots WHERE slot_id=? AND tutor_id=? FOR UPDATE');
        $q->execute([$slotId,$tutorId]); $slot = $q->fetch();
        // First consistent reads occur AFTER participant/slot locks, including after waits.
        $student = tmq_profile_user((int)$actor['user_id']);
        if ($student['role'] !== 'student' || $student['account_status'] !== 'active') { throw new DomainException('Student account unavailable.'); }
        $tutor = tmq_profile_tutor($tutorId);
        if (!tmq_public_tutor($tutor)) { throw new DomainException('This tutor is unavailable for booking.'); }
        if (!$slot || $slot['availability_status'] !== 'available' || tmq_slot_local($slot['starts_at']) <= new DateTimeImmutable('now',new DateTimeZone('UTC'))) {
            throw new DomainException('This slot is no longer available. Choose another lesson.');
        }
        if (!in_array($mode,tmq_booking_modes($tutor['teaching_mode'],$slot['mode']),true)) { throw new DomainException('Choose a class mode supported by both the tutor and this slot.'); }
        $q = $db->prepare('SELECT booking_id FROM bookings WHERE occupied_slot_id=? FOR UPDATE');
        $q->execute([$slotId]);
        if ($q->fetchColumn()) { throw new DomainException('This slot is no longer available. Choose another lesson.'); }
        $q = $db->prepare("SELECT booking_id FROM bookings WHERE student_id=? AND status IN ('requested','accepted') AND starts_at < ? AND ends_at > ? LIMIT 1 FOR UPDATE");
        $q->execute([$studentId,$slot['ends_at'],$slot['starts_at']]);
        if ($q->fetchColumn()) { throw new DomainException('This lesson overlaps one of your active bookings.'); }
        $amount = tmq_booking_amount($tutor['hourly_rate'],$slot);
        $q = $db->prepare("INSERT INTO bookings (student_id,tutor_id,slot_id,starts_at,ends_at,class_mode,notes,hourly_rate_snapshot,expected_amount,status) VALUES (?,?,?,?,?,?,?,?,?,'requested')");
        $q->execute([$studentId,$tutorId,$slotId,$slot['starts_at'],$slot['ends_at'],$mode,$notes?:null,$tutor['hourly_rate'],$amount]);
        $id = (int)$db->lastInsertId(); $db->commit(); return $id;
    } catch (Throwable $error) {
        if ($db->inTransaction()) { $db->rollBack(); }
        if ($error instanceof PDOException && (int)($error->errorInfo[1]??0)===1062) {
            throw new DomainException('This slot is no longer available. Choose another lesson.');
        }
        throw $error;
    }
}

function tmq_booking_slots(array $tutor): array
{
    $slots = tmq_profile_rows("SELECT s.* FROM availability_slots s WHERE s.tutor_id=? AND s.availability_status='available' AND s.starts_at>UTC_TIMESTAMP() AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.occupied_slot_id=s.slot_id) ORDER BY s.starts_at,s.slot_id",[$tutor['tutor_id']]);
    return array_values(array_filter($slots,static function($slot) use ($tutor) {
        if (tmq_booking_modes($tutor['teaching_mode'],$slot['mode'])===[]) { return false; }
        try { tmq_booking_amount($tutor['hourly_rate'],$slot); return true; }
        catch (DomainException $error) { return false; } // Do not offer an amount exceeding DECIMAL capacity.
    }));
}
