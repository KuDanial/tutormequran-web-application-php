<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Your receipt needs correction - TutorMeQuran';
$pageDescription = 'PAY-1024 · sample rejected receipt.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main booking-simple">
<header class="portal-heading">
<div><span class="section-subtitle">Your learning journey</span>
<h1>Your receipt needs correction</h1>
<p>PAY-1024 · sample rejected receipt.</p>
</div>
<div class="portal-actions"><a href="student-payment-upload.php" class="btn btn-primary btn-sm">Replace receipt</a><a href="student-booking-view.php" class="btn btn-outline btn-sm">Back to booking</a>
</div>
</header><section class="booking-progress" aria-label="Booking process" data-progress-booking="TMQ-1024" data-progress-status="issue" data-progress-source="page"><ol class="booking-journey"><li class="done"><span class="booking-step" aria-label="Booking: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Booking</span></span></li><li class="done"><span class="booking-step" aria-label="Arrange: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Arrange</span></span></li><li class="done"><span class="booking-step" aria-label="Class: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Class</span></span></li><li class="current" aria-current="step"><span class="booking-step" aria-label="Payment: current stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li class=""><span class="booking-step" aria-label="Verified: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Payment rejected - check the reason and submit a corrected receipt</p></section><div class="booking-quick-summary"><span><strong>TMQ-1024</strong></span><span>20 Oct · 8–9 PM</span><span>RM 40.00</span></div>
<aside class="flow-notice danger"><strong>Receipt rejected</strong>
<p>Admin remark: The uploaded receipt is unreadable. Replace it with a clear copy of the original transaction. Do not transfer money again.</p>
</aside>
<section class="portal-card ">
<h2>Payment review</h2>
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
<dt>Status</dt>
<dd><span class="portal-badge bad">Rejected</span>
</dd>
</div>
<div>
<dt>Next Action</dt>
<dd>Replace the receipt for this payment.</dd>
</div>
</dl></details>
</section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
