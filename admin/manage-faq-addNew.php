<?php
require_once __DIR__ . '/../includes/config.php';
$faqMode = 'add';
require __DIR__ . '/../includes/faq-admin.php';
$assetPrefix = '../';
$pageTitle = 'Add New FAQ - TutorMeQuran';
$pageDescription = 'Manage your TutorMeQuran activity.';
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
<header class="portal-heading">
<div><span class="section-subtitle">Operations workspace</span>
<h1>Add New FAQ</h1>
<p>Manage your TutorMeQuran activity.</p>
</div>
</header>

  <section class="portal-card ">
<h2>FAQ Answer</h2>
<?php require __DIR__ . '/../includes/faq-form.php'; ?>
</section>

</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
