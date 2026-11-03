<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Class completed - TutorMeQuran';
$pageDescription = 'Both participants have confirmed the session. You can now pay your tutor.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main student-hub"><header class="portal-heading"><div><h1>Class completion</h1><p>TMQ-1024 · Quran recitation &amp; Tajweed</p></div></header>
<section class="booking-progress" aria-label="Booking process" data-progress-booking="TMQ-1024" data-progress-status="completed" data-progress-source="page"><ol class="booking-journey"><li class="done"><span class="booking-step" aria-label="Booking: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Booking</span></span></li><li class="done"><span class="booking-step" aria-label="Arrange: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Arrange</span></span></li><li class="done"><span class="booking-step" aria-label="Class: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Class</span></span></li><li class="current" aria-current="step"><span class="booking-step" aria-label="Payment: current stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li class=""><span class="booking-step" aria-label="Verified: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Class completion - both participants must confirm before payment</p></section><section class="portal-card"><h2>Completion confirmations</h2><p data-student-completion></p><p data-tutor-completion></p><p>Payment becomes available after both participants confirm the lesson.</p><div class="portal-actions"><a href="student-payment-upload.php" class="btn btn-primary">Continue to payment</a><a href="student-booking-coordinate.php" class="btn btn-outline">Open conversation</a></div></section></main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
