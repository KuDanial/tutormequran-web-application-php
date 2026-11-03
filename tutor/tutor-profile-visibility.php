<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/profile-page.php';
$assetPrefix = '../';
$pageTitle = 'Choose your visibility - TutorMeQuran';
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
<h1>Choose your visibility</h1>
<p>Your saved account and profile information.</p>
</div>
</header>
<?php tmq_profile_notice(); ?><?php if($missing): ?><aside class="flow-notice info"><strong>Still needed</strong><p><?= tmq_escape(implode(', ', $missing)) ?></p></aside><?php endif; ?>
<ol class="journey">
<li class="done"><span>01</span>
<div><strong>Apply</strong><small>Personal details & Ijazah</small>
</div>
</li>
<li class="done"><span>02</span>
<div><strong>Admin review</strong><small>Qualification verification</small>
</div>
</li>
<li class="done"><span>03</span>
<div><strong>Set up profile</strong><small>Pricing & teaching details</small>
</div>
</li>
<li class="current" aria-current="step"><span>04</span>
<div><strong>Publish</strong><small>Choose your visibility</small>
</div>
</li>
</ol>
<section class="portal-card ">
<h2>Listing controls</h2>
<form class="visibility-form" method="post" enctype="multipart/form-data" data-profile-form><?= tmq_csrf_field() ?><input type="hidden" name="profile_action" value="visibility">

<fieldset>
<legend>Profile visibility</legend>
<label class="visibility-option">
<input type="radio" name="profileVisibility" value="draft" <?= ($profile['profile_visibility'] ?? 'draft') === 'draft' ? 'checked' : '' ?>><span><strong>Draft</strong><small>Prepare your profile. It is not listed or bookable.</small></span>
</label>
<label class="visibility-option">
<input type="radio" name="profileVisibility" value="private" <?= ($profile['profile_visibility'] ?? 'draft') === 'private' ? 'checked' : '' ?>><span><strong>Private</strong><small>Keep your completed profile unlisted and pause new bookings.</small></span>
</label>
<label class="visibility-option">
<input type="radio" name="profileVisibility" value="public" <?= ($profile['profile_visibility'] ?? 'draft') === 'public' ? 'checked' : '' ?>><span><strong>Public</strong><small>Appear in search and accept bookings for available slots.</small></span>
</label>
</fieldset>
<div class="portal-actions">
<button type="submit" class="btn btn-primary">Save Visibility</button>
</div>
</form>
</section>
<aside class="flow-notice info"><strong>Before a profile can be public</strong>
<p>The tutor must be verified and active, with a profile picture, completed biography, qualification, hourly rate and at least one language and lesson focus. Existing bookings remain accessible when a profile becomes private.</p>
</aside>
<section class="portal-card ">
<h2>Profile states</h2>
<div class="portal-actions"><a href="tutor-profile-draft.php" class="btn btn-outline btn-sm">Profile status</a><a href="tutor-profile-private.php" class="btn btn-outline btn-sm">Saved visibility</a><a href="../tutor-detail.php?id=<?= (int)($profile['tutor_id'] ?? 0) ?>" class="btn btn-outline btn-sm">Public listing</a>
</div>
</section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
