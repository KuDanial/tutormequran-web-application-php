<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/profile-page.php';
$assetPrefix = '../';
$pageTitle = 'Profile publishing - TutorMeQuran';
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
<h1><?= $missing ? 'Complete your profile before publishing' : 'Your profile is ready to publish' ?></h1>
<p>Your saved account and profile information.</p>
</div><a href="tutor-profile-setup.php" class="btn btn-outline btn-sm">Back to profile setup</a>
</header>
<?php tmq_profile_notice(); ?><?php if($missing): ?><aside class="flow-notice info"><strong>Still needed</strong><p><?= tmq_escape(implode(', ', $missing)) ?></p></aside><?php endif; ?>
<ol class="journey">
<li class="<?= $certificateHistory ? 'done' : 'current' ?>"><span>01</span>
<div><strong>Apply</strong><small>Personal details & Ijazah</small>
</div>
</li>
<li class="<?= $applicationApproved ? 'done' : 'current' ?>"><span>02</span>
<div><strong>Admin review</strong><small>Qualification verification</small>
</div>
</li>
<li class="<?= $applicationApproved ? ($missing ? 'current' : 'done') : '' ?>"><span>03</span>
<div><strong>Set up profile</strong><small>Pricing & teaching details</small>
</div>
</li>
<li class="<?= !$missing ? 'current' : '' ?>"><span>04</span>
<div><strong>Publish</strong><small>Choose your visibility</small>
</div>
</li>
</ol>
<div class="portal-columns">
<section class="portal-card ">
<h2>Profile checklist</h2>
<dl class="portal-details">
<div>
<dt>Certificate</dt>
<dd><?= tmq_escape(tmq_profile_detail('Certificate')) ?></dd>
</div>
<div>
<dt>Display Name</dt>
<dd><?= tmq_escape(tmq_profile_detail('Display Name')) ?></dd>
</div>
<div>
<dt>Biography</dt>
<dd><?= tmq_escape(tmq_profile_detail('Biography')) ?></dd>
</div>
<div>
<dt>Hourly Rate</dt>
<dd><?= tmq_escape(tmq_profile_detail('Hourly Rate')) ?></dd>
</div>
<div>
<dt>Teaching Mode</dt>
<dd><?= tmq_escape(tmq_profile_detail('Teaching Mode')) ?></dd>
</div>
<div>
<dt>Available Slot</dt>
<dd><?= tmq_escape(tmq_profile_detail('Available Slot')) ?></dd>
</div>
<div>
<dt>Current Visibility</dt>
<dd><?= tmq_escape(tmq_profile_detail('Current Visibility')) ?></dd>
</div>
</dl>
</section>
<section class="portal-card ">
<h2>Choose your listing visibility</h2>
<form class="visibility-form" method="post" enctype="multipart/form-data" data-profile-form><?= tmq_csrf_field() ?><input type="hidden" name="profile_action" value="visibility">

<fieldset>
<legend>Visibility</legend>
<label class="visibility-option">
<input type="radio" name="profileVisibility" value="draft" <?= ($profile['profile_visibility'] ?? 'draft') === 'draft' ? 'checked' : '' ?>><span><strong>Draft</strong><small>Keep preparing. Not listed.</small></span>
</label>
<label class="visibility-option">
<input type="radio" name="profileVisibility" value="private" <?= ($profile['profile_visibility'] ?? 'draft') === 'private' ? 'checked' : '' ?>><span><strong>Private</strong><small>Completed but hidden from search.</small></span>
</label>
<label class="visibility-option">
<input type="radio" name="profileVisibility" value="public" <?= ($profile['profile_visibility'] ?? 'draft') === 'public' ? 'checked' : '' ?>><span><strong>Public</strong><small>List your verified profile and accept bookings.</small></span>
</label>
</fieldset>
<button type="submit" class="btn btn-primary">Save Visibility</button>
</form>
</section>
</div>
<aside class="flow-notice info"><strong>You stay in control</strong>
<p>Publishing is your choice after verification. Private and Draft profiles are not listed or bookable. Only eligible Public profiles appear in the public directory.</p>
</aside>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
