<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/profile-page.php';
$assetPrefix = '../';
$pageTitle = 'Your teaching profile - TutorMeQuran';
$pageDescription = 'Your TutorMeQuran account and profile information.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Tutor studio</span>
<h1>Your teaching profile</h1>
<p>Your saved account and profile information.</p>
</div>
<div class="portal-actions"><a href="tutor-profile-edit.php" class="btn btn-primary btn-sm">Edit profile</a><a href="tutor-profile-edit.php#profile-visibility" class="btn btn-outline btn-sm">Manage visibility</a><a href="../tutor-detail.php?id=<?= (int)($profile['tutor_id'] ?? 0) ?>" class="btn btn-outline btn-sm">View public profile</a>
</div>
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
<aside class="flow-notice info"><strong>Public profile</strong>
<p>Students can find this profile in search. You can make it private without cancelling existing bookings.</p>
</aside><section class="portal-card"><h2>Profile details</h2><dl class="portal-details"><div><dt>Profile Picture</dt><dd><?= tmq_escape(tmq_profile_detail('Profile Picture')) ?></dd></div><div><dt>Gender</dt><dd><?= tmq_escape(tmq_profile_detail('Gender')) ?></dd></div><div><dt>Date of Birth</dt><dd><?= tmq_escape(tmq_profile_detail('Date of Birth')) ?></dd></div><div><dt>State</dt><dd><?= tmq_escape(tmq_profile_detail('State')) ?></dd></div><div><dt>Address / Area</dt><dd><?= tmq_escape(tmq_profile_detail('Address / Area')) ?></dd></div></dl></section><section class="portal-card"><h2>Teaching preferences</h2><dl class="portal-details"><div><dt>Teaching Location / Area</dt><dd><?= tmq_escape(tmq_profile_detail('Teaching Location / Area')) ?></dd></div><div><dt>Language Available</dt><dd><?= tmq_escape(tmq_profile_detail('Language Available')) ?></dd></div><div><dt>Lesson Focus</dt><dd><?= tmq_escape(tmq_profile_detail('Lesson Focus')) ?></dd></div><div><dt>Optional Social Networks</dt><dd><?= tmq_escape(tmq_profile_detail('Optional Social Networks')) ?></dd></div></dl></section></div>
</div>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
