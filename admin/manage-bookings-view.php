<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/booking-page.php';
$assetPrefix = '../';
$pageTitle = 'Booking management - TutorMeQuran';
$pageDescription = 'View your bookings and tutor decisions.';
$currentPage = '';
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
<?php require __DIR__ . '/../includes/booking-detail-content.php'; ?>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
