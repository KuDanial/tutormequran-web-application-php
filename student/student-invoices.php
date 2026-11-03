<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Invoice - TutorMeQuran';
$pageDescription = 'Invoice for your TutorMeQuran account.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main student-hub">
<header class="portal-heading"><div><span class="section-subtitle">Your learning journey</span><h1>Invoice</h1><p>Your lesson fees and DuitNow payment records.</p></div></header><nav class="student-tabs" aria-label="View options" data-student-tabs><a href="#pending" aria-current="page">Pending</a><a href="#receipts">Receipts</a></nav>
<section id="pending" data-student-panel aria-labelledby="pending-title"><h2 id="pending-title" class="student-panel-title">Pending</h2>
<section class="portal-card"><article class="student-list-item"><div><h2>Quran recitation & Tajweed</h2><p>TMQ-1024 · Ustaz Muhammad Farhan · 20 Oct 2026</p></div><div class="student-list-action"><span class="portal-badge">RM 40.00</span><a class="btn btn-primary btn-sm" href="student-booking-view.php">View booking</a></div></article>
<p class="field-help">Payment is not due yet. Complete the class first, then open the payment step to pay and upload your receipt.</p></section>
</section>
<section id="receipts" data-student-panel aria-labelledby="receipts-title"><h2 id="receipts-title" class="student-panel-title">Receipts</h2>
<details class="student-record"><summary><span>TMQ-1008<small>6 Oct 2026 · RM 40.00</small></span><span class="portal-badge good">Verified</span></summary><div><dl class="portal-details"><div><dt>Tutor</dt><dd>Ustaz Muhammad Farhan</dd></div><div><dt>Payment method</dt><dd>DuitNow</dd></div><div><dt>Amount</dt><dd>RM 40.00</dd></div><div><dt>Tutor confirmation</dt><dd>Received</dd></div><div><dt>Admin decision</dt><dd>Verified</dd></div><div><dt>Payment date</dt><dd>6 October 2026</dd></div></dl><p class="field-help">Sample payment record. The original bank receipt will be available once receipt storage is connected.</p></div></details><details class="student-record"><summary><span>TMQ-0992<small>29 Sep 2026 · RM 40.00</small></span><span class="portal-badge good">Verified</span></summary><div><dl class="portal-details"><div><dt>Tutor</dt><dd>Ustaz Muhammad Farhan</dd></div><div><dt>Payment method</dt><dd>DuitNow</dd></div><div><dt>Amount</dt><dd>RM 40.00</dd></div><div><dt>Tutor confirmation</dt><dd>Received</dd></div><div><dt>Admin decision</dt><dd>Verified</dd></div><div><dt>Payment date</dt><dd>29 September 2026</dd></div></dl><p class="field-help">Sample payment record. The original bank receipt will be available once receipt storage is connected.</p></div></details>
</section><p class="field-help">Invoice groups your lesson charges and payment records. Verification follows tutor acknowledgement and admin review.</p>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
