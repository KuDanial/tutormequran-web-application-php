<?php if (!isset($authUser,$reviewRole)) { http_response_code(404);exit; } ?>
<header class="portal-heading"><div><span class="section-subtitle">Payment records</span><h1><?= $reviewClosed?'Closed bookings':($reviewVerified?'Verified payments':($reviewList?'Payments':'Payment evidence review')) ?></h1><p>Tutor Received acknowledges one transfer. Admin Valid accepts its evidence. Final verification settles the entire booking.</p></div></header>
<?php if($reviewMessage): ?><p role="status"><?= tmq_escape($reviewMessage) ?></p><?php endif; ?>
<?php if($reviewList): ?>
<?php if($reviewClosed): ?><p>No closing action is available on this page. Booking closure occurs only through successful final Admin payment verification.</p><?php endif; ?>
<?php if(!$reviewRows): ?><p>No matching records.</p><?php endif; ?>
<?php foreach($reviewRows as $row): ?><section class="portal-card"><h2>Booking #<?= (int)$row['booking_id'] ?> &middot; <?= $row['payment_id']?'PAY-'.(int)$row['payment_id']:'No payment record' ?></h2>
<p><?= tmq_escape($row['student_name']) ?> &middot; <?= tmq_escape($row['tutor_name']) ?> &middot; <?= tmq_escape(tmq_booking_time($row['starts_at'])) ?></p>
<p>Expected RM <?= tmq_escape($row['expected_amount']) ?> &middot; Payment: <?= tmq_escape(ucfirst(str_replace('_',' ',$row['status']??'unavailable'))) ?> &middot; Booking: <?= tmq_escape($row['booking_status']) ?></p>
<?php if($row['closed_at']): ?><p>Closed: <?= tmq_escape(tmq_booking_time($row['closed_at'])) ?></p><?php endif; ?>
<?php if($row['verified_at']): ?><p>Verified: <?= tmq_escape(tmq_booking_time($row['verified_at'])) ?></p><?php endif; ?>
<a class="btn btn-outline" href="<?= $reviewDetail ?>?id=<?= (int)$row['booking_id'] ?>">View payment history</a></section><?php endforeach; ?>
<?php elseif($reviewBooking): ?>
<section class="portal-card"><h2>Booking #<?= (int)$reviewBooking['booking_id'] ?></h2><dl class="portal-details">
<?php foreach(['Student'=>$reviewBooking['student_name'],'Tutor'=>$reviewBooking['tutor_name'],'Lesson'=>tmq_booking_time($reviewBooking['starts_at']),
    'Expected amount'=>'RM '.$reviewBooking['expected_amount'],'Booking status'=>ucfirst($reviewBooking['status']),
    'Payment status'=>$reviewPayment?ucfirst(str_replace('_',' ',$reviewPayment['status'])):'Not opened'] as $label=>$value): ?><div><dt><?= tmq_escape($label) ?></dt><dd><?= tmq_escape($value) ?></dd></div><?php endforeach; ?>
<?php if($reviewPayment): ?><div><dt>Payment reference</dt><dd>PAY-<?= (int)$reviewPayment['payment_id'] ?></dd></div><div><dt>Evidence revision</dt><dd><?= (int)$reviewPayment['evidence_revision'] ?></dd></div>
<div><dt>Current valid total</dt><dd>RM <?= tmq_escape($reviewTotals['valid_total']) ?></dd></div><div><dt>Outstanding</dt><dd>RM <?= tmq_escape($reviewTotals['outstanding']) ?></dd></div>
<?php if($reviewPayment['verified_at']): ?><div><dt>Final verification</dt><dd><?= tmq_escape(tmq_booking_time($reviewPayment['verified_at'])) ?></dd></div><?php if($reviewAdmin): ?><div><dt>Verified by</dt><dd>Admin #<?= (int)$reviewPayment['verified_by_admin_id'] ?></dd></div><?php endif; ?><?php endif; ?>
<?php endif; ?></dl>
<?php if($reviewTotals): ?><p role="status"><?= tmq_escape($reviewTotals['message']) ?></p><?php endif; ?></section>
<?php if($reviewPayment): ?>
<?php $reviewWritable=tmq_payment_eligible($reviewBooking) && $reviewPayment['status']!=='verified' && $reviewPayment['verified_at']===null && $reviewPayment['verified_by_admin_id']===null; ?>
<section class="portal-card"><h2>Transfers and immutable receipt history</h2><p>Only the current version of each transfer counts, and only with Tutor Received and Admin Valid. Historical versions and rejected evidence contribute RM0.</p>
<?php if(!$reviewHistory): ?><p>No receipt evidence submitted.</p><?php endif; ?>
<?php foreach($reviewHistory as $receipt): ?>
<article class="portal-card"><h3>Transfer #<?= (int)$receipt['transfer_id'] ?> &middot; Version <?= (int)$receipt['attempt_number'] ?> &middot; <?= $receipt['is_current']?'Current':'Historical (read-only)' ?></h3><dl class="portal-details">
<?php $reviewFields=['Claimed amount'=>'RM '.$receipt['amount_paid'],'Bank reference'=>$receipt['bank_reference']??'Not supplied','Transfer date'=>$receipt['transfer_date']??'Not supplied','Student note'=>$receipt['payment_note']??'None',
    'Tutor acknowledgement'=>ucfirst($receipt['tutor_status']),'Tutor issue'=>str_replace('_',' ',$receipt['tutor_issue_type']??'None'),'Tutor remark'=>$receipt['tutor_remark']??'None',
    'Tutor reviewed at'=>$receipt['tutor_reviewed_at']?tmq_booking_time($receipt['tutor_reviewed_at']):'Not reviewed','Admin evidence decision'=>ucfirst(str_replace('_',' ',$receipt['admin_status']))];
if($reviewAdmin) { $reviewFields+=['Admin remark'=>$receipt['admin_remark']??'None','Admin reviewer'=>$receipt['reviewed_by_admin_id']===null?'Not reviewed':'Admin #'.$receipt['reviewed_by_admin_id'],'Admin reviewed at'=>$receipt['admin_reviewed_at']?tmq_booking_time($receipt['admin_reviewed_at']):'Not reviewed']; }
foreach($reviewFields as $label=>$value): ?><div><dt><?= tmq_escape($label) ?></dt><dd><?= nl2br(tmq_escape($value)) ?></dd></div><?php endforeach; ?></dl>
<a class="btn btn-outline" href="<?= $reviewAdmin?'manage-payments-file.php':'tutor-payment-file.php' ?>?id=<?= (int)$receipt['submission_id'] ?>">Download <?= tmq_escape($receipt['original_filename']) ?></a>
<?php if($receipt['duplicate_evidence'] || $receipt['duplicate_reference']): ?><p role="status">Duplicate evidence warning: this file or bank reference matches other evidence. Check the genuine transfer before accepting it.</p><?php endif; ?>
<?php if($reviewWritable && $receipt['is_current'] && $receipt['admin_status']==='pending' && ($reviewAdmin || $receipt['tutor_status']==='awaiting')): ?>
<form method="post" action="<?= $reviewDetail ?>?id=<?= (int)$reviewBooking['booking_id'] ?>" class="portal-form">
<?= tmq_csrf_field() ?><input type="hidden" name="payment_action" value="<?= $reviewAdmin?'admin_review':'tutor_review' ?>"><input type="hidden" name="submission_id" value="<?= (int)$receipt['submission_id'] ?>"><input type="hidden" name="evidence_revision" value="<?= (int)$reviewPayment['evidence_revision'] ?>">
<label class="form-label" for="decision-<?= (int)$receipt['submission_id'] ?>"><?= $reviewAdmin?'Admin evidence decision':'Tutor acknowledgement' ?></label>
<select class="form-control" required name="decision" id="decision-<?= (int)$receipt['submission_id'] ?>"><option value="">Choose a decision</option>
<?php if($reviewAdmin): ?><?php if($receipt['tutor_status']==='received'): ?><option value="valid">Valid — accept this transfer's evidence</option><?php endif; ?><option value="rejected">Rejected — exclude this claim</option><option value="reupload_required">Request Reupload — correct this transfer's evidence</option>
<?php else: ?><option value="received">Received</option><option value="issue">Issue</option><?php endif; ?></select>
<?php if(!$reviewAdmin): ?><label class="form-label" for="issue-<?= (int)$receipt['submission_id'] ?>">Issue type (required for Issue)</label><select class="form-control" name="tutor_issue_type" id="issue-<?= (int)$receipt['submission_id'] ?>"><option value="">Choose when reporting an issue</option><option value="not_received">Not received</option><option value="amount_mismatch">Amount mismatch</option><option value="reference_mismatch">Reference mismatch</option></select><?php endif; ?>
<label class="form-label" for="remark-<?= (int)$receipt['submission_id'] ?>">Private review remark <?= $reviewAdmin?'(required for rejection or reupload)':'(optional)' ?></label><textarea class="form-control" name="remark" id="remark-<?= (int)$receipt['submission_id'] ?>" maxlength="4000" rows="3"></textarea><p>This decision cannot be rewritten on the same receipt version.</p><button type="submit" class="btn btn-primary">Save <?= $reviewAdmin?'evidence decision':'acknowledgement' ?></button></form>
<?php endif; ?></article><?php endforeach; ?></section>
<?php if($reviewAdmin && $reviewWritable): ?><section class="portal-card"><h2>Final Verify Payment</h2><p>This separate action requires all current evidence resolved, the exact expected valid total, and both lesson completion confirmations. Rejected claims may remain excluded.</p>
<?php if($reviewTotals['ready']): ?><form method="post" action="<?= $reviewDetail ?>?id=<?= (int)$reviewBooking['booking_id'] ?>"><?= tmq_csrf_field() ?><input type="hidden" name="payment_action" value="verify"><input type="hidden" name="evidence_revision" value="<?= (int)$reviewPayment['evidence_revision'] ?>"><button class="btn btn-primary" type="submit">Final Verify Payment and Close Booking</button></form><?php else: ?><p>Final verification is unavailable: <?= tmq_escape($reviewTotals['message']) ?></p><?php endif; ?></section><?php endif; ?>
<?php if(!$reviewWritable): ?><p>This payment history is read-only.</p><?php endif; ?>
<?php endif; ?>
<a class="booking-back-link" href="<?= $reviewAdmin?'manage-bookings-view.php':'tutor-booking-view.php' ?>?id=<?= (int)$reviewBooking['booking_id'] ?>">View booking</a>
<?php else: ?><p>Choose a booking from the payment list.</p><?php endif; ?>
<nav class="portal-actions"><a class="btn btn-outline" href="<?= $reviewAdmin?'manage-payments.php':'tutor-payments.php' ?>">All payments</a><?php if($reviewAdmin): ?><a class="btn btn-outline" href="manage-payments-confirmed.php">Verified payments</a><a class="btn btn-outline" href="manage-bookings-closed.php">Closed bookings</a><?php else: ?><a class="btn btn-outline" href="tutor-payment-qr.php">Manage DuitNow QR</a><?php endif; ?></nav>
