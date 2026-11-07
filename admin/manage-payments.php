<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/payment-review-page.php';
$assetPrefix = '../';
$pageTitle = 'Payment records - TutorMeQuran';
$pageDescription = 'Payment evidence, review and settlement history.';
$currentPage = 'manage-payments.php';
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
<?php require __DIR__ . '/../includes/payment-review-content.php'; ?>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div></body></html>
