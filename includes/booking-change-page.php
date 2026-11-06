<?php
declare(strict_types=1);
if (!isset($authUser) || !in_array($authUser['role'],['student','tutor'],true)) { http_response_code(404); exit; }
require_once __DIR__ . '/booking-changes.php';
$bookingRole=$authUser['role']; $bookingPage=basename($_SERVER['SCRIPT_FILENAME']);
$isBookingCoordination=$bookingPage===$bookingRole.'-booking-coordinate.php';
$isBookingChangeEdit=!$isBookingCoordination;
$bookingListPage=$bookingRole.'-bookings.php'; $bookingDetailPage=$bookingRole.'-booking-view.php';
$booking=null; $bookingMessage=''; $bookingChanges=[]; $changeSlots=[]; $bookingId=null;
try {
    if (!in_array($_SERVER['REQUEST_METHOD'],['GET','POST'],true)) { http_response_code(405); throw new DomainException('Use the booking change form.'); }
    if (!isset($_GET['id']) && $_SERVER['REQUEST_METHOD']==='GET') { $bookingMessage='Choose a booking from your booking list.'; }
    else {
        $bookingId=filter_var(tmq_input($_GET,'id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>4294967295]]);
        if (!$bookingId) { tmq_booking_not_found(); }
        $booking=tmq_change_participant($authUser,$bookingId);
    }
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        tmq_check_csrf(); $action=tmq_input($_POST,'change_action');
        if ($action==='confirm_arrangement' && $isBookingCoordination) {
            tmq_confirm_arrangement($authUser,$bookingId,tmq_booking_id(tmq_input($_POST,'agreement_version')));
        }
        elseif (in_array($action,['create','propose_arrangement'],true)) { tmq_create_booking_change($authUser,$bookingId,$_POST); }
        else { tmq_respond_booking_change($authUser,$bookingId,tmq_booking_id(tmq_input($_POST,'change_id')),$action,tmq_input($_POST,'response_note')); }
        $_SESSION['booking_change_saved']=$bookingId;
        tmq_redirect($bookingRole.'/'.$bookingPage.'?id='.$bookingId);
    }
    if ($booking && ($_SESSION['booking_change_saved']??null)===$bookingId) {
        unset($_SESSION['booking_change_saved']); $bookingMessage='Booking arrangement or change request updated.';
    }
} catch (DomainException $error) {
    if (!in_array(http_response_code(),[403,404,405,409],true)) { http_response_code(422); }
    $bookingMessage=$error->getMessage();
} catch (Throwable $error) {
    http_response_code(503); $booking=null; $bookingMessage='Booking changes are temporarily unavailable. Please try again later.';
}
if ($booking) {
    try {
        $booking=tmq_change_participant($authUser,$bookingId);
        $bookingChanges=tmq_change_history($authUser,$bookingId);
        if ($isBookingChangeEdit && tmq_change_eligible($booking)) { $changeSlots=tmq_change_slots($authUser,$booking); }
    } catch (Throwable $error) {
        http_response_code(503); $booking=null; $bookingMessage='Booking changes are temporarily unavailable. Please try again later.';
    }
}
