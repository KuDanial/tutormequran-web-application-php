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
<h2>Nur Amirah</h2>
<dl class="portal-details">
<div>
<dt>Admin ID</dt>
<dd>AD-001</dd>
</div>
<div>
<dt>First Name</dt>
<dd>Nur</dd>
</div>
<div>
<dt>Last Name</dt>
<dd>Amirah</dd>
</div>
<div>
<dt>Email</dt>
<dd>admin@example.com</dd>
</div>
<div>
<dt>Account ID</dt>
<dd>ACC-004</dd>
</div>
<div>
<dt>Username</dt>
<dd>nur.amirah</dd>
</div>
<div>
<dt>Role</dt>
<dd>Admin</dd>
</div>
</dl>
</section>
<section class="portal-card ">
<h2>Edit profile</h2>
<form class="portal-form">
<button type="submit" hidden disabled aria-hidden="true">
</button>
<div class="portal-fields">
<div class="form-group">
<label for="f-adminFName" class="form-label">First Name</label>
<div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg><input id="f-adminFName" name="adminFName" class="form-control" type="text" value="Nur"></div>
</div>
<div class="form-group">
<label for="f-adminLName" class="form-label">Last Name</label>
<div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg><input id="f-adminLName" name="adminLName" class="form-control" type="text" value="Amirah"></div>
</div>
<div class="form-group">
<label for="f-adminEmail" class="form-label">Email</label>
<div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 6 10 7L22 6"/></svg><input id="f-adminEmail" name="adminEmail" class="form-control" type="email" value="admin@example.com"></div>
</div>
</div>
<div class="portal-actions">
<button type="button" class="btn btn-primary">Save Changes</button>
</div>
</form>
</section><section class="portal-card" id="change-password"><h2>Change password</h2><?php require __DIR__ . '/../includes/auth-message.php'; ?><form class="portal-form" method="post" data-auth-form action="#change-password"><?= tmq_csrf_field() ?><div class="portal-fields"><div class="form-group"><label class="form-label" for="x-currentPassword">Current Password</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/></svg><input id="x-currentPassword" name="currentPassword" class="form-control" type="password" required value="" autocomplete="current-password"></div></div><div class="form-group"><label class="form-label" for="x-newPassword">New Password</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/></svg><input id="x-newPassword" name="newPassword" class="form-control" type="password" required value="" autocomplete="new-password" minlength="8" maxlength="72"></div></div><div class="form-group"><label class="form-label" for="x-confirmNewPassword">Confirm New Password</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/></svg><input id="x-confirmNewPassword" name="confirmNewPassword" class="form-control" type="password" required value="" autocomplete="new-password" minlength="8" maxlength="72"></div></div></div><button type="submit" class="btn btn-primary">Update Password</button></form><p class="field-help">Use 8?72 bytes, including a letter and a number. Your other sessions will need to log in again.</p></section>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
