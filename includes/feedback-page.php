<?php
if (!isset($authUser) || !in_array($authUser['role'],['student','tutor'],true)) { http_response_code(403); exit; }
require_once __DIR__.'/feedback.php';
$feedbackRole=$authUser['role'];
$feedbackForm=basename($_SERVER['SCRIPT_FILENAME'])==='student-feedback-addNew.php';
$feedbackMessage=''; $feedbackBooking=null; $feedbackRows=[]; $feedbackAvailable=[]; $feedbackPage=1;
$feedbackId=null;
try {
    if (!in_array($_SERVER['REQUEST_METHOD'],['GET','POST'],true) || ($_SERVER['REQUEST_METHOD']==='POST' && !$feedbackForm)) {
        http_response_code(405); throw new DomainException('This page is read-only. Use the Student feedback submission form.');
    }
    if (isset($_GET['id'])) { $feedbackId=tmq_booking_id(tmq_input($_GET,'id')); $feedbackBooking=tmq_booking_record($authUser,$feedbackId); }
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        tmq_check_csrf();
        if (!$feedbackId) { throw new DomainException('Choose an eligible booking first.'); }
        tmq_submit_feedback($authUser,$feedbackId,$_POST);
        tmq_redirect('student/student-feedbacks.php?id='.$feedbackId);
    }
    if (!$feedbackId && isset($_GET['page'])) {
        $feedbackPage=filter_var(tmq_input($_GET,'page'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1000000]]);
        if (!$feedbackPage) { $feedbackPage=1; throw new DomainException('Choose a valid page.'); }
    }
} catch (DomainException $error) {
    if (!in_array(http_response_code(),[403,404,405,409],true)) { http_response_code(422); }
    $feedbackMessage=$error->getMessage();
} catch (Throwable $error) { http_response_code(503); $feedbackBooking=null; $feedbackMessage='Feedback is temporarily unavailable. Please try again.'; }
try {
    // A conflict may have waited behind a lifecycle transition. Render current
    // eligibility, never the booking snapshot from before the submission.
    if ($feedbackBooking && $_SERVER['REQUEST_METHOD']==='POST') { $feedbackBooking=tmq_booking_record($authUser,$feedbackId); }
    if (!isset($_GET['id']) || $feedbackBooking) {
        $feedbackRows=tmq_feedback_history($authUser,$feedbackId,false,$feedbackPage);
        if ($feedbackRole==='student' && !$feedbackId) { $feedbackAvailable=tmq_feedback_history($authUser,null,true,$feedbackPage); }
    }
} catch (Throwable $error) { http_response_code(503); $feedbackBooking=null; $feedbackRows=[]; $feedbackAvailable=[]; $feedbackMessage='Feedback is temporarily unavailable. Please try again.'; }
