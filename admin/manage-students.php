<?php
require_once __DIR__ . '/../includes/config.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') { http_response_code(405); exit('This page is read-only.'); }
$assetPrefix = '../';
$pageTitle = 'Student accounts - TutorMeQuran';
$pageDescription = 'Admin Student account management is not available. Students manage their own profiles.';
$currentPage = 'manage-students.php';
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
<header class="portal-heading"><div><span class="section-subtitle">Operations workspace</span><h1>Student accounts</h1></div></header>
<section class="portal-card"><p>Admin Student account management is not available. Students manage their own profiles.</p><a class="btn btn-outline btn-sm" href="admin-settings.php">Profile field permissions</a></section>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
