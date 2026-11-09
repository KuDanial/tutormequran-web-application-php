<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/password-change.php';
$assetPrefix = '../';
$pageTitle = 'My admin account - TutorMeQuran';
$pageDescription = 'Private administrator information.';
$currentPage = 'admin-profile.php';
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
<h1>My admin account</h1>
<p>Private administrator information.</p>
</div>
</header>
<section class="portal-card ">
<h2><?= tmq_escape($authUser['first_name'] . ' ' . $authUser['last_name']) ?></h2>
<dl class="portal-details"><div><dt>Account ID</dt><dd><?= (int)$authUser['user_id'] ?></dd></div><div><dt>Username</dt><dd><?= tmq_escape($authUser['username']) ?></dd></div><div><dt>Role</dt><dd>Admin</dd></div></dl>
</section><section class="portal-card" id="change-password"><h2>Change password</h2><?php require __DIR__ . '/../includes/auth-message.php'; ?><form class="portal-form" method="post" data-auth-form action="#change-password"><?= tmq_csrf_field() ?><div class="portal-fields"><div class="form-group"><label class="form-label" for="x-currentPassword">Current Password</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/></svg><input id="x-currentPassword" name="currentPassword" class="form-control" type="password" required value="" autocomplete="current-password"></div></div><div class="form-group"><label class="form-label" for="x-newPassword">New Password</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/></svg><input id="x-newPassword" name="newPassword" class="form-control" type="password" required value="" autocomplete="new-password" minlength="8" maxlength="72"></div></div><div class="form-group"><label class="form-label" for="x-confirmNewPassword">Confirm New Password</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/></svg><input id="x-confirmNewPassword" name="confirmNewPassword" class="form-control" type="password" required value="" autocomplete="new-password" minlength="8" maxlength="72"></div></div></div><button type="submit" class="btn btn-primary">Update Password</button></form><p class="field-help">Use 8–72 bytes, including a letter and a number. Your other sessions will need to log in again.</p></section>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
