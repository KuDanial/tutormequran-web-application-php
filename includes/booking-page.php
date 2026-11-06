<?php
declare(strict_types=1);
if (!isset($authUser) || !in_array($authUser['role'],['student','tutor','admin'],true)) { http_response_code(404); exit; }
require_once __DIR__ . '/booking-changes.php';
$bookingPage=basename($_SERVER['SCRIPT_FILENAME']);
$bookingRole=$authUser['role'];
$bookingListPage=['student'=>'student-bookings.php','tutor'=>'tutor-bookings.php','admin'=>'manage-bookings.php'][$bookingRole];
$bookingDetailPage=['student'=>'student-booking-view.php','tutor'=>'tutor-booking-view.php','admin'=>'manage-bookings-view.php'][$bookingRole];
$isBookingList=$bookingPage===$bookingListPage;
$booking=null; $bookingRows=[]; $bookingChanges=[]; $bookingMessage=''; $bookingStatus=tmq_input($_GET,'status');
$bookingId=null;
try {
    if ($_SERVER['REQUEST_METHOD']==='POST' && ($bookingRole!=='tutor' || $bookingPage!=='tutor-booking-view.php')) {
        http_response_code(405); throw new DomainException('This page is read-only.');
    }
    if (!$isBookingList) {
        if (!isset($_GET['id']) && $_SERVER['REQUEST_METHOD']==='GET') { $bookingMessage='Choose a booking from your booking list.'; }
        else {
            $bookingId=filter_var(tmq_input($_GET,'id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>4294967295]]);
            if (!$bookingId) { tmq_booking_not_found(); }
            $booking=tmq_booking_record($authUser,$bookingId);
        }
    }
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        tmq_check_csrf();
        tmq_decide_booking($authUser,$bookingId,tmq_input($_POST,'decision'),tmq_input($_POST,'reason'));
        $_SESSION['booking_decided']=$bookingId;
        tmq_redirect('tutor/tutor-booking-view.php?id='.$bookingId);
    }
    if ($isBookingList) { $bookingRows=tmq_booking_records($authUser,null,$bookingStatus); }
    if ($booking && ($_SESSION['booking_decided']??null)===$bookingId) {
        unset($_SESSION['booking_decided']); $bookingMessage='Booking decision saved.';
    }
} catch (DomainException $error) {
    if (!in_array(http_response_code(),[403,404,405,409],true)) { http_response_code(422); }
    $bookingMessage=$error->getMessage();
    // Refresh displayed state after a stale action; never render an old actionable form.
    if ($booking && $bookingId) { try { $booking=tmq_booking_record($authUser,$bookingId); } catch (Throwable $ignored) { $booking=null; } }
} catch (Throwable $error) {
    http_response_code(503); $booking=null; $bookingMessage='Bookings are temporarily unavailable. Please try again later.';
}
if ($booking && in_array($bookingRole,['student','tutor'],true)) {
    try { $bookingChanges=tmq_change_history($authUser,(int)$booking['booking_id']); }
    catch (Throwable $error) { http_response_code(503); $booking=null; $bookingMessage='Booking changes are temporarily unavailable. Please try again later.'; }
}
