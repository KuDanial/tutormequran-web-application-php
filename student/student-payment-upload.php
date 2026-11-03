<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Pay your tutor with DuitNow - TutorMeQuran';
$pageDescription = 'Class completed · TMQ-1024 · pay RM 40.00 and upload your receipt.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main booking-simple">
<header class="portal-heading">
<div><span class="section-subtitle">Your learning journey</span>
<h1>Pay your tutor with DuitNow</h1>
<p>Class completed · TMQ-1024 · pay RM 40.00 and upload your receipt.</p>
</div>
</header>
<section class="booking-progress" aria-label="Booking process" data-progress-booking="TMQ-1024" data-progress-status="completed" data-progress-source="page"><ol class="booking-journey"><li class="done"><span class="booking-step" aria-label="Booking: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Booking</span></span></li><li class="done"><span class="booking-step" aria-label="Arrange: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Arrange</span></span></li><li class="done"><span class="booking-step" aria-label="Class: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Class</span></span></li><li class="current" aria-current="step"><span class="booking-step" aria-label="Payment: current stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li class=""><span class="booking-step" aria-label="Verified: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Payment - transfer with DuitNow, then upload your receipt</p></section><div class="booking-quick-summary"><span><strong>TMQ-1024</strong></span><span>20 Oct · 8–9 PM</span><span>RM 40.00</span></div>
<div class="portal-columns booking-task-layout">

<section class="portal-card ">
<h2>Pay your tutor</h2>
<div class="duitnow-payment" data-duitnow-student>
<div class="duitnow-qr-panel">
<img data-duitnow-image hidden alt="Tutor's uploaded DuitNow payment QR" width="260" height="260">
<p data-duitnow-empty>The tutor has not added a DuitNow QR yet.</p>
<div class="portal-actions" data-duitnow-actions hidden>
<a data-duitnow-download class="btn btn-primary btn-sm" download="tutor-TU-001-duitnow-qr.png" aria-label="Download tutor DuitNow QR to your phone"><i class="fas fa-download" aria-hidden="true"></i> Download DuitNow QR</a>
<a data-duitnow-open class="btn btn-outline btn-sm" target="_blank" rel="noopener">Open QR</a>
</div>
</div>
<div><p class="duitnow-amount">RM 40.00</p><p><strong>Muhammad Farhan</strong><br>Booking reference: TMQ-1024</p>
<ol class="booking-checklist duitnow-instructions">
<li>Save the tutor's QR to your phone, or scan it from another screen.</li>
<li>Open your banking app's QR payment feature. If it supports importing an image, select the saved QR.</li>
<li>Check the recipient shown by your bank, enter RM 40.00 if needed, and pay.</li>
<li>Save the successful transaction receipt from your banking app. Upload that receipt below.</li>
</ol>
<p class="field-help" data-duitnow-missing>Ask your tutor to add their payment QR before paying. <a href="student-booking-coordinate.php">Message tutor</a></p>
</div></div>
</section>
</div>
<section class="portal-card ">
<h2>After paying, add your receipt</h2><p class="field-help">Upload the bank's successful transaction receipt, not the tutor's QR image.</p>
<aside class="flow-notice info"><strong>One payment per booking</strong>
<p>A corrected receipt replaces the existing submission for PAY-1024. Tutor acknowledgement and admin review restart for a replacement.</p>
</aside>
<form class="portal-form" data-student-receipt>

<div class="portal-fields">
<div class="form-group">
<label for="f-payDate-display" class="form-label">Payment Date (automatically detected)</label>
<input id="f-payDate-display" data-payment-date-display class="form-control" type="date" value="2026-10-29" disabled aria-describedby="payment-date-help">
<input type="hidden" name="payDate" data-payment-date-value value="2026-10-29"><small id="payment-date-help">Detected automatically and cannot be changed.</small>
</div>
<div class="form-group">
<label for="f-payAmount" class="form-label">Amount Paid (RM)</label>
<input id="f-payAmount" name="payAmount" required class="form-control" type="number" value="40.00" min="1" step="0.01">
</div>
<div class="form-group">
<label for="f-payReceipt" class="form-label">DuitNow Receipt</label>
<input id="f-payReceipt" name="payReceipt" required class="form-control" type="file" accept=".pdf,.png,.jpg,.jpeg"><small>One PDF, JPG or PNG document, up to 2 MB.</small>
</div>
<div class="form-group">
<label for="f-paymentNote" class="form-label">Payment Note</label>
<textarea id="f-paymentNote" name="paymentNote" class="form-control" rows="4">TMQ-1024</textarea>
</div>
</div>
<div class="portal-actions">
<button type="submit" class="btn btn-primary btn-sm">Submit receipt</button><a href="student-booking-completed.php" class="btn btn-outline btn-sm">Back</a>
</div>
<p data-workflow-error role="status"></p></form>
</section>
<details class="booking-disclosure"><summary>1. Check your lesson</summary><div class="booking-disclosure-body">
<dl class="portal-details">
<div>
<dt>Student</dt>
<dd>Ahmad Hakimi · ST-001</dd>
</div>
<div>
<dt>Tutor</dt>
<dd>Muhammad Farhan · TU-001</dd>
</div>
<div>
<dt>Appointment Date</dt>
<dd>20 October 2026</dd>
</div>
<div>
<dt>Start Time</dt>
<dd>8:00 PM (MYT)</dd>
</div>
<div>
<dt>End Time</dt>
<dd>9:00 PM (MYT)</dd>
</div>
<div>
<dt>Class Type</dt>
<dd>Online</dd>
</div>
<div>
<dt>Booking Status</dt>
<dd><span class="portal-badge good">Class completed</span>
</dd>
</div>
<div>
<dt>Amount Due</dt>
<dd>RM 40.00</dd>
</div>
</dl>
</div></details>
<p class="field-help">For an underpayment, keep the original receipt and provide evidence for the corrected total as instructed by admin. A reupload is not a new booking or proof of additional money by itself.</p>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/duitnow-qr.js"></script>
<script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
