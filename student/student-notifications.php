<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Notifications - TutorMeQuran';
$pageDescription = 'Notifications for your TutorMeQuran account.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main student-hub">
<header class="portal-heading"><div><span class="section-subtitle">Your learning journey</span><h1>Notifications</h1><p>Updates about your lessons and payments.</p></div></header><section class="portal-card student-list" aria-label="Recent updates">
<article class="student-list-item"><div><h2>Your booking is reserved</h2><p>TMQ-1024 · 20 Oct, 8–9 PM · Ustaz Muhammad Farhan</p></div><div class="student-list-action"><span class="portal-badge">Booking</span><a class="btn btn-outline btn-sm" href="student-booking-view.php">View booking</a></div></article>
<article class="student-list-item"><div><h2>Your payment was verified</h2><p>TMQ-1008 · RM 40.00 · 7 Oct 2026</p></div><div class="student-list-action"><span class="portal-badge">Payment</span><a class="btn btn-outline btn-sm" href="student-invoices.php#receipts">View receipts</a></div></article>
</section><p class="field-help">Sample updates. Live notifications will appear here when the account system is connected.</p>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
