<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Payment verified - TutorMeQuran';
$pageDescription = 'TMQ-1024 · class completed, payment verified and booking closed.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main booking-simple">
<header class="portal-heading">
<div><span class="section-subtitle">Your learning journey</span>
<h1>Verified &amp; closed</h1>
<p>TMQ-1024 · class completed, payment verified and booking closed.</p>
</div><a href="student-booking-view.php" class="btn btn-outline btn-sm">Back to booking</a>
</header>
<section class="booking-progress" aria-label="Booking process" data-progress-booking="TMQ-1024" data-progress-status="verified" data-progress-source="page"><ol class="booking-journey"><li class="done"><span class="booking-step" aria-label="Booking: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Booking</span></span></li><li class="done"><span class="booking-step" aria-label="Arrange: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Arrange</span></span></li><li class="done"><span class="booking-step" aria-label="Class: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Class</span></span></li><li class="done"><span class="booking-step" aria-label="Payment: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Payment</span></span></li><li class="done"><span class="booking-step" aria-label="Verified: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Payment verified - booking closed</p></section><div class="booking-quick-summary"><span><strong>TMQ-1024</strong></span><span>20 Oct · 8–9 PM</span><span>RM 40.00</span></div>
<section class="portal-card ">
<h2>Your booking is complete</h2><dl class="portal-details"><div><dt>Class status</dt><dd>Completed · confirmed by both participants</dd></div><div><dt>Booking status</dt><dd>Closed</dd></div><div><dt>Admin verified at</dt><dd>21 October 2026 · 10:00 AM MYT</dd></div></dl>
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
<dt>Tutor Confirmation</dt>
<dd><span class="portal-badge good">Received</span>
</dd>
</div>
<div>
<dt>Admin Status</dt>
<dd><span class="portal-badge good">Verified</span>
</dd>
</div>
<div>
<dt>Admin Remark</dt>
<dd>Receipt amount and booking reference matched.</dd>
</div>
</dl></details>
</section><div class="portal-actions"><a href="student-feedback-addNew.php" class="btn btn-primary btn-sm">Leave lesson feedback</a><a href="student-bookings.php" class="btn btn-outline btn-sm">All bookings</a></div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
