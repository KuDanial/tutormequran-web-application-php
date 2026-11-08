<?php
declare(strict_types=1);
require_once __DIR__.'/bookings.php';

// Both confirmations are authoritative; payment is deliberately absent.
const TMQ_FEEDBACK_ELIGIBLE_SQL = "b.student_completed_at IS NOT NULL AND b.tutor_completed_at IS NOT NULL AND b.status IN ('accepted','completed','closed')";

function tmq_feedback_eligible(array $booking): bool
{
    return $booking['student_completed_at'] !== null && $booking['tutor_completed_at'] !== null
        && in_array($booking['status'], ['accepted','completed','closed'], true);
}

function tmq_submit_feedback(array $actor, int $bookingId, array $input): void
{
    if ($actor['role'] !== 'student') { http_response_code(403); throw new DomainException('Student access required.'); }
    if (tmq_input($input,'feedback_action') !== 'submit') { throw new DomainException('Choose Submit feedback. Submitted feedback cannot be changed.'); }
    $rating=tmq_input($input,'rating');
    if (!preg_match('/\A[1-5]\z/D',$rating)) { throw new DomainException('Choose a whole-number rating from 1 to 5.'); }
    if (isset($input['comment']) && !is_string($input['comment'])) { throw new DomainException('Enter a plain-text comment.'); }
    $comment=tmq_input($input,'comment');
    if (!mb_check_encoding($comment,'UTF-8')) { throw new DomainException('Enter valid UTF-8 text.'); }
    $comment=trim($comment);
    // MariaDB TEXT capacity is bytes, not Unicode characters.
    if (strlen($comment)>65535) { throw new DomainException('Comments must be at most 65,535 UTF-8 bytes.'); }
    $db=tmq_db();
    try {
        $db->beginTransaction();
        // Only the booking is explicitly locked. Payment verification uses the same
        // booking-first order; no payment, participant or availability locks needed.
        $q=$db->prepare('SELECT * FROM bookings WHERE booking_id=? FOR UPDATE');
        $q->execute([$bookingId]); $booking=$q->fetch();
        $q=$db->prepare("SELECT s.student_id FROM students s JOIN users u ON u.user_id=s.user_id WHERE u.user_id=? AND u.role='student' AND u.account_status='active'");
        $q->execute([$actor['user_id']]); $studentId=$q->fetchColumn();
        if (!$studentId) { http_response_code(403); throw new DomainException('Student account unavailable.'); }
        if (!$booking || (int)$booking['student_id']!==(int)$studentId) { tmq_booking_not_found(); }
        if (!tmq_feedback_eligible($booking)) { http_response_code(409); throw new DomainException('Feedback requires both lesson-completion confirmations on an eligible booking.'); }
        // UNIQUE(booking_id), not a pre-insert existence check, is the final guard.
        $q=$db->prepare('INSERT INTO feedback (booking_id,rating,comment,created_at) VALUES (?,?,?,UTC_TIMESTAMP())');
        $q->execute([$bookingId,(int)$rating,$comment===''?null:$comment]);
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) { $db->rollBack(); }
        if ($error instanceof PDOException && (int)($error->errorInfo[1]??0)===1062) {
            http_response_code(409); throw new DomainException('Feedback has already been submitted for this booking and cannot be changed.');
        }
        if ($error instanceof PDOException && in_array((int)($error->errorInfo[1]??0),[1205,1213],true)) {
            http_response_code(409); throw new DomainException('This booking is being updated. Reload to check its feedback before trying again.');
        }
        throw $error;
    }
}

// Shared scoped history/eligible selector. No client-supplied participant IDs.
function tmq_feedback_history(array $actor, ?int $bookingId=null, bool $available=false, int $page=1): array
{
    if (!in_array($actor['role'],['student','tutor'],true)) { http_response_code(403); throw new DomainException('Participant access required.'); }
    $where=($actor['role']==='student'?'s':'t').'.user_id=?'; $params=[$actor['user_id']];
    if ($bookingId!==null) { $where.=' AND b.booking_id=?'; $params[]=$bookingId; }
    $where.=$available?' AND f.feedback_id IS NULL AND '.TMQ_FEEDBACK_ELIGIBLE_SQL:' AND f.feedback_id IS NOT NULL';
    $offset=(max(1,min($page,1000000))-1)*50;
    return tmq_profile_rows("SELECT b.booking_id,b.starts_at,f.feedback_id,f.rating,f.comment,f.created_at,
        CONCAT(su.first_name,' ',su.last_name) AS student_name,
        COALESCE(NULLIF(t.display_name,''),CONCAT(tu.first_name,' ',tu.last_name)) AS tutor_name
        FROM bookings b JOIN students s ON s.student_id=b.student_id JOIN users su ON su.user_id=s.user_id
        JOIN tutors t ON t.tutor_id=b.tutor_id JOIN users tu ON tu.user_id=t.user_id
        LEFT JOIN feedback f ON f.booking_id=b.booking_id WHERE $where
        ORDER BY ".($available?'b.starts_at DESC,b.booking_id DESC':'f.created_at DESC,f.feedback_id DESC')." LIMIT 51 OFFSET $offset",$params);
}

function tmq_feedback_public(int $tutorId): array
{
    $where='b.tutor_id=? AND '.TMQ_FEEDBACK_ELIGIBLE_SQL;
    return [
        'summary'=>tmq_profile_rows("SELECT COUNT(*) AS total,AVG(f.rating) AS average FROM feedback f JOIN bookings b ON b.booking_id=f.booking_id WHERE $where",[$tutorId])[0],
        // Public reviews omit participant names, booking IDs and private lesson data.
        'rows'=>tmq_profile_rows("SELECT f.rating,f.comment,f.created_at FROM feedback f JOIN bookings b ON b.booking_id=f.booking_id WHERE $where ORDER BY f.created_at DESC,f.feedback_id DESC LIMIT 20",[$tutorId]),
    ];
}
