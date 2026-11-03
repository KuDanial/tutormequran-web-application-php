<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Verify acknowledged payment - TutorMeQuran';
$pageDescription = 'Sample state · tutor has confirmed receipt of RM 40.00.';
$currentPage = '';
$adminPrefix = '';
$workspaceLabel = 'Administration';
$showAdminSettings = true;
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page admin-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<div class="admin-layout">
<?php require __DIR__ . '/../includes/admin-sidebar.php'; ?>
<div class="admin-body">
<?php require __DIR__ . '/../includes/admin-header.php'; ?>
<main id="main-content" class="container portal-main booking-simple">
<header class="portal-heading">
<div><span class="section-subtitle">Operations workspace</span>
<h1>Verify acknowledged payment</h1>
<p>Sample state · tutor has confirmed receipt of RM 40.00.</p>
</div>
</header>
<nav class="booking-progress" aria-label="Booking progress"><ol class="booking-journey"><li class="done"><a href="../student/student-booking-view.php" aria-label="Booked: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Booked</span></a></li><li class="done"><a href="../student/student-booking-coordinate.php" aria-label="Arrange: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Arrange</span></a></li><li class="done"><a href="../student/student-booking-session.php" aria-label="Class: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Class</span></a></li><li class="current" aria-current="step"><a href="../student/student-payment-review.php" aria-label="Payment: current stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></a></li><li class=""><a href="../student/student-payment-verified.php" aria-label="Done: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Done</span></a></li></ol><p class="booking-current-label">Admin reviews payment</p></nav><div class="booking-quick-summary"><span><strong>TMQ-1024</strong></span><span>20 Oct · 8–9 PM</span><span>RM 40.00</span></div>
<div class="portal-columns booking-task-layout">
<details class="booking-disclosure"><summary>DuitNow receipt</summary><div class="booking-disclosure-body">
<figure class="receipt-paper">
<figcaption>DUITNOW TRANSFER <span>Sample receipt · not a real transaction</span>
</figcaption><strong class="receipt-amount">RM 40.00</strong>
<p>Transfer successful</p>
<details class="booking-record"><summary>View booking details</summary><dl class="portal-details">
<div>
<dt>Recipient</dt>
<dd>Muhammad Farhan</dd>
</div>
<div>
<dt>From</dt>
<dd>Ahmad Hakimi</dd>
</div>
<div>
<dt>Reference</dt>
<dd>TMQ-1024</dd>
</div>
<div>
<dt>Transaction Date</dt>
<dd>20 Oct 2026, 9:15 PM</dd>
</div>
<div>
<dt>Receipt File</dt>
<dd>duitnow-TMQ-1024.jpg</dd>
</div>
</dl></details>
</figure>
</div></details>
<details class="booking-disclosure"><summary>Verification evidence</summary><div class="booking-disclosure-body"><dl class="portal-details"><div><dt>Class completed</dt><dd>20 October 2026 · confirmed by both participants</dd></div><div><dt>Closure</dt><dd>After admin verifies this payment</dd></div></dl>
<details class="booking-record"><summary>View booking details</summary><dl class="portal-details">
<div>
<dt>Payment ID</dt>
<dd>PAY-1024</dd>
</div>
<div>
<dt>Booking ID</dt>
<dd>TMQ-1024</dd>
</div>
<div>
<dt>Payment Amount</dt>
<dd>RM 40.00</dd>
</div>
<div>
<dt>Payment Date</dt>
<dd>20 October 2026</dd>
</div>
<div>
<dt>Receipt</dt>
<dd>duitnow-TMQ-1024.jpg</dd>
</div>
<div>
<dt>Tutor Confirmation</dt>
<dd><span class="portal-badge good">Received</span>
</dd>
</div>
<div>
<dt>Acknowledged At</dt>
<dd>20 Oct 2026, 9:20 PM</dd>
</div>
<div>
<dt>Admin Status</dt>
<dd><span class="portal-badge pending">Pending</span>
</dd>
</div>
</dl></details>
</div></details>
</div>
<section class="portal-card ">
<h2>Final review</h2>
<form class="portal-form">
<button type="submit" hidden disabled aria-hidden="true">
</button>
<div class="portal-fields">
<div class="form-group">
<label for="f-paymentAdminRemark" class="form-label">Admin Remark</label>
<textarea id="f-paymentAdminRemark" name="paymentAdminRemark" class="form-control" rows="4">Receipt reference and amount match the booking. Tutor confirms receipt.</textarea>
</div>
</div>
<div class="portal-actions">
<a href="manage-bookings-closed.php" class="btn btn-primary btn-sm">Verify payment &amp; close booking</a><a href="manage-payments.php" class="btn btn-outline btn-sm">Back</a>
</div>
</form>
</section>
<div class="portal-actions">
<button type="button" class="btn btn-danger action-danger" data-confirm="Reject Payment? Check the details before confirming this action."><span class="action-warning-icon" aria-hidden="true">⚠</span> Reject Payment</button>
</div>
<aside class="flow-notice info"><strong>Final verification</strong>
<p>Approved receipts become Verified and the completed booking is Closed. Rejected receipts remain attached to the same payment record and can be replaced by the student.</p>
</aside><details class="booking-disclosure"><summary>Amount reconciliation</summary><div class="booking-disclosure-body"><dl class="portal-details"><div><dt>Expected Amount</dt><dd>RM 40.00</dd></div><div><dt>Paid Amount Shown on Receipt</dt><dd>RM 40.00</dd></div><div><dt>Outstanding Amount</dt><dd>RM 0.00</dd></div></dl><p class="field-help">If the paid amount is lower than the expected fee, Admin can mark the payment as underpaid and request the student to upload a correct receipt.</p><div class="portal-actions"><button type="button" class="btn btn-secondary">Mark as Underpaid</button><button type="button" class="btn btn-outline">Request Reupload</button></div></div></details><details class="booking-disclosure"><summary>Underpaid example · separate scenario</summary><div class="booking-disclosure-body"><dl class="portal-details"><div><dt>Expected</dt><dd>RM 40.00</dd></div><div><dt>Paid</dt><dd>RM 4.00</dd></div><div><dt>Outstanding</dt><dd>RM 36.00</dd></div><div><dt>Status</dt><dd><span class="portal-badge pending">Underpaid</span></dd></div></dl><p class="field-help">Check the actual transfer before requesting the remaining RM36. A clearer receipt alone does not settle an unpaid balance.</p></div></details><details class="booking-disclosure"><summary>No payment received</summary><div class="booking-disclosure-body"><p class="field-help">Keep the booking payment unverified. No receipt or transfer means unpaid; an invalid or false receipt can be rejected with a reason. Request payment or correct evidence before approval.</p></div></details>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
