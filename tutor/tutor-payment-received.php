<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Payment acknowledged - TutorMeQuran';
$pageDescription = 'Sample state after the tutor confirms receipt.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main student-hub tutor-hub">
<header class="portal-heading"><div><span class="section-subtitle">Tutor studio</span><h1>Payment acknowledgement</h1><p>PAY-1024 · TMQ-1024</p></div></header>
<section class="booking-progress" aria-label="Booking process" data-progress-booking="TMQ-1024" data-progress-status="received" data-progress-source="page"><ol class="booking-journey"><li class="done"><span class="booking-step" aria-label="Booking: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Booking</span></span></li><li class="done"><span class="booking-step" aria-label="Arrange: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Arrange</span></span></li><li class="done"><span class="booking-step" aria-label="Class: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Class</span></span></li><li class="current" aria-current="step"><span class="booking-step" aria-label="Payment: current stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li class=""><span class="booking-step" aria-label="Verified: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Payment received - waiting for admin verification</p></section>
<section class="portal-card"><h2 data-payment-result-title>Confirm the transfer first</h2><p data-payment-result-text>Check the receipt and your bank account before recording that payment was received.</p><dl class="portal-details"><div><dt>Amount</dt><dd>RM 40.00</dd></div><div><dt>Tutor confirmation</dt><dd data-payment-confirmation>Awaiting confirmation</dd></div><div><dt>Admin verification</dt><dd>Pending</dd></div></dl><div class="portal-actions"><a class="btn btn-primary btn-sm" href="tutor-payments.php#received">Received payments</a><a class="btn btn-outline btn-sm" href="tutor-payment-view.php">View receipt</a></div></section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
