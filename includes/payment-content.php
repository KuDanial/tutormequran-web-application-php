<?php if (!isset($authUser,$paymentMessage)) { http_response_code(404);exit; } ?>
<header class="portal-heading"><div><span class="section-subtitle">Your lesson</span><h1><?= $paymentListPage?'Lesson charges':($paymentUploadPage?'Submit payment evidence':'Payment and receipt history') ?></h1><p>Record genuine DuitNow transfers and their receipt evidence. Uploading a receipt does not verify payment.</p></div></header>
<?php if($paymentMessage): ?><p role="status"><?= tmq_escape($paymentMessage) ?></p><?php endif; ?>
<?php if($paymentListPage): ?>
<p>This is a lesson-charge summary, not a finalized paid invoice. Tutor acknowledges each transfer; Admin reviews evidence and separately verifies the exact total.</p>
<?php foreach($paymentBookings as $row): ?><section class="portal-card"><h2>Booking #<?= (int)$row['booking_id'] ?> &middot; <?= tmq_escape($row['tutor_name']) ?></h2><?= tmq_booking_badge($row['status']) ?><p><?= tmq_escape(tmq_booking_time($row['starts_at'])) ?> &middot; Agreed lesson amount RM <?= tmq_escape($row['expected_amount']) ?></p>
<?php if(in_array($row['status'],['completed','closed'],true)): ?><a class="btn btn-outline" href="student-payment-view.php?id=<?= (int)$row['booking_id'] ?>">View payment record</a><?php else: ?><p><?= in_array($row['status'],['cancelled','rejected'],true)?'No payment submission for this booking.':'Payment is not due before both lesson completion confirmations.' ?></p><?php endif; ?></section><?php endforeach; ?>
<?php if(!$paymentBookings): ?><p>No lesson charges to display.</p><?php endif; ?>
<?php elseif($paymentBooking): ?>
<?php $paymentWritable=tmq_payment_eligible($paymentBooking) && (!$payment || in_array($payment['status'],['pending','underpaid','rejected','reupload_required'],true)); ?>
<section class="portal-card"><h2>Booking #<?= (int)$paymentBooking['booking_id'] ?></h2><dl class="portal-details">
<?php foreach(['Tutor'=>$paymentBooking['tutor_name'],'Lesson start'=>tmq_booking_time($paymentBooking['starts_at']),'Lesson end'=>tmq_booking_time($paymentBooking['ends_at']),
    'Booking status'=>ucfirst($paymentBooking['status']),'Expected lesson amount'=>'RM '.$paymentBooking['expected_amount'],
    'Payment reference'=>$payment?'PAY-'.$payment['payment_id']:'Not opened','Payment status'=>$payment?ucfirst(str_replace('_',' ',$payment['status'])):'Not opened'] as $label=>$value): ?>
<div><dt><?= tmq_escape($label) ?></dt><dd><?= tmq_escape($value) ?></dd></div><?php endforeach; ?></dl>
<?php if(!empty($paymentTotals)): ?><p>Current valid total: RM <?= tmq_escape($paymentTotals['valid_total']) ?> &middot; Outstanding: RM <?= tmq_escape($paymentTotals['outstanding']) ?></p><p role="status"><?= tmq_escape($paymentTotals['message']) ?></p><?php endif; ?>
<?php if(!$paymentWritable): ?><p>This booking/payment is read-only for Student submissions.</p><?php endif; ?>
<?php if($paymentWritable && !$paymentUploadPage): ?><a class="btn btn-primary" href="student-payment-upload.php?id=<?= (int)$paymentBookingId ?>"><?= $payment?'Add a genuine transfer or correct a receipt':'Open payment' ?></a><?php endif; ?>
</section>
<?php if($paymentUploadPage && $paymentWritable && !$payment): ?>
<section class="portal-card"><h2>Open the payment record</h2><p>One payment record groups all genuine transfers for this booking. Opening it does not record money received.</p><form method="post"><?= tmq_csrf_field() ?><input type="hidden" name="payment_action" value="open"><button type="submit" class="btn btn-primary">Open payment</button></form></section>
<?php endif; ?>
<?php if($payment): ?>
<section class="portal-card"><h2>DuitNow recipient</h2><p><?= tmq_escape($payment['recipient_name_snapshot']) ?></p>
<?php if($payment['qr_path_snapshot']): ?><img src="student-payment-file.php?kind=qr&amp;id=<?= (int)$payment['payment_id'] ?>" alt="Tutor DuitNow QR saved for this payment" width="260" height="260"><p><a class="btn btn-outline" href="student-payment-file.php?kind=qr&amp;id=<?= (int)$payment['payment_id'] ?>" target="_blank" rel="noopener">Open saved QR</a></p><p>Check the bank recipient before sending money. Record the amount of each genuine transfer, not a cumulative total.</p>
<?php else: ?><p>No QR was available when this payment was opened. Confirm the recipient directly before making a transfer. You can submit evidence for a transfer you have already made.</p><?php endif; ?></section>
<?php if($paymentUploadPage && $paymentWritable): ?>
<section class="portal-card"><h2>I made another payment</h2><p>Use this only for money actually sent in a new transfer. For example, after an RM4 transfer, a genuine RM36 top-up is a separate transfer. To replace an existing receipt, use its correction action below.</p>
<?php $receiptAction='transfer';$receiptCurrent=null;require __DIR__.'/payment-receipt-form.php'; ?></section>
<?php endif; ?>
<section class="portal-card"><h2>Genuine transfer and receipt history</h2><p>Amounts below are Student claims, not verified funds. Only the current receipt describes each transfer; older versions never represent additional money. Only current Tutor Received + Admin Valid evidence contributes to the valid total.</p>
<?php if(!$paymentHistory): ?><p>No genuine transfers submitted.</p><?php endif; ?>
<?php foreach($paymentHistory as $receipt): ?>
<article class="portal-card"><h3>Transfer #<?= (int)$receipt['transfer_id'] ?> &middot; Receipt version <?= (int)$receipt['attempt_number'] ?> &middot; <?= $receipt['is_current']?'Current':'Historical' ?></h3><dl class="portal-details">
<?php foreach(['Declared transfer amount'=>'RM '.$receipt['amount_paid'],'Bank reference'=>$receipt['bank_reference']??'Not supplied','Bank transfer date'=>$receipt['transfer_date']??'Not supplied',
    'Uploaded on'=>tmq_booking_time($receipt['uploaded_at']),'Receipt filename'=>$receipt['original_filename'],'Student note'=>$receipt['payment_note']??'None',
    'Tutor review'=>ucfirst($receipt['tutor_status']),'Admin evidence review'=>ucfirst(str_replace('_',' ',$receipt['admin_status']))] as $label=>$value): ?><div><dt><?= tmq_escape($label) ?></dt><dd><?= nl2br(tmq_escape($value)) ?></dd></div><?php endforeach; ?></dl>
<a class="btn btn-outline" href="student-payment-file.php?id=<?= (int)$receipt['submission_id'] ?>">Download receipt version <?= (int)$receipt['attempt_number'] ?></a>
<?php if($receipt['duplicate_evidence'] || $receipt['duplicate_reference']): ?><p role="status">Duplicate evidence warning: <?= $receipt['duplicate_evidence']?'This file matches other receipt evidence.':'This bank reference matches another submitted transfer.' ?> A match does not prove whether bank transfers are distinct. Evidence is retained for review; no money is automatically verified.</p><?php endif; ?>
<?php if(!$receipt['is_current']): ?><p>Historical version retained unchanged. This amount is not additional money.</p>
<?php elseif(tmq_payment_correction_allowed($paymentBooking,$payment,$receipt,(int)$receipt['submission_id'])): ?>
<?php if($paymentUploadPage): ?><details><summary>Correct / reupload the receipt for this transfer</summary><p>This creates the next receipt version for the same transfer. Enter its correct claimed amount. Do not enter a top-up here.</p><?php $receiptAction='correct';$receiptCurrent=$receipt;require __DIR__.'/payment-receipt-form.php'; ?></details>
<?php else: ?><a class="btn btn-outline" href="student-payment-upload.php?id=<?= (int)$paymentBookingId ?>">Correct this transfer's receipt</a><?php endif; ?>
<?php else: ?><p>This receipt is locked for Student correction. Received evidence awaits Admin review; valid evidence remains unchanged. A genuine top-up must be a new transfer.</p><?php endif; ?>
</article><?php endforeach; ?></section>
<?php endif; ?>
<a class="booking-back-link" href="student-booking-view.php?id=<?= (int)$paymentBookingId ?>">Back to booking</a>
<?php endif; ?>
<a class="booking-back-link" href="student-invoices.php">All lesson charges</a>
