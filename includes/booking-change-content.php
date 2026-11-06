<?php if (!isset($authUser,$booking,$bookingChanges) || !in_array($authUser['role'],['student','tutor'],true)) { http_response_code(404); exit; }
$changePage=$bookingRole.'-booking-edit.php?id='.(int)$booking['booking_id'];
$responsePage=!empty($isBookingCoordination)?$bookingRole.'-booking-coordinate.php?id='.(int)$booking['booking_id']:$changePage;
$pendingChange=false;
foreach($bookingChanges as $change) { if($change['status']==='pending') { $pendingChange=true; } }
?>
<section class="portal-card">
<h2>Booking changes</h2>
<p>The current agreement remains effective until the other participant accepts. Proposed slots are not held and will be checked again at acceptance.</p>
<?php if(!$bookingChanges): ?><p>No change requests yet.</p><?php endif; ?>
<?php foreach($bookingChanges as $change): ?>
<article class="portal-card">
<h3>Change request #<?= (int)$change['change_request_id'] ?> &middot; <?= tmq_escape(ucfirst($change['status'])) ?></h3>
<p>Requested by <?= tmq_escape($change['requester_name']) ?> (<?= tmq_escape(ucfirst($change['requester_role'])) ?>) on <?= tmq_escape(tmq_booking_time($change['requested_at'])) ?>.</p>
<div class="portal-table-wrap"><table class="portal-table"><thead><tr><th>Agreement</th><th><?= $change['status']==='pending'?'Current at request':'Original' ?></th><th>Proposed<?= $change['status']==='accepted'?' (accepted)':'' ?></th></tr></thead><tbody>
<tr><th>Lesson start</th><td><?= tmq_escape(tmq_booking_time($change['original_start'])) ?></td><td><?= tmq_escape(tmq_booking_time($change['proposed_start'])) ?></td></tr>
<tr><th>Lesson end</th><td><?= tmq_escape(tmq_booking_time($change['original_end'])) ?></td><td><?= tmq_escape(tmq_booking_time($change['proposed_end'])) ?></td></tr>
<tr><th>Mode</th><td><?= tmq_escape(ucfirst($change['original_mode'])) ?></td><td><?= tmq_escape(ucfirst($change['requested_mode'])) ?></td></tr>
<tr><th>Expected amount</th><td>RM <?= tmq_escape($change['original_expected_amount']) ?></td><td>RM <?= tmq_escape($change['proposed_expected_amount']) ?></td></tr>
<?php foreach(['venue_address'=>'Physical venue','meeting_platform'=>'Meeting platform','meeting_url'=>'Meeting URL'] as $field=>$label): ?>
<?php if($change['original_'.$field]!==null || $change['requested_'.$field]!==null): ?><tr><th><?= $label ?></th><td><?= nl2br(tmq_escape($change['original_'.$field]??'Not applicable / not agreed')) ?></td><td><?= nl2br(tmq_escape($change['requested_'.$field]??'Not applicable / not agreed')) ?></td></tr><?php endif; ?>
<?php endforeach; ?>
</tbody></table></div>
<p><strong>Reason:</strong> <?= nl2br(tmq_escape($change['reason'])) ?></p>
<?php if($change['responded_at']): ?><p><?= $change['status']==='withdrawn'?'Withdrawn':'Responded' ?> on <?= tmq_escape(tmq_booking_time($change['responded_at'])) ?>.</p><?php endif; ?>
<?php if($change['response_note']): ?><p><strong>Response:</strong> <?= nl2br(tmq_escape($change['response_note'])) ?></p><?php endif; ?>
<?php if($change['status']==='pending'): ?>
<form method="post" action="<?= tmq_escape($responsePage) ?>" class="portal-form">
<?= tmq_csrf_field() ?><input type="hidden" name="change_id" value="<?= (int)$change['change_request_id'] ?>">
<?php if((int)$change['requested_by_user_id']===(int)$authUser['user_id']): ?>
<p>Waiting for the other participant to respond.</p><button class="btn btn-outline" type="submit" name="change_action" value="withdraw">Withdraw request</button>
<?php else: ?>
<label class="form-label" for="response-<?= (int)$change['change_request_id'] ?>">Response note (optional)</label><textarea class="form-control" id="response-<?= (int)$change['change_request_id'] ?>" name="response_note" maxlength="4000" rows="2"></textarea>
<div class="portal-actions"><?php if(tmq_change_eligible($booking)): ?><button class="btn btn-primary" type="submit" name="change_action" value="accept">Accept change</button><?php endif; ?><button class="btn btn-outline" type="submit" name="change_action" value="reject">Reject change</button></div>
<?php endif; ?></form>
<?php endif; ?></article>
<?php endforeach; ?>
<?php if(tmq_change_eligible($booking) && !$pendingChange): ?>
<?php if(empty($isBookingChangeEdit)): ?><a class="btn btn-primary" href="<?= tmq_escape($changePage) ?>">Request a booking change</a>
<?php else: ?>
<h3>Propose a schedule or mode change</h3>
<p>Choose the current slot for a mode-only change. Prices use your original agreed rate of RM <?= tmq_escape($booking['hourly_rate_snapshot']) ?>/hour. You can include arrangement details in the same proposal.</p>
<form method="post" class="portal-form"><?= tmq_csrf_field() ?><input type="hidden" name="change_action" value="create">
<div class="portal-fields"><div class="form-group form-group-wide"><label class="form-label" for="change-slot">Proposed lesson slot</label><select class="form-control" id="change-slot" name="slotId" required>
<?php foreach($changeSlots as $slot): $current=(int)$slot['slot_id']===(int)$booking['slot_id']; ?>
<option value="<?= (int)$slot['slot_id'] ?>" <?= (tmq_input($_POST,'slotId')?:((string)$booking['slot_id']))===(string)$slot['slot_id']?'selected':'' ?>><?= tmq_escape(($current?'Current slot: ':'').tmq_booking_time($slot['starts_at']).' to '.tmq_booking_time($slot['ends_at']).' — '.($current?'Mode change with Tutor support':ucfirst($slot['mode'])).' — RM '.tmq_booking_amount($booking['hourly_rate_snapshot'],$slot)) ?></option>
<?php endforeach; ?></select></div>
<div class="form-group"><label class="form-label" for="change-mode">Proposed mode</label><select class="form-control" id="change-mode" name="classType" required><?php foreach(['online','physical'] as $mode): ?><option value="<?= $mode ?>" <?= (tmq_input($_POST,'classType')?:$booking['class_mode'])===$mode?'selected':'' ?>><?= ucfirst($mode) ?></option><?php endforeach; ?></select></div>
<div class="form-group form-group-wide"><label><input type="checkbox" name="include_arrangement" value="1" <?= tmq_input($_POST,'include_arrangement')==='1'?'checked':'' ?>> Include new arrangement details</label><p class="field-help">When selected, provide the venue for Physical, or both platform and URL for Online. Otherwise existing applicable details are retained.</p>
<div data-arrangement-mode="physical"><label class="form-label" for="combined-venue">Physical venue / address</label><textarea class="form-control" id="combined-venue" name="venue_address" maxlength="500" rows="2"><?= tmq_escape(tmq_input($_POST,'venue_address')) ?></textarea></div>
<div data-arrangement-mode="online"><label class="form-label" for="combined-platform">Online meeting platform</label><input class="form-control" id="combined-platform" name="meeting_platform" maxlength="80" value="<?= tmq_escape(tmq_input($_POST,'meeting_platform')) ?>"><label class="form-label" for="combined-url">Online meeting URL</label><input class="form-control" id="combined-url" name="meeting_url" type="url" maxlength="2048" value="<?= tmq_escape(tmq_input($_POST,'meeting_url')) ?>"></div></div>
<div class="form-group form-group-wide"><label class="form-label" for="change-reason">Reason for change</label><textarea class="form-control" id="change-reason" name="reason" rows="3" required maxlength="4000"><?= tmq_escape(tmq_input($_POST,'reason')) ?></textarea></div></div>
<div class="portal-actions"><button class="btn btn-primary" type="submit">Send change request</button></div></form>
<?php endif; ?>
<?php elseif(!tmq_change_eligible($booking)): ?><p>New changes are available only for accepted bookings before the lesson starts.</p><?php endif; ?>
</section>
