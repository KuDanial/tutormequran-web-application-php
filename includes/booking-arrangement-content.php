<?php if (!isset($authUser,$booking)) { http_response_code(404); exit; }
$coordinationPage=$bookingRole.'-booking-coordinate.php?id='.(int)$booking['booking_id'];
$arrangementPending=false;
foreach($bookingChanges as $request) { if($request['status']==='pending') { $arrangementPending=true; } }
?>
<h3>Current arrangement &middot; version <?= (int)$booking['agreement_version'] ?></h3>
<dl class="portal-details">
<?php $arrangementDetails=$booking['class_mode']==='physical'
    ?['Physical venue'=>$booking['venue_address']?:'Not yet agreed']
    :['Meeting platform'=>$booking['meeting_platform']?:'Not yet agreed','Meeting URL'=>$booking['meeting_url']?:'Not yet agreed'];
$arrangementDetails += ['Student confirmation'=>$booking['student_arranged_at']?tmq_booking_time($booking['student_arranged_at']):'Not confirmed',
    'Tutor confirmation'=>$booking['tutor_arranged_at']?tmq_booking_time($booking['tutor_arranged_at']):'Not confirmed'];
foreach($arrangementDetails as $label=>$value): ?><div><dt><?= tmq_escape($label) ?></dt><dd><?= nl2br(tmq_escape($value)) ?></dd></div><?php endforeach; ?>
</dl>
<?php if($booking['status']==='accepted'): ?><p><strong><?= tmq_escape(tmq_arrangement_label($booking)) ?></strong></p><?php endif; ?>
<?php if(in_array($bookingRole,['student','tutor'],true) && tmq_change_eligible($booking)): ?>
<?php if(empty($isBookingCoordination)): ?>
<a class="btn btn-outline" href="<?= tmq_escape($coordinationPage) ?>"><?= $booking['student_arranged_at'] && $booking['tutor_arranged_at']?'Review arrangement':'Coordinate / Arrange' ?></a>
<?php else: ?>
<p>Accepting a proposal agrees the details. Each participant must then separately confirm the current arrangement.</p>
<?php if($booking[$bookingRole.'_arranged_at']): ?><p>You have confirmed this version.</p>
<?php elseif(tmq_arrangement_ready($booking)): ?>
<form method="post" class="portal-form"><?= tmq_csrf_field() ?><input type="hidden" name="change_action" value="confirm_arrangement"><input type="hidden" name="agreement_version" value="<?= (int)$booking['agreement_version'] ?>"><button type="submit" class="btn btn-primary">Confirm Arrangement</button></form>
<?php else: ?><p>Agree the <?= $booking['class_mode']==='physical'?'physical venue':'meeting platform and URL' ?> before confirming.</p><?php endif; ?>
<?php if(!$arrangementPending): ?>
<h3>Propose <?= $booking['class_mode']==='physical'?'a physical venue':'online meeting details' ?></h3>
<form method="post" class="portal-form"><?= tmq_csrf_field() ?><input type="hidden" name="change_action" value="propose_arrangement"><input type="hidden" name="agreement_version" value="<?= (int)$booking['agreement_version'] ?>">
<div class="portal-fields">
<?php if($booking['class_mode']==='physical'): ?>
<div class="form-group form-group-wide"><label class="form-label" for="venue-address">Venue / address</label><textarea class="form-control" id="venue-address" name="venue_address" maxlength="500" rows="3" required><?= tmq_escape($_SERVER['REQUEST_METHOD']==='POST'?tmq_input($_POST,'venue_address'):($booking['venue_address']??'')) ?></textarea></div>
<?php else: ?>
<div class="form-group"><label class="form-label" for="meeting-platform">Meeting platform</label><input class="form-control" id="meeting-platform" name="meeting_platform" maxlength="80" required value="<?= tmq_escape($_SERVER['REQUEST_METHOD']==='POST'?tmq_input($_POST,'meeting_platform'):($booking['meeting_platform']??'')) ?>"></div>
<div class="form-group"><label class="form-label" for="meeting-url">Meeting URL</label><input class="form-control" id="meeting-url" name="meeting_url" type="url" maxlength="2048" required value="<?= tmq_escape($_SERVER['REQUEST_METHOD']==='POST'?tmq_input($_POST,'meeting_url'):($booking['meeting_url']??'')) ?>"><p class="field-help">Use an HTTP or HTTPS link to your external meeting service.</p></div>
<?php endif; ?>
<div class="form-group form-group-wide"><label class="form-label" for="arrangement-reason">Reason for proposal</label><textarea class="form-control" id="arrangement-reason" name="reason" maxlength="4000" rows="3" required><?= tmq_escape(tmq_input($_POST,'reason')) ?></textarea></div>
</div><button type="submit" class="btn btn-primary">Send arrangement proposal</button></form>
<?php else: ?><p>A change request is pending. Review it below before making another proposal. Confirmations apply to the current agreement; accepting a change will clear both confirmations.</p><?php endif; ?>
<?php endif; ?>
<?php elseif(!empty($isBookingCoordination)): ?><p>Coordination is available only for accepted bookings before the lesson starts.</p><?php endif; ?>
