<?php
declare(strict_types=1);
require_once __DIR__.'/uploads.php';

function tmq_support_text(array $input, string $key, bool $required = false): string
{
    $value=$input[$key] ?? '';
    if (!is_string($value) || !preg_match('//u',$value)) { throw new DomainException('Enter valid text.'); }
    $value=trim($value);
    if (($required && $value==='') || strlen($value)>65535) { throw new DomainException('Request details are required and must fit within 65,535 UTF-8 bytes.'); }
    return $value;
}

function tmq_support_create(PDO $db, array $user, array $input): int
{
    if (!in_array($user['role'],['student','tutor'],true)) { throw new DomainException('Student or Tutor account required.'); }
    $category=tmq_support_text($input,'category',true);
    if (!in_array($category,['billing','attendance','other'],true)) { throw new DomainException('Choose a valid topic.'); }
    $description=tmq_support_text($input,'description',true);
    $booking=tmq_support_text($input,'booking');
    if ($booking!=='' && (!preg_match('/\A[1-9][0-9]{0,9}\z/',$booking) || (int)$booking>4294967295)) { throw new DomainException('Enter a valid numeric booking ID.'); }
    $upload=null;
    try {
        $upload=tmq_receive_upload('photo','support');
        $db->beginTransaction();
        // Atomic INSERT ... SELECT rechecks active account and optional booking ownership.
        // Submitted identity, status, handler and timestamps never enter the query.
        $query=$db->prepare("INSERT INTO support_requests (requester_user_id,category,booking_id,description,photo_path,status,created_at)
            SELECT u.user_id,?,?,?,?, 'open',UTC_TIMESTAMP() FROM users u
            WHERE u.user_id=? AND u.role=? AND u.account_status='active'
            AND ((u.role='student' AND EXISTS (SELECT 1 FROM students s WHERE s.user_id=u.user_id))
              OR (u.role='tutor' AND EXISTS (SELECT 1 FROM tutors t WHERE t.user_id=u.user_id)))
            AND (? IS NULL OR EXISTS (SELECT 1 FROM bookings b
                JOIN students s ON s.student_id=b.student_id JOIN tutors t ON t.tutor_id=b.tutor_id
                WHERE b.booking_id=? AND ((u.role='student' AND s.user_id=u.user_id) OR (u.role='tutor' AND t.user_id=u.user_id))))");
        $bid=$booking===''?null:(int)$booking;
        $query->execute([$category,$bid,$description,$upload['file_path']??null,$user['user_id'],$user['role'],$bid,$bid]);
        if ($query->rowCount()!==1) { http_response_code(404); throw new DomainException('Account or booking unavailable.'); }
        $id=(int)$db->lastInsertId();
        $db->commit();
        return $id;
    } catch (Throwable $e) {
        if ($db->inTransaction()) { $db->rollBack(); }
        tmq_discard_upload($upload);
        throw $e;
    }
}

function tmq_support_history(PDO $db, int $userId, int $page, ?int $id): array
{
    $offset=($page-1)*50;
    $query=$db->prepare('SELECT request_id,category,booking_id,description,status,resolution_note,created_at,resolved_at,
        (photo_path IS NOT NULL) AS has_photo FROM support_requests WHERE requester_user_id=?'.
        ($id===null?' ORDER BY created_at DESC,request_id DESC LIMIT 51 OFFSET '.$offset:' AND request_id=?'));
    $query->execute($id===null?[$userId]:[$userId,$id]);
    $rows=$query->fetchAll();
    return ['rows'=>array_slice($rows,0,50),'more'=>count($rows)>50];
}
