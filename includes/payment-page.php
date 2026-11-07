<?php
declare(strict_types=1);
if (!isset($authUser) || $authUser['role']!=='student') { http_response_code(403); exit; }
require_once __DIR__ . '/payment-verification.php';
$paymentPage=basename($_SERVER['SCRIPT_FILENAME']);
$paymentUploadPage=$paymentPage==='student-payment-upload.php';
$paymentListPage=$paymentPage==='student-invoices.php';
$paymentBooking=null;$payment=null;$paymentHistory=[];$paymentBookings=[];$paymentMessage='';$paymentBookingId=null;
try {
    if (!in_array($_SERVER['REQUEST_METHOD'],['GET','POST'],true) || ($_SERVER['REQUEST_METHOD']==='POST' && !$paymentUploadPage)) {
        http_response_code(405);throw new DomainException('This page is read-only. Use the payment submission form.');
    }
    if ($paymentListPage) { $paymentBookings=tmq_booking_records($authUser); }
    elseif (!isset($_GET['id']) && $_SERVER['REQUEST_METHOD']==='GET') { $paymentMessage='Choose a booking from your lesson charges.'; }
    else {
        $paymentBookingId=filter_var(tmq_input($_GET,'id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>4294967295]]);
        if (!$paymentBookingId) { tmq_booking_not_found(); }
        $paymentBooking=tmq_payment_booking($authUser,$paymentBookingId);
        if (!tmq_payment_eligible($paymentBooking) && $paymentBooking['status']!=='closed') { tmq_payment_conflict('Payment submission is available only after both participants complete the lesson.'); }
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH']??0)>0) { http_response_code(413);throw new DomainException('The request is too large. Maximum receipt size is 5 MB.'); }
            tmq_check_csrf();$action=tmq_input($_POST,'payment_action');
            if ($action==='open') { tmq_open_payment($authUser,$paymentBookingId); }
            else { tmq_submit_payment($authUser,$paymentBookingId,$_POST); }
            $_SESSION['payment_saved']=$action==='open'?'Payment opened. No transfer has been recorded.':'Receipt submitted. Uploading evidence does not verify a payment.';
            tmq_redirect('student/'.($action==='open'?'student-payment-upload.php':'student-payment-view.php').'?id='.$paymentBookingId);
        }
        if (isset($_SESSION['payment_saved'])) { $paymentMessage=$_SESSION['payment_saved'];unset($_SESSION['payment_saved']); }
    }
} catch (DomainException $error) {
    if (!in_array(http_response_code(),[403,404,405,409,413],true)) { http_response_code(422); }
    $paymentMessage=$error->getMessage();
} catch (Throwable $error) {
    http_response_code(503);$paymentBooking=null;$paymentMessage='Payments are temporarily unavailable. Please try again later.';
}
if ($paymentBooking && $paymentBookingId) {
    try {
        $db=tmq_db();$db->beginTransaction();
        $paymentBooking=tmq_payment_booking($authUser,$paymentBookingId);
        $payment=tmq_payment_record($authUser,$paymentBookingId);
        $paymentHistory=tmq_payment_history($authUser,$paymentBookingId);
        $paymentTotals=$payment?tmq_payment_settlement($paymentBooking,$payment,tmq_payment_current($db,(int)$payment['payment_id'])):null;
        $db->commit();
    } catch (Throwable $error) { if(isset($db) && $db->inTransaction()) { $db->rollBack(); } http_response_code(503);$paymentBooking=null;$paymentMessage='Payments are temporarily unavailable. Please try again later.'; }
}
