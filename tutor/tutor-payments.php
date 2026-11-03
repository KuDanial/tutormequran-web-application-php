<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Payment confirmations - TutorMeQuran';
$pageDescription = 'Review receipts and acknowledge payments received for your lessons.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main student-hub tutor-hub">
<header class="portal-heading"><div><span class="section-subtitle">Tutor studio</span><h1>Payments</h1><p>Manage your DuitNow QR and confirm student payments.</p></div><div class="portal-actions"><a class="btn btn-outline btn-sm" href="tutor-payment-qr.php">Manage DuitNow QR</a></div></header>
<nav class="student-tabs" aria-label="Payment views" data-student-tabs><a href="#pending" aria-current="page">Pending confirmation</a><a href="#received">Received</a></nav><section id="pending" data-student-panel><article class="portal-card" data-payment-pending-row><h2>Ahmad Hakimi · RM 40.00</h2><p>PAY-1024 · TMQ-1024</p><p data-receipt-status>Awaiting the student's receipt.</p><div class="portal-actions"><a class="btn btn-primary btn-sm" href="tutor-payment-view.php">View payment</a></div></article><p data-payment-pending-empty hidden>No payments are awaiting your confirmation.</p></section><section id="received" data-student-panel><article class="portal-card" data-payment-received-row hidden><span class="portal-badge pending">Admin review pending</span><h2>PAY-1024 · RM 40.00</h2><p>Ahmad Hakimi · TMQ-1024</p><div class="portal-actions"><a class="btn btn-primary btn-sm" href="tutor-payment-received.php">View acknowledgement</a></div></article><p data-payment-received-empty>No new payment acknowledgements yet.</p></section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
