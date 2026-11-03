<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Confirm funds received - TutorMeQuran';
$pageDescription = 'Your acknowledgement will be shown to the student and admin.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main student-hub tutor-hub">
<header class="portal-heading"><div><span class="section-subtitle">Tutor studio</span><h1>Confirm funds received</h1><p>Check your bank account before confirming this student's payment.</p></div></header>
<section class="booking-progress" aria-label="Booking process" data-progress-booking="TMQ-1024" data-progress-status="receipt" data-progress-source="page"><ol class="booking-journey"><li class="done"><span class="booking-step" aria-label="Booking: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Booking</span></span></li><li class="done"><span class="booking-step" aria-label="Arrange: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Arrange</span></span></li><li class="done"><span class="booking-step" aria-label="Class: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Class</span></span></li><li class="current" aria-current="step"><span class="booking-step" aria-label="Payment: current stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li class=""><span class="booking-step" aria-label="Verified: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Payment - check the transfer before confirming receipt</p></section>
<section class="portal-card"><h2>PAY-1024 · RM 40.00</h2><p>Ahmad Hakimi · Booking TMQ-1024</p><form data-payment-confirm><label class="eligibility"><input type="checkbox" name="receiptChecked" required> I have checked my bank account and received RM 40.00 for booking TMQ-1024.</label><div class="portal-actions"><button type="submit" class="btn btn-primary">Confirm funds received</button><a class="btn btn-outline btn-sm" href="tutor-payment-view.php">Back to receipt</a></div><p data-workflow-error role="status"></p></form></section><p class="field-help">Your acknowledgement is separate from admin verification. Only admin can mark the payment Verified.</p>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
