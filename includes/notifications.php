<?php
declare(strict_types=1);
require_once __DIR__.'/db.php';

// Internal event catalogue. Neither content nor destinations come from request data.
const TMQ_NOTIFICATION_TYPES = [
    'booking_requested'=>['New booking request','A Student requested a Quran lesson.'],
    'booking_accepted'=>['Booking accepted','Your Tutor accepted your booking.'],
    'booking_rejected'=>['Booking rejected','Your Tutor rejected your booking.'],
    'booking_change_requested'=>['Booking change requested','The other participant proposed a change to your lesson.'],
    'booking_change_accepted'=>['Booking change accepted','Your proposed lesson change was accepted.'],
    'booking_change_rejected'=>['Booking change rejected','Your proposed lesson change was rejected.'],
    'booking_change_withdrawn'=>['Booking change withdrawn','The other participant withdrew their proposed lesson change.'],
    'booking_cancelled'=>['Booking cancelled','Your booking was cancelled. View the booking for details.'],
    'booking_completion_needed'=>['Completion confirmation needed','The other participant confirmed completion. Your confirmation is still needed.'],
    'booking_completed'=>['Lesson completed','Both participants have confirmed lesson completion.'],
    'chat_message'=>['New message','You received a new message from the other lesson participant.'],
    'payment_submitted'=>['Payment evidence submitted','Your Student submitted payment evidence for your acknowledgement.'],
    'payment_tutor_received'=>['Payment evidence acknowledged','Your Tutor acknowledged the payment evidence. Final payment verification is still required.'],
    'payment_tutor_issue'=>['Payment evidence issue','Your Tutor reported an issue with your payment evidence.'],
    'payment_admin_valid'=>['Payment evidence accepted','Admin accepted this payment evidence. Final payment verification is still required.'],
    'payment_admin_rejected'=>['Payment evidence rejected','Admin rejected your payment evidence. View the payment for next steps.'],
    'payment_reupload_required'=>['Receipt correction required','Please submit corrected receipt evidence for the same transfer.'],
    'payment_verified'=>['Payment verified','Payment has been verified and the booking is closed.'],
    'certificate_approved'=>['Certificate approved','Your teaching certificate was approved.'],
    'certificate_rejected'=>['Certificate rejected','Your teaching certificate was rejected. View your qualification for next steps.'],
];

// Caller owns the domain transaction. Failures must propagate to its rollback.
function tmq_notification_insert(PDO $db, int $recipient, string $type, ?int $bookingId = null, ?int $certificateId = null): void
{
    if (!$db->inTransaction()) { throw new LogicException('Notifications require a domain transaction.'); }
    if ($recipient<1 || !isset(TMQ_NOTIFICATION_TYPES[$type])) { throw new InvalidArgumentException('Invalid notification event.'); }
    $certificate=str_starts_with($type,'certificate_');
    if (($certificate && ($certificateId===null || $certificateId<1 || $bookingId!==null))
        || (!$certificate && ($bookingId===null || $bookingId<1 || $certificateId!==null))) {
        throw new InvalidArgumentException('Invalid notification relation.');
    }
    [$title,$message]=TMQ_NOTIFICATION_TYPES[$type];
    $q=$db->prepare('INSERT INTO notifications (recipient_user_id,type,title,message,booking_id,certificate_id,created_at) VALUES (?,?,?,?,?,?,UTC_TIMESTAMP())');
    $q->execute([$recipient,$type,$title,$message,$bookingId,$certificateId]);
}

// Plain identity lookup only: no participant, slot or payment locks added.
function tmq_notify_booking(PDO $db, int $bookingId, string $type, int $actorId, string $audience = 'counterpart'): void
{
    if (!in_array($audience,['counterpart','student','tutor','participants'],true)) { throw new LogicException('Invalid notification audience.'); }
    $q=$db->prepare('SELECT s.user_id AS student,t.user_id AS tutor FROM bookings b JOIN students s ON s.student_id=b.student_id JOIN tutors t ON t.tutor_id=b.tutor_id WHERE b.booking_id=?');
    $q->execute([$bookingId]); $participants=$q->fetch();
    if (!$participants) { throw new LogicException('Notification booking unavailable.'); }
    foreach ($participants as $role=>$recipient) {
        if ((int)$recipient!==$actorId && (in_array($audience,['counterpart','participants'],true) || $audience===$role)) {
            tmq_notification_insert($db,(int)$recipient,$type,$bookingId);
        }
    }
}

function tmq_notification_unread_count(PDO $db, int $recipient): int
{
    $q=$db->prepare('SELECT COUNT(*) FROM notifications WHERE recipient_user_id=? AND read_at IS NULL');
    $q->execute([$recipient]); return (int)$q->fetchColumn();
}

function tmq_notification_mark_read(PDO $db, int $recipient, ?int $id): void
{
    $q=$db->prepare('UPDATE notifications SET read_at=UTC_TIMESTAMP() WHERE recipient_user_id=? AND read_at IS NULL'.($id===null?'':' AND notification_id=?'));
    $q->execute($id===null?[$recipient]:[$recipient,$id]);
}

function tmq_notification_page(PDO $db, array $actor, int $page): array
{
    $offset=(max(1,min(1000000,$page))-1)*50;
    // Join authorization context once for the entire bounded page; no N+1 lookups.
    $q=$db->prepare('SELECT n.*,s.user_id AS booking_student_user,t.user_id AS booking_tutor_user,ct.user_id AS certificate_user
        FROM notifications n LEFT JOIN bookings b ON b.booking_id=n.booking_id
        LEFT JOIN students s ON s.student_id=b.student_id LEFT JOIN tutors t ON t.tutor_id=b.tutor_id
        LEFT JOIN certificates c ON c.certificate_id=n.certificate_id LEFT JOIN tutors ct ON ct.tutor_id=c.tutor_id
        WHERE n.recipient_user_id=? ORDER BY n.created_at DESC,n.notification_id DESC LIMIT 51 OFFSET '.$offset);
    $q->execute([$actor['user_id']]); $rows=$q->fetchAll(); $more=count($rows)>50;
    if ($more) { array_pop($rows); }
    foreach ($rows as &$row) { $row['link']=tmq_notification_link($row,$actor); }
    unset($row);
    return ['rows'=>$rows,'more'=>$more];
}

// Ownership of the notice is never used as proof of ownership of its relation.
function tmq_notification_link(array $row, array $actor): ?string
{
    $role=$actor['role']; $type=$row['type'];
    if (!in_array($role,['student','tutor'],true) || !isset(TMQ_NOTIFICATION_TYPES[$type])
        || (int)$row['recipient_user_id']!==(int)$actor['user_id']) { return null; }
    if (str_starts_with($type,'certificate_')) {
        return $role==='tutor' && !$row['booking_id'] && (int)($row['certificate_user']??0)===(int)$actor['user_id']
            ? 'tutor-certificate-view.php?certificate_id='.(int)$row['certificate_id'] : null;
    }
    if ($row['certificate_id'] || !$row['booking_id'] || (int)($row['booking_'.$role.'_user']??0)!==(int)$actor['user_id']) { return null; }
    $page=$type==='chat_message'?'chat':(str_starts_with($type,'payment_')?'payment-view':'booking-view');
    return $role.'-'.$page.'.php?id='.(int)$row['booking_id'];
}
