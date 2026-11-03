<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Booking decision - TutorMeQuran';
$pageDescription = 'Coordinate the lesson with your student, then confirm completion before payment.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main student-hub tutor-hub">
<header class="portal-heading"><div><span class="section-subtitle">Tutor studio</span><h1>Booking decision</h1><p>TMQ-1024 · Ahmad Hakimi</p></div></header>
<section class="booking-progress" aria-label="Booking process" data-progress-booking="TMQ-1024" data-progress-status="rejected" data-progress-source="page"><ol class="booking-journey"><li class="stopped"><span class="booking-step" aria-label="Booking: stopped"><span class="booking-step-dot" aria-hidden="true">!</span><span class="booking-step-label">Booking</span></span></li><li class=""><span class="booking-step" aria-label="Arrange: stopped"><span class="booking-step-dot" aria-hidden="true">2</span><span class="booking-step-label">Arrange</span></span></li><li class=""><span class="booking-step" aria-label="Class: stopped"><span class="booking-step-dot" aria-hidden="true">3</span><span class="booking-step-label">Class</span></span></li><li class=""><span class="booking-step" aria-label="Payment: stopped"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li class=""><span class="booking-step" aria-label="Verified: stopped"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Booking rejected - no payment due</p></section>
<section class="portal-card" data-rejection-result><h2 data-rejection-title>Review this booking</h2><p data-booking-reason>Open the booking to accept or reject the request.</p><div class="portal-actions"><a class="btn btn-primary btn-sm" href="tutor-booking-view.php">View booking</a><a class="btn btn-outline btn-sm" href="tutor-bookings.php">All bookings</a></div></section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
