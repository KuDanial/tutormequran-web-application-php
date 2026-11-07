<?php
declare(strict_types=1);
require_once __DIR__ . '/bookings.php';

function tmq_payment_conflict(string $message): never
{
    http_response_code(409); throw new DomainException($message);
}

function tmq_payment_booking(array $actor, int $id): array
{
    if ($actor['role']!=='student') { http_response_code(403); throw new DomainException('Student access required.'); }
    return tmq_booking_record($actor,$id);
}

function tmq_payment_eligible(array $booking): bool
{
    return $booking['status']==='completed' && $booking['student_completed_at']!==null && $booking['tutor_completed_at']!==null;
}

function tmq_payment_record(array $actor, int $bookingId): ?array
{
    tmq_payment_booking($actor,$bookingId);
    // Aggregate/private reviewer notes are deliberately not selected for Student display.
    return tmq_profile_rows('SELECT payment_id,booking_id,status,evidence_revision,recipient_name_snapshot,qr_path_snapshot,created_at FROM payments WHERE booking_id=?',[$bookingId])[0]??null;
}

function tmq_payment_history(array $actor, int $bookingId): array
{
    tmq_payment_booking($actor,$bookingId);
    return tmq_profile_rows('SELECT t.transfer_id,t.bank_reference,s.submission_id,s.attempt_number,s.amount_paid,s.transfer_date,s.payment_note,
        s.original_filename,s.mime_type,s.file_size_bytes,s.uploaded_at,s.tutor_status,s.admin_status,
        (s.attempt_number=(SELECT MAX(c.attempt_number) FROM payment_submissions c WHERE c.transfer_id=t.transfer_id)) AS is_current,
        EXISTS(SELECT 1 FROM payment_submissions d WHERE d.file_sha256=s.file_sha256 AND d.submission_id<>s.submission_id) AS duplicate_evidence,
        (t.bank_reference IS NOT NULL AND EXISTS(SELECT 1 FROM payment_transfers d WHERE d.bank_reference=t.bank_reference AND d.transfer_id<>t.transfer_id)) AS duplicate_reference
        FROM payments p JOIN payment_transfers t ON t.payment_id=p.payment_id JOIN payment_submissions s ON s.transfer_id=t.transfer_id
        WHERE p.booking_id=? ORDER BY t.transfer_id DESC,s.attempt_number DESC',[$bookingId]);
}

function tmq_payment_amount(string $input): string
{
    $input=trim($input);
    if (!preg_match('/\A(0|[1-9][0-9]{0,7})(?:\.([0-9]{1,2}))?\z/',$input,$parts)) {
        throw new DomainException('Enter a positive RM amount with at most two decimal places (maximum 99999999.99).');
    }
    $fraction=str_pad($parts[2]??'',2,'0');
    if ($parts[1]==='0' && $fraction==='00') { throw new DomainException('Transfer amount must be greater than zero.'); }
    return $parts[1].'.'.$fraction;
}

// Final rule: inspect the current submission, never authorize from aggregate status alone.
function tmq_payment_correction_allowed(array $booking, array $payment, array $submission, int $currentId): bool
{
    if (!tmq_payment_eligible($booking) || $payment['status']==='verified') { return false; }
    if ((int)$submission['submission_id']!==$currentId) { return false; }
    if (!in_array($payment['status'],['pending','underpaid','rejected','reupload_required'],true)
        || !in_array($submission['tutor_status'],['awaiting','received','issue'],true)
        || !in_array($submission['admin_status'],['pending','valid','rejected','reupload_required'],true)) { return false; }
    if ($submission['admin_status']==='valid') { return false; }
    if (in_array($submission['admin_status'],['rejected','reupload_required'],true)) { return true; }
    if ($submission['tutor_status']==='received') { return false; }
    return $submission['admin_status']==='pending' && in_array($submission['tutor_status'],['issue','awaiting'],true);
}

// Caller begins a transaction. Payment protocol is booking -> payment -> transfer/submission.
// Do not acquire participant/slot locks after these locks (approved design rule 11).
function tmq_payment_lock(PDO $db, array $actor, int $id, bool $create = false): array
{
    if ($actor['role']!=='student') { http_response_code(403); throw new DomainException('Student access required.'); }
    $q=$db->prepare('SELECT * FROM bookings WHERE booking_id=? FOR UPDATE');$q->execute([$id]);$booking=$q->fetch();
    if (!$booking) { tmq_booking_not_found(); }
    $owner=tmq_profile_rows('SELECT u.user_id,u.role,u.account_status FROM students s JOIN users u ON u.user_id=s.user_id WHERE s.student_id=?',[$booking['student_id']])[0]??null;
    if (!$owner || (int)$owner['user_id']!==(int)$actor['user_id']) { tmq_booking_not_found(); }
    if ($owner['role']!=='student' || $owner['account_status']!=='active') { http_response_code(403); throw new DomainException('Student account unavailable.'); }
    if (!tmq_payment_eligible($booking)) { tmq_payment_conflict('Only your completed lesson with both completion confirmations can receive payment evidence.'); }
    $q=$db->prepare('SELECT * FROM payments WHERE booking_id=? FOR UPDATE');$q->execute([$id]);$payment=$q->fetch();
    if (!$payment && $create) {
        $tutor=tmq_profile_rows("SELECT COALESCE(NULLIF(t.display_name,''),CONCAT(u.first_name,' ',u.last_name)) AS recipient,t.duitnow_qr_path
            FROM tutors t JOIN users u ON u.user_id=t.user_id WHERE t.tutor_id=?",[$booking['tutor_id']])[0];
        $q=$db->prepare('INSERT INTO payments (booking_id,recipient_name_snapshot,qr_path_snapshot) VALUES (?,?,?)');
        $q->execute([$id,mb_substr($tutor['recipient'],0,200),$tutor['duitnow_qr_path']]);
        $q=$db->prepare('SELECT * FROM payments WHERE booking_id=? FOR UPDATE');$q->execute([$id]);$payment=$q->fetch();
    }
    if (!$payment) { tmq_payment_conflict('Open this booking payment before adding a receipt.'); }
    if (!in_array($payment['status'],['pending','underpaid','rejected','reupload_required'],true)) { tmq_payment_conflict('This payment is read-only.'); }
    return [$booking,$payment];
}

function tmq_payment_rollback(PDO $db, Throwable $error, ?array $upload = null): never
{
    if ($db->inTransaction()) { $db->rollBack(); }
    tmq_discard_upload($upload);
    if ($error instanceof PDOException && in_array((int)($error->errorInfo[1]??0),[1062,1205,1213],true)) {
        tmq_payment_conflict('Payment evidence was just updated. Reload the payment and try again.');
    }
    throw $error;
}

function tmq_open_payment(array $actor, int $bookingId): int
{
    tmq_payment_booking($actor,$bookingId);$db=tmq_db();
    try {
        $db->beginTransaction();[, $payment]=tmq_payment_lock($db,$actor,$bookingId,true);
        $db->commit();return (int)$payment['payment_id'];
    } catch (Throwable $error) { tmq_payment_rollback($db,$error); }
}

function tmq_submit_payment(array $actor, int $bookingId, array $input): int
{
    tmq_payment_booking($actor,$bookingId);
    $action=tmq_input($input,'payment_action');
    if (!in_array($action,['transfer','correct'],true)) { throw new DomainException('Choose a genuine transfer or a receipt correction.'); }
    $paymentId=tmq_booking_id(tmq_input($input,'payment_id'));
    $amount=tmq_payment_amount(tmq_input($input,'amount_paid'));
    $note=tmq_profile_text(tmq_input($input,'payment_note'),4000);
    $reference=tmq_profile_text(tmq_input($input,'bank_reference'),150);
    $date=tmq_input($input,'transfer_date');
    if ($date!=='') {
        $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$date,new DateTimeZone('Asia/Kuala_Lumpur'));
        if (!$parsed || $parsed->format('Y-m-d')!==$date || $date<'1000-01-01' || $date>'9999-12-31') { throw new DomainException('Enter the bank transfer date as YYYY-MM-DD, or leave it blank.'); }
    }
    $db=tmq_db();$upload=null;
    try {
        $db->beginTransaction();[$booking,$payment]=tmq_payment_lock($db,$actor,$bookingId);
        if ((int)$payment['payment_id']!==$paymentId) { tmq_booking_not_found(); }
        if ((int)$payment['evidence_revision']>=4294967295) { tmq_payment_conflict('Payment evidence revision limit reached. Contact support.'); }
        $attempt=1;$transferId=null;
        if ($action==='correct') {
            $transferId=tmq_booking_id(tmq_input($input,'transfer_id'));
            $submissionId=tmq_booking_id(tmq_input($input,'submission_id'));
            $q=$db->prepare('SELECT transfer_id FROM payment_transfers WHERE transfer_id=? AND payment_id=? FOR UPDATE');$q->execute([$transferId,$paymentId]);
            if (!$q->fetchColumn()) { tmq_booking_not_found(); }
            $q=$db->prepare('SELECT * FROM payment_submissions WHERE transfer_id=? ORDER BY attempt_number DESC LIMIT 1 FOR UPDATE');$q->execute([$transferId]);$current=$q->fetch();
            if (!$current || (int)$current['submission_id']!==$submissionId) { tmq_payment_conflict('This receipt is no longer current. Reload the payment.'); }
            if (!tmq_payment_correction_allowed($booking,$payment,$current,$submissionId)) { tmq_payment_conflict('This receipt cannot be replaced in its current review state. Wait for review; record a new transfer only if you actually sent additional money.'); }
            $attempt=(int)$current['attempt_number']+1;
            if ($attempt>65535) { tmq_payment_conflict('Receipt version limit reached. Contact support.'); }
        }
        $upload=tmq_receive_upload('receipt','receipts',true);
        $hash=hash_file('sha256',tmq_stored_file($upload['file_path']));
        if ($hash===false) { throw new RuntimeException('Unable to read stored receipt.'); }
        if ($action==='transfer') {
            $q=$db->prepare('INSERT INTO payment_transfers (payment_id,bank_reference) VALUES (?,?)');$q->execute([$paymentId,$reference===''?null:$reference]);$transferId=(int)$db->lastInsertId();
        }
        $q=$db->prepare('INSERT INTO payment_submissions (transfer_id,attempt_number,uploaded_by_user_id,amount_paid,transfer_date,payment_note,file_path,original_filename,mime_type,file_size_bytes,file_sha256) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute([$transferId,$attempt,$actor['user_id'],$amount,$date===''?null:$date,$note===''?null:$note,$upload['file_path'],$upload['original_filename'],$upload['mime_type'],$upload['file_size_bytes'],$hash]);
        $id=(int)$db->lastInsertId();
        $q=$db->prepare("UPDATE payments SET status='pending',evidence_revision=evidence_revision+1,admin_remark=NULL,reviewed_by_admin_id=NULL,reviewed_at=NULL WHERE payment_id=? AND status<>'verified' AND evidence_revision=?");
        $q->execute([$paymentId,$payment['evidence_revision']]);
        if ($q->rowCount()!==1) { tmq_payment_conflict('Payment evidence was just updated. Reload the payment.'); }
        $db->commit();return $id;
    } catch (Throwable $error) { tmq_payment_rollback($db,$error,$upload); }
}
