<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Platform overview - TutorMeQuran';
$pageDescription = 'Your review queues and platform records.';
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
<p>Your review queues and platform records.</p>
</div><a href="manage-bookings.php" class="btn btn-primary btn-sm">View bookings</a>
</header>
<div class="portal-columns">
<section class="portal-card admin-dashboard-card ">
<h2><span class="dashboard-card-icon"><i class="fas fa-user-check" aria-hidden="true"></i></span>Tutor applications</h2>
<p>Review submitted qualifications and their current status.</p>
<a href="manage-certificates.php" class="btn btn-primary btn-sm">Review certificates</a>
</section>
<section class="portal-card admin-dashboard-card ">
<h2><span class="dashboard-card-icon"><i class="fas fa-money-check-dollar" aria-hidden="true"></i></span>Payment reviews</h2>
<p>Check current receipt evidence, booking amounts and tutor acknowledgements.</p>
<a href="manage-payments.php" class="btn btn-primary btn-sm">Review payments</a>
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
