<?php
if (!isset($authUser,$booking) || !$booking) { http_response_code(404); exit; }
require_once __DIR__ . '/booking-cancellations.php';
require_once __DIR__ . '/booking-completion.php';
$cancelPage=$bookingRole==='admin'?'manage-bookings-cancel.php':$bookingRole.'-booking-cancel.php';
?>
<?php if($booking['status']==='cancelled'): ?>
<h3>Cancellation record</h3><dl class="portal-details">
<?php foreach(['Cancelled by'=>ucfirst($booking['cancelled_by_role']??'Unknown'),'Cancellation timing'=>$booking['cancellation_timing']??'',
    'Cancellation reason'=>$booking['cancellation_reason']??'','Cancelled on'=>$booking['cancelled_at']?tmq_booking_time($booking['cancelled_at']):'Not recorded'] as $label=>$value): ?>
<div><dt><?= tmq_escape($label) ?></dt><dd><?= nl2br(tmq_escape($value)) ?></dd></div><?php endforeach; ?></dl>
<p>This booking is retained in history. No cancellation fee, refund or penalty was automatically applied.</p>
<?php endif; ?>
<h3>Lesson completion</h3><dl class="portal-details">
<?php foreach(['student','tutor'] as $participant): ?><div><dt><?= ucfirst($participant) ?> completion confirmation</dt><dd><?= $booking[$participant.'_completed_at']?tmq_escape(tmq_booking_time($booking[$participant.'_completed_at'])):'Not confirmed' ?></dd></div><?php endforeach; ?>
</dl><p><?= tmq_escape(tmq_completion_label($booking)) ?></p>
<?php if($booking['status']==='completed'): ?><p>Both participants confirmed the lesson. This booking is completed, not closed. Receipt submission does not verify payment.</p><?php if($bookingRole==='student'): ?><a class="btn btn-outline" href="student-payment-view.php?id=<?= (int)$booking['booking_id'] ?>">View payment / submit receipt</a><?php endif; ?><?php endif; ?>
<?php if(!empty($isBookingSession)): ?>
<?php if($booking[$bookingRole.'_completed_at']): ?><p>You have confirmed lesson completion.</p>
<?php elseif(tmq_completion_eligible($booking)): ?>
<form method="post" class="portal-form"><?= tmq_csrf_field() ?><input type="hidden" name="lifecycle_action" value="complete"><input type="hidden" name="agreement_version" value="<?= (int)$booking['agreement_version'] ?>"><p>Confirm that this lesson took place.</p><button class="btn btn-primary" type="submit">Confirm Lesson Completed</button></form>
<?php else: ?><p>Completion is available for accepted lessons after the scheduled end, once both participants have confirmed the arrangement.</p><?php endif; ?>
<?php elseif(in_array($bookingRole,['student','tutor'],true) && in_array($booking['status'],['accepted','completed'],true)): ?>
<a class="btn btn-outline" href="<?= $bookingRole ?>-booking-session.php?id=<?= (int)$booking['booking_id'] ?>">View lesson / completion</a>
<?php endif; ?>
<?php if(tmq_cancellation_allowed($booking,$bookingRole)): ?>
<?php if(!empty($isBookingCancellation)): ?>
<h3><?= $bookingRole==='admin'?'Exceptional Admin cancellation':'Cancel booking' ?></h3>
<p><?= $bookingRole==='admin'?'Record the legitimate manual exception and its reason.':'You may cancel through the lesson’s Malaysia calendar date.' ?> Timing: <?= tmq_escape(tmq_cancellation_timing($booking)) ?>. No automatic fee, refund or penalty applies.</p>
<form method="post" class="portal-form"><?= tmq_csrf_field() ?><input type="hidden" name="lifecycle_action" value="cancel"><label class="form-label" for="cancellation-reason">Cancellation reason (plain text)</label><textarea class="form-control" id="cancellation-reason" name="reason" rows="4" maxlength="65535" required><?= tmq_escape(tmq_input($_POST,'reason')) ?></textarea><div class="portal-actions"><button type="submit" class="btn action-danger">Confirm Cancellation</button></div></form>
<?php else: ?><a class="btn btn-outline action-danger" href="<?= $cancelPage ?>?id=<?= (int)$booking['booking_id'] ?>"><?= $bookingRole==='admin'?'Exceptional cancellation':'Cancel booking' ?></a><?php endif; ?>
<?php elseif(!empty($isBookingCancellation)): ?><p><?= $bookingRole==='tutor' && $booking['status']==='requested'?'Requested bookings use Reject on the booking detail page.':'This booking cannot be cancelled through this workflow.' ?></p><a class="btn btn-outline" href="<?= $bookingDetailPage ?>?id=<?= (int)$booking['booking_id'] ?>">View booking</a><?php endif; ?>
