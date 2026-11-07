<?php
declare(strict_types=1);
if (!isset($authUser) || !in_array($authUser['role'],['tutor','admin'],true)) { http_response_code(403);exit; }
require_once __DIR__.'/payment-verification.php';
$reviewRole=$authUser['role'];$reviewAdmin=$reviewRole==='admin';
$reviewPage=basename($_SERVER['SCRIPT_FILENAME']);
$reviewClosed=$reviewPage==='manage-bookings-closed.php';
$reviewVerified=$reviewPage==='manage-payments-confirmed.php';
$reviewList=in_array($reviewPage,['tutor-payments.php','manage-payments.php','manage-payments-confirmed.php','manage-bookings-closed.php'],true);
$reviewDetail=$reviewAdmin?'manage-payments-view.php':'tutor-payment-view.php';
$reviewMessage='';$reviewBooking=null;$reviewPayment=null;$reviewHistory=[];$reviewRows=[];$reviewTotals=null;
try {
    if (!in_array($_SERVER['REQUEST_METHOD'],['GET','POST'],true) || ($_SERVER['REQUEST_METHOD']==='POST' && $reviewList)) {
        http_response_code(405);throw new DomainException('This page is read-only. Use the payment review form.');
    }
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        tmq_check_csrf();
        $id=tmq_booking_id(tmq_input($_GET,'id'));
        tmq_review_payment($authUser,$id,$_POST);
        $_SESSION['payment_review_saved']=tmq_input($_POST,'payment_action')==='verify'?'Payment verified and booking closed.':'Evidence decision saved. Final payment verification is a separate Admin action.';
        tmq_redirect($reviewRole.'/'.$reviewDetail.'?id='.$id);
    }
    if (isset($_SESSION['payment_review_saved'])) { $reviewMessage=$_SESSION['payment_review_saved'];unset($_SESSION['payment_review_saved']); }
} catch (DomainException $error) {
    if (!in_array(http_response_code(),[403,404,405,409],true)) { http_response_code(422); }
    $reviewMessage=$error->getMessage();
} catch (Throwable $error) { http_response_code(503);$reviewMessage='Payment review is temporarily unavailable. Please try again later.'; }

try {
    // One consistent read snapshot ties displayed evidence and eligibility to the form revision.
    $db=tmq_db();$db->beginTransaction();
    if ($reviewList) {
        $where=$reviewAdmin?'1=1':'t.user_id=?';$params=$reviewAdmin?[]:[$authUser['user_id']];
        if ($reviewVerified) { $where.=" AND p.status='verified'"; }
        if ($reviewClosed) { $where.=" AND b.status='closed'"; }
        $reviewRows=tmq_profile_rows("SELECT b.booking_id,b.starts_at,b.expected_amount,b.status AS booking_status,b.closed_at,
            p.payment_id,p.status,p.evidence_revision,p.verified_at,CONCAT(su.first_name,' ',su.last_name) AS student_name,
            COALESCE(NULLIF(t.display_name,''),CONCAT(tu.first_name,' ',tu.last_name)) AS tutor_name
            FROM bookings b JOIN students s ON s.student_id=b.student_id JOIN users su ON su.user_id=s.user_id
            JOIN tutors t ON t.tutor_id=b.tutor_id JOIN users tu ON tu.user_id=t.user_id
            ".($reviewClosed?'LEFT JOIN':'JOIN')." payments p ON p.booking_id=b.booking_id WHERE $where ORDER BY b.starts_at DESC,b.booking_id DESC",$params);
    } elseif (isset($_GET['id'])) {
        $id=tmq_booking_id(tmq_input($_GET,'id'));$reviewBooking=tmq_booking_record($authUser,$id);
        $reviewPayment=tmq_profile_rows('SELECT * FROM payments WHERE booking_id=?',[$id])[0]??null;
        if ($reviewPayment) {
            $reviewHistory=tmq_profile_rows('SELECT s.*,t.bank_reference,
                (s.attempt_number=(SELECT MAX(c.attempt_number) FROM payment_submissions c WHERE c.transfer_id=s.transfer_id)) AS is_current,
                EXISTS(SELECT 1 FROM payment_submissions d WHERE d.file_sha256=s.file_sha256 AND d.submission_id<>s.submission_id) AS duplicate_evidence,
                (t.bank_reference IS NOT NULL AND EXISTS(SELECT 1 FROM payment_transfers d WHERE d.bank_reference=t.bank_reference AND d.transfer_id<>t.transfer_id)) AS duplicate_reference
                FROM payment_transfers t JOIN payment_submissions s ON s.transfer_id=t.transfer_id WHERE t.payment_id=? ORDER BY t.transfer_id DESC,s.attempt_number DESC',[$reviewPayment['payment_id']]);
            $reviewTotals=tmq_payment_settlement($reviewBooking,$reviewPayment,tmq_payment_current($db,(int)$reviewPayment['payment_id']));
        }
    }
    $db->commit();
} catch (DomainException $error) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    if (!in_array(http_response_code(),[403,404,405,409],true)) { http_response_code(422); }
    $reviewBooking=null;$reviewMessage=$error->getMessage();
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    http_response_code(503);$reviewBooking=null;$reviewRows=[];$reviewMessage='Payment records are temporarily unavailable. Please try again later.';
}
