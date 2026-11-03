<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Student account ST-001 - TutorMeQuran';
$pageDescription = 'Ahmad Hakimi · student profile and activity summary.';
$currentPage = '';
$adminPrefix = '';
$workspaceLabel = 'Administration';
$showAdminSettings = false;
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page admin-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<div class="admin-layout">
<?php require __DIR__ . '/../includes/admin-sidebar.php'; ?>
<div class="admin-body">
<?php require __DIR__ . '/../includes/admin-header.php'; ?>
<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Operations workspace</span>
<h1>Student account ST-001</h1>
<p>Ahmad Hakimi · student profile and activity summary.</p>
</div>
<div class="portal-actions"><a href="manage-students-edit.php" class="btn btn-primary btn-sm">Edit</a><a href="manage-students.php" class="btn btn-outline btn-sm">Back</a>
</div>
</header>
<section class="admin-profile-preview" aria-label="Student profile picture"><img src="../assets/img/default-avatar-profile.png" alt="Profile picture of Ahmad Hakimi"><div><strong>Ahmad Hakimi</strong><span>Student profile picture · ST-001</span></div></section>
<section class="portal-card ">
<h2>Personal & account information</h2>
<dl class="portal-details">
<div>
<dt>Student ID</dt>
<dd>ST-001</dd>
</div>
<div>
<dt>First Name</dt>
<dd>Ahmad</dd>
</div>
<div>
<dt>Last Name</dt>
<dd>Hakimi</dd>
</div>
<div>
<dt>IC Number</dt>
<dd>••••••-••-1234</dd>
</div>
<div>
<dt>Phone</dt>
<dd>012-345 6789</dd>
</div>
<div>
<dt>Email</dt>
<dd>ahmad@example.com</dd>
</div>
<div>
<dt>Address</dt>
<dd>Shah Alam, Selangor, Malaysia</dd>
</div>
<div>
<dt>Account ID</dt>
<dd>ACC-001</dd>
</div>
<div>
<dt>Username</dt>
<dd>ahmad.hakimi</dd>
</div>
<div>
<dt>Role</dt>
<dd>Student</dd>
</div>
</dl>
</section>
<div class="portal-columns">
<section class="portal-card ">
<h2>Booking history</h2>
<dl class="portal-details">
<div>
<dt>Total Bookings</dt>
<dd>3</dd>
</div>
<div>
<dt>Upcoming</dt>
<dd>1</dd>
</div>
<div>
<dt>Completed</dt>
<dd>2</dd>
</div>
</dl>
</section>
<section class="portal-card ">
<h2>Payment summary</h2>
<dl class="portal-details">
<div>
<dt>Verified</dt>
<dd>2 payments · RM 80.00</dd>
</div>
<div>
<dt>Pending</dt>
<dd>1 payment · RM 40.00</dd>
</div>
<div>
<dt>Rejected</dt>
<dd>0</dd>
</div>
</dl>
</section>
</div><section class="portal-card"><h2>Profile details</h2><dl class="portal-details"><div><dt>Profile Picture</dt><dd>Photo upload required · initials shown until uploaded</dd></div><div><dt>Gender</dt><dd>Male</dd></div><div><dt>Date of Birth</dt><dd>1 January 2000</dd></div><div><dt>State</dt><dd>Selangor</dd></div><div><dt>Address / Area</dt><dd>Shah Alam, Selangor</dd></div></dl></section>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
