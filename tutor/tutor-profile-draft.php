<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/profile-page.php';
$assetPrefix = '../';
$pageTitle = 'Your profile is draft - TutorMeQuran';
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
<h1>Your profile is <?= tmq_escape($profile['profile_visibility'] ?? 'draft') ?></h1>
<p>Your saved account and profile information.</p>
</div>
<div class="portal-actions"><a href="tutor-profile-edit.php" class="btn btn-primary btn-sm">Edit profile</a><a href="tutor-profile-visibility.php" class="btn btn-outline btn-sm">Visibility settings</a>
</div>
</header>
<?php tmq_profile_notice(); ?>
<aside class="flow-notice warm"><strong><?= isset($profile['tutor_id']) && tmq_public_tutor($profile) ? 'Listed in tutor search' : 'Not listed in tutor search' ?></strong>
<p>Public listing depends on your saved visibility and profile requirements. Booking features will be available in a later phase.</p>
</aside>
<section class="portal-card ">
<h2>Profile visibility</h2>
<dl class="portal-details">
<div>
<dt>Verification</dt>
<dd><?= tmq_escape(tmq_profile_detail('Verification')) ?></dd>
</div>
<div>
<dt>Visibility</dt>
<dd><?= tmq_escape(tmq_profile_detail('Visibility')) ?></dd>
</div>
<div>
<dt>Hourly Rate</dt>
<dd><?= tmq_escape(tmq_profile_detail('Hourly Rate')) ?></dd>
</div>
<div>
<dt>Next Step</dt>
<dd><?= tmq_escape(tmq_profile_detail('Next Step')) ?></dd>
</div>
</dl>
</section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
