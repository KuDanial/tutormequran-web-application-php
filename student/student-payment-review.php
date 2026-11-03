<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Waiting for admin verification - TutorMeQuran';
$pageDescription = 'Your tutor has confirmed the payment. Admin will review the receipt and close the booking.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main booking-simple">
<header class="portal-heading"><div><span class="section-subtitle">Your lesson</span><h1>Waiting for admin verification</h1><p>Your tutor has confirmed the payment. Admin will review the receipt and close the booking.</p></div></header>

<section class="booking-progress" aria-label="Booking process" data-progress-booking="TMQ-1024" data-progress-status="receipt" data-progress-source="page"><ol class="booking-journey"><li class="done"><span class="booking-step" aria-label="Booking: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Booking</span></span></li><li class="done"><span class="booking-step" aria-label="Arrange: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Arrange</span></span></li><li class="done"><span class="booking-step" aria-label="Class: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Class</span></span></li><li class="current" aria-current="step"><span class="booking-step" aria-label="Payment: current stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li class=""><span class="booking-step" aria-label="Verified: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Payment - awaiting review</p></section><div class="booking-quick-summary"><span><strong>TMQ-1024</strong></span><span>20 Oct · 8–9 PM</span><span>RM 40.00</span></div>
<aside class="flow-notice info"><strong>Tutor confirmed · admin review pending</strong><p>No further transfer is needed. Admin checks the completed session, receipt amount, booking reference and tutor acknowledgement.</p></aside><section class="portal-card"><h2>Review record</h2><dl class="portal-details"><div><dt>Session ended</dt><dd>20 October 2026 · 9:00 PM MYT</dd></div><div><dt>Tutor confirmed completion</dt><dd>20 October 2026 · 9:02 PM MYT</dd></div><div><dt>Student confirmed completion</dt><dd>20 October 2026 · 9:05 PM MYT</dd></div><div><dt>Payment due</dt><dd>RM 40.00</dd></div></dl><details class="booking-record"><summary>View booking details</summary><dl class="portal-details"><div><dt>Payment</dt><dd>PAY-1024 · RM 40.00</dd></div><div><dt>Receipt submitted</dt><dd>20 October 2026 · 9:15 PM MYT</dd></div><div><dt>Tutor received funds</dt><dd>20 October 2026 · 9:20 PM MYT</dd></div><div><dt>Admin decision</dt><dd>Pending</dd></div><div><dt>Booking</dt><dd>Class completed · awaiting closure</dd></div></dl></details><div class="portal-actions"><a href="student-payment-verified.php" class="btn btn-primary btn-sm">View verification status</a><a href="student-payment-view.php" class="btn btn-outline btn-sm">View submitted receipt</a></div></section>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
