<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Session outcome - TutorMeQuran';
$pageDescription = 'Coordinate the lesson with your student, then confirm completion before payment.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main student-hub tutor-hub">
<header class="portal-heading"><div><h1>Class completion</h1><p>TMQ-1024 · Quran recitation & Tajweed</p></div></header>
<section class="booking-progress" aria-label="Booking process" data-progress-booking="TMQ-1024" data-progress-status="completed" data-progress-source="page"><ol class="booking-journey"><li class="done"><span class="booking-step" aria-label="Booking: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Booking</span></span></li><li class="done"><span class="booking-step" aria-label="Arrange: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Arrange</span></span></li><li class="done"><span class="booking-step" aria-label="Class: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Class</span></span></li><li class="current" aria-current="step"><span class="booking-step" aria-label="Payment: current stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li class=""><span class="booking-step" aria-label="Verified: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Class completion - both participants must confirm before payment</p></section>
<section class="portal-card"><h2>Your completion confirmation</h2><p data-tutor-completion>Your confirmation has not been recorded yet.</p><p>Payment follows completion of the class. Once the student pays and uploads a receipt, check your bank account before confirming receipt of funds.</p><div class="portal-actions"><a class="btn btn-primary btn-sm" href="tutor-payment-qr.php">Manage payment QR</a><a class="btn btn-outline btn-sm" href="tutor-payment-view.php">View payment</a><a class="btn btn-outline btn-sm" href="tutor-booking-session.php">Back to session</a></div></section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
