<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/profile-page.php';
$assetPrefix = '../';
$pageTitle = 'Tutor account - TutorMeQuran';
$pageDescription = 'Your TutorMeQuran account and profile information.';
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
<h1>Tutor account</h1>
<p>Your saved account and profile information.</p>
</div>
<div class="portal-actions"><a href="manage-tutors-edit.php?id=<?= (int)($profile['tutor_id'] ?? 0) ?>" class="btn btn-primary btn-sm">Edit account</a><a href="manage-tutors.php" class="btn btn-outline btn-sm">Back</a>
</div>
</header>
<?php tmq_profile_notice(); ?>
<section class="admin-profile-preview" aria-label="Tutor profile picture"><img src="<?= tmq_escape(tmq_photo_url($profile ?? $authUser)) ?>" alt="Profile picture of <?= tmq_escape(trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''))) ?>"><div><strong><?= tmq_escape(trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''))) ?></strong><span>Tutor profile picture</span></div></section>
<div class="portal-columns">
<section class="portal-card ">
<h2>Private account details</h2>
<dl class="portal-details">
<div>
<dt>Tutor ID</dt>
<dd><?= tmq_escape(tmq_profile_detail('Tutor ID')) ?></dd>
</div>
<div>
<dt>First Name</dt>
<dd><?= tmq_escape(tmq_profile_detail('First Name')) ?></dd>
</div>
<div>
<dt>Last Name</dt>
<dd><?= tmq_escape(tmq_profile_detail('Last Name')) ?></dd>
</div>
<div>
<dt>IC Number</dt>
<dd><?= tmq_escape(tmq_profile_detail('IC Number')) ?></dd>
</div>
<div>
<dt>Phone</dt>
<dd><?= tmq_escape(tmq_profile_detail('Phone')) ?></dd>
</div>
<div>
<dt>Email</dt>
<dd><?= tmq_escape(tmq_profile_detail('Email')) ?></dd>
</div>
<div>
<dt>Address</dt>
<dd><?= tmq_escape(tmq_profile_detail('Address')) ?></dd>
</div>
<div>
<dt>Account ID</dt>
<dd><?= tmq_escape(tmq_profile_detail('Account ID')) ?></dd>
</div>
<div>
<dt>Username</dt>
<dd><?= tmq_escape(tmq_profile_detail('Username')) ?></dd>
</div>
<div>
<dt>Tutor Status</dt>
<dd><?= tmq_escape(tmq_profile_detail('Tutor Status')) ?></dd>
</div>
</dl>
</section>
<section class="portal-card ">
<h2>Teaching information</h2>
<dl class="portal-details">
<div>
<dt>Qualification</dt>
<dd><?= tmq_escape(tmq_profile_detail('Qualification')) ?></dd>
</div>
<div>
<dt>Teaching Mode</dt>
<dd><?= tmq_escape(tmq_profile_detail('Teaching Mode')) ?></dd>
</div>
<div>
<dt>Hourly Rate</dt>
<dd><?= tmq_escape(tmq_profile_detail('Hourly Rate')) ?></dd>
</div>
<div>
<dt>Teaching Location</dt>
<dd><?= tmq_escape(tmq_profile_detail('Teaching Location')) ?></dd>
</div>
<div>
<dt>About</dt>
<dd><?= tmq_escape(tmq_profile_detail('About')) ?></dd>
</div>
<div>
<dt>Certificate</dt>
<dd><?= tmq_escape(tmq_profile_detail('Certificate')) ?></dd>
</div>
<div>
<dt>Profile Visibility</dt>
<dd><?= tmq_escape(tmq_profile_detail('Profile Visibility')) ?></dd>
</div>
</dl>
</section>
</div>
<div class="portal-stats">
<article class="portal-card portal-stat">
<p>Total Bookings</p><strong>?</strong><span>Booking data is not connected</span>
</article>
<article class="portal-card portal-stat">
<p>Average Rating</p><strong>Not available</strong><span>Feedback data is not connected</span>
</article>
<article class="portal-card portal-stat">
<p>Certificate</p><strong>Verified</strong><span>See certificate history</span>
</article>
</div><section class="portal-card"><h2>Profile details</h2><dl class="portal-details"><div><dt>Profile Picture</dt><dd><?= tmq_escape(tmq_profile_detail('Profile Picture')) ?></dd></div><div><dt>Gender</dt><dd><?= tmq_escape(tmq_profile_detail('Gender')) ?></dd></div><div><dt>Date of Birth</dt><dd><?= tmq_escape(tmq_profile_detail('Date of Birth')) ?></dd></div><div><dt>State</dt><dd><?= tmq_escape(tmq_profile_detail('State')) ?></dd></div><div><dt>Address / Area</dt><dd><?= tmq_escape(tmq_profile_detail('Address / Area')) ?></dd></div></dl></section><section class="portal-card"><h2>Teaching preferences</h2><dl class="portal-details"><div><dt>Teaching Location / Area</dt><dd><?= tmq_escape(tmq_profile_detail('Teaching Location / Area')) ?></dd></div><div><dt>Language Available</dt><dd><?= tmq_escape(tmq_profile_detail('Language Available')) ?></dd></div><div><dt>Lesson Focus</dt><dd><?= tmq_escape(tmq_profile_detail('Lesson Focus')) ?></dd></div><div><dt>Optional Social Networks</dt><dd><?= tmq_escape(tmq_profile_detail('Optional Social Networks')) ?></dd></div></dl></section>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
