<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Platform overview - TutorMeQuran';
$pageDescription = 'Monday, 12 October 2026 · Your review queue comes first.';
$currentPage = 'admin-dashboard.php';
$adminPrefix = '';
$workspaceLabel = 'Administration';
$showAdminSettings = true;
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page admin-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<div class="admin-layout">
<?php require __DIR__ . '/../includes/admin-sidebar.php'; ?>
<div class="admin-body">
<?php require __DIR__ . '/../includes/admin-header.php'; ?>
<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Operations workspace</span>
<h1>Platform overview</h1>
<p>Monday, 12 October 2026 · Your review queue comes first.</p>
</div><a href="manage-bookings.php" class="btn btn-primary btn-sm">View bookings</a>
</header>
<div class="portal-stats">
<article class="portal-card portal-stat admin-dashboard-card"><span class="dashboard-card-icon"><i class="fas fa-user-graduate" aria-hidden="true"></i></span><p>Total Students</p><strong>120</strong><span>Registered learners</span>
</article>
<article class="portal-card portal-stat admin-dashboard-card"><span class="dashboard-card-icon"><i class="fas fa-chalkboard-user" aria-hidden="true"></i></span><p>Total Tutors</p><strong>12</strong><span>9 verified · 3 awaiting review</span>
</article>
<article class="portal-card portal-stat admin-dashboard-card"><span class="dashboard-card-icon"><i class="fas fa-certificate" aria-hidden="true"></i></span><p>Pending Certificates</p><strong>3</strong><span>Applications to review</span>
</article>
<article class="portal-card portal-stat admin-dashboard-card"><span class="dashboard-card-icon"><i class="fas fa-receipt" aria-hidden="true"></i></span><p>Pending Payments</p><strong>12</strong><span>Receipts awaiting a decision</span>
</article>
<article class="portal-card portal-stat admin-dashboard-card"><span class="dashboard-card-icon"><i class="fas fa-calendar-days" aria-hidden="true"></i></span><p>Total Bookings</p><strong>60</strong><span>October 2026</span>
</article>
</div>
<div class="portal-columns">
<section class="portal-card admin-dashboard-card ">
<h2><span class="dashboard-card-icon"><i class="fas fa-user-check" aria-hidden="true"></i></span>Tutor applications</h2>
<aside class="flow-notice warm"><strong>3 applications awaiting review</strong>
<p>Verify qualifications before tutors can set up a public profile.</p>
</aside>
<div class="review-queue-item"><span class="queue-initials">NH</span><div><strong>Nur Hana</strong><p>CERT-002 · 12 Oct 2026</p><span class="portal-badge pending">Pending certificate</span></div><a href="manage-certificates-view.php" class="btn btn-primary btn-sm">Review</a></div>
</section>
<section class="portal-card admin-dashboard-card ">
<h2><span class="dashboard-card-icon"><i class="fas fa-money-check-dollar" aria-hidden="true"></i></span>Payment reviews</h2>
<aside class="flow-notice info"><strong>12 payments awaiting verification</strong>
<p>Check the receipt, booking amount and tutor acknowledgement.</p>
</aside>
<div class="review-queue-item"><span class="queue-initials">AH</span><div><strong>TMQ-1024 · RM 40.00</strong><p>Ahmad Hakimi</p><span class="portal-badge pending">Awaiting tutor</span></div><a href="manage-payments-view.php" class="btn btn-primary btn-sm">Review</a></div>
</section>
</div>
<section class="portal-card admin-dashboard-card ">
<h2><span class="dashboard-card-icon"><i class="fas fa-layer-group" aria-hidden="true"></i></span>Manage the platform</h2>
<div class="portal-actions"><a href="manage-tutors.php" class="btn btn-outline btn-sm">Manage Tutors</a><a href="manage-students.php" class="btn btn-outline btn-sm">Manage Students</a><a href="manage-certificates.php" class="btn btn-outline btn-sm">Verify Certificates</a><a href="manage-payments.php" class="btn btn-outline btn-sm">Verify Payments</a><a href="../report/report-dashboard.php" class="btn btn-outline btn-sm">View Report</a>
</div>
</section><section class="portal-card admin-dashboard-card"><h2><span class="dashboard-card-icon"><i class="fas fa-user-shield" aria-hidden="true"></i></span>Profile permissions</h2><a class="btn btn-outline btn-sm" href="admin-settings.php">Manage Editable Fields</a></section>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
