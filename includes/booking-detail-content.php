<?php if (!isset($authUser,$bookingMessage)) { http_response_code(404); exit; } ?>
<header class="portal-heading"><div><span class="section-subtitle"><?= $bookingRole==='admin'?'Operations workspace':($bookingRole==='tutor'?'Tutor studio':'Your lesson') ?></span><h1><?= !empty($isBookingCoordination)?'Arrange your class':(!empty($isBookingCancellation)?'Cancel booking':(!empty($isBookingSession)?'Lesson session and completion':'Booking details')) ?><?= $booking?' #'.(int)$booking['booking_id']:'' ?></h1></div></header>
<?php if($bookingMessage): ?><p role="status"><?= tmq_escape($bookingMessage) ?></p><?php endif; ?>
<?php if($booking): ?>
<section class="portal-card <?= $bookingRole==='tutor'?'tutor-booking-card':'' ?>"><?= tmq_booking_badge($booking['status']) ?><h2><?= tmq_escape($booking['lesson_focus_summary']?:'Quran lesson') ?></h2>
<h3>Current booking agreement</h3><dl class="portal-details">
<?php $details=['Booking'=>(string)$booking['booking_id']];
if($bookingRole!=='student'){$details['Student']=$booking['student_name'];}
if($bookingRole!=='tutor'){$details['Tutor']=$booking['tutor_name'];}
$details += ['Lesson start'=>tmq_booking_time($booking['starts_at']),'Lesson end'=>tmq_booking_time($booking['ends_at']),'Duration'=>intdiv(tmq_slot_local($booking['ends_at'])->getTimestamp()-tmq_slot_local($booking['starts_at'])->getTimestamp(),3600).' hour(s)','Mode'=>ucfirst($booking['class_mode']),'Hourly rate (agreed)'=>'RM '.$booking['hourly_rate_snapshot'],'Expected amount'=>'RM '.$booking['expected_amount'],'Requested on'=>tmq_booking_time($booking['created_at']),'Learning notes'=>$booking['notes']?:'None'];
if($booking['decision_at']){$details['Decision on']=tmq_booking_time($booking['decision_at']);}
if($booking['status']==='rejected'){$details['Reason for rejection']=$booking['rejection_reason']??'';}
foreach($details as $label=>$value): ?><div><dt><?= tmq_escape($label) ?></dt><dd><?= nl2br(tmq_escape($value)) ?></dd></div><?php endforeach; ?></dl>
<?php if($booking['status']==='requested'): ?>
<?php if($bookingRole==='tutor' && $bookingPage==='tutor-booking-view.php'): ?>
<h3>Can you teach this lesson?</h3><p>Accept this request or explain why you cannot teach the lesson.</p>
<?php if(tmq_slot_local($booking['starts_at'])>new DateTimeImmutable('now',new DateTimeZone('UTC'))): ?>
<form method="post"><?= tmq_csrf_field() ?><input type="hidden" name="decision" value="accept"><div class="portal-actions"><button type="submit" class="btn btn-primary">Accept booking</button></div></form>
<?php else: ?><p>This lesson has started and can no longer be accepted.</p><?php endif; ?>
<details class="tutor-decline"><summary class="action-danger">Reject booking</summary><form method="post"><?= tmq_csrf_field() ?><input type="hidden" name="decision" value="reject"><label class="form-label" for="booking-reason">Reason for rejection</label><textarea id="booking-reason" name="reason" class="form-control" rows="3" required maxlength="600" placeholder="Explain why you cannot take this lesson."><?= tmq_escape(tmq_input($_POST,'reason')) ?></textarea><div class="portal-actions"><button type="submit" class="btn btn-outline action-danger" data-confirm="Reject this booking request?">Reject booking</button></div></form></details>
<?php else: ?><p>Waiting for tutor acceptance.</p><?php endif; ?>
<?php elseif($booking['status']==='accepted'): ?><p>The Tutor accepted this booking. Your lesson slot remains reserved.</p>
<?php elseif($booking['status']==='rejected'): ?><p>The Tutor rejected this booking. This record is retained in your booking history.</p><?php endif; ?>
<?php if(in_array($bookingRole,['student','tutor'],true)): ?><a class="btn btn-outline" href="<?= $bookingRole ?>-chat.php?id=<?= (int)$booking['booking_id'] ?>"><?= $bookingRole==='student'?'Message Tutor':'Message Student' ?></a><?php endif; ?>
<?php require __DIR__ . '/booking-arrangement-content.php'; ?>
<?php require __DIR__ . '/booking-lifecycle-content.php'; ?>
</section>
<?php if(in_array($bookingRole,['student','tutor'],true)): require __DIR__ . '/booking-change-content.php'; endif; ?>
<?php endif; ?>
<a class="booking-back-link" href="<?= $bookingListPage ?>">&larr; Back to bookings</a>
<?php if($booking && $bookingPage==='tutor-booking-rejected.php'): ?><a class="btn btn-outline btn-sm" href="tutor-booking-view.php?id=<?= (int)$booking['booking_id'] ?>">View booking</a><?php endif; ?>
