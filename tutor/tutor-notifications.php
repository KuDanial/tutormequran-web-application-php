<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Notifications - TutorMeQuran';
$pageDescription = 'Coordinate the lesson with your student, then confirm completion before payment.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main student-hub tutor-hub">
<header class="portal-heading"><div><span class="section-subtitle">Tutor studio</span><h1>Notifications</h1><p>Updates about your bookings and payments.</p></div></header>
<section class="portal-card"><h2>A student requested a lesson</h2><p>Ahmad Hakimi · TMQ-1024 · 20 Oct, 8–9 PM</p><div class="portal-actions"><a class="btn btn-primary btn-sm" href="tutor-booking-view.php">Review booking</a></div></section><section class="portal-card"><h2>Payment updates</h2><p>Open Payments to review student receipts and check the admin verification status.</p><div class="portal-actions"><a class="btn btn-outline btn-sm" href="tutor-payments.php">View payments</a></div></section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
