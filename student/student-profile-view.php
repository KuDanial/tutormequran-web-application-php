<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/profile-page.php';
$assetPrefix = '../';
$pageTitle = 'Your profile - TutorMeQuran';
$pageDescription = 'Private personal and account information.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Your learning journey</span>
<h1>Your profile</h1>
<p>Private personal and account information.</p>
</div><a href="student-profile-edit.php" class="btn btn-primary btn-sm">Edit profile</a>
</header>
<?php tmq_profile_notice(); ?>
<div class="profile-page-layout">
<aside class="portal-card profile-side-card" aria-label="Profile picture">
<img class="profile-photo" src="<?= tmq_escape(tmq_photo_url($profile ?? $authUser)) ?>" alt="Profile picture" width="160" height="160">

</aside>
<div class="profile-side-content">

<section class="portal-card ">
<h2><?= tmq_escape(trim(($profile['display_name'] ?? '') ?: (($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')))) ?></h2>
<dl class="portal-details">
<div>
<dt>Student ID</dt>
<dd><?= tmq_escape(tmq_profile_detail('Student ID')) ?></dd>
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
<dt>Role</dt>
<dd><?= tmq_escape(tmq_profile_detail('Role')) ?></dd>
</div>
</dl>
</section><section class="portal-card"><h2>Profile details</h2><dl class="portal-details"><div><dt>Profile Picture</dt><dd><?= tmq_escape(tmq_profile_detail('Profile Picture')) ?></dd></div><div><dt>Gender</dt><dd><?= tmq_escape(tmq_profile_detail('Gender')) ?></dd></div><div><dt>Date of Birth</dt><dd><?= tmq_escape(tmq_profile_detail('Date of Birth')) ?></dd></div><div><dt>State</dt><dd><?= tmq_escape(tmq_profile_detail('State')) ?></dd></div><div><dt>Address / Area</dt><dd><?= tmq_escape(tmq_profile_detail('Address / Area')) ?></dd></div></dl></section></div>
</div>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
