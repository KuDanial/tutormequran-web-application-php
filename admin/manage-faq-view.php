<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'FAQ Detail - TutorMeQuran';
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
<h1>FAQ Detail</h1>
<p>Manage your TutorMeQuran activity.</p>
</div>
<div class="portal-actions"><a class="btn btn-primary btn-sm" href="manage-faq-edit.php">Edit</a><a class="btn btn-outline btn-sm" href="manage-faq.php">Back</a>
</div>
</header>

  <section class="portal-card ">
<h2>Question & Answer</h2>
<dl class="portal-details">
<div>
<dt>Question / Keyword</dt>
<dd>How to book a tutor?</dd>
</div>
<div>
<dt>Answer</dt>
<dd>Browse tutors, open a tutor profile, choose an available date and time, then complete the booking form. Your booking details will appear in My Bookings.</dd>
</div>
<div>
<dt>Status</dt>
<dd><span class="portal-badge good">Active</span>
</dd>
</div>
</dl>
</section>

</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
