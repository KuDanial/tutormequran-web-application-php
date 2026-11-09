<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/profile-page.php';
$assetPrefix = '../';
$pageTitle = 'Create your teaching profile - TutorMeQuran';
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
<h1>Create your teaching profile</h1>
<p>Your saved account and profile information.</p>
</div>
</header>
<?php tmq_profile_notice(); ?>
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
<section class="portal-card ">
<h2>Teaching details</h2>
<form class="portal-form" method="post" enctype="multipart/form-data" data-profile-form><?= tmq_csrf_field() ?><input type="hidden" name="profile_action" value="teaching">

<div class="portal-fields">
<div class="form-group">
<label for="f-displayName" class="form-label">Display Name</label>
<div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg><input id="f-displayName" name="displayName" class="form-control" type="text" value="<?= tmq_escape(tmq_profile_value('displayName')) ?>"<?= tmq_profile_disabled('displayName') ?>></div>
</div>
<div class="form-group">
<label for="f-qualification" class="form-label">Qualification</label>
<div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 5c-3-2-7-2-10-1v15c3-1 7-1 10 1 3-2 7-2 10-1V4c-3-1-7-1-10 1Zm0 0v15"/></svg><input id="f-qualification" name="qualification" class="form-control" type="text" readonly value="<?= tmq_escape(tmq_profile_value('qualification')) ?>"<?= tmq_profile_disabled('qualification') ?>></div>
</div>
<div class="form-group">
<label for="f-hourlyRate" class="form-label">Hourly Rate (RM)</label>
<div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="2" y="5" width="20" height="14" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M5 12h1m12 0h1"/></svg><input id="f-hourlyRate" name="hourlyRate" min="0.01" step="0.01" class="form-control" type="number" value="<?= tmq_escape(tmq_profile_value('hourlyRate')) ?>"<?= tmq_profile_disabled('hourlyRate') ?>></div>
</div>
<div class="form-group">
<label for="f-teachingMode" class="form-label">Teaching Mode</label>
<div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><ellipse cx="12" cy="12" rx="4" ry="10"/><path d="M2 12h20"/></svg><select id="f-teachingMode" name="teachingMode" class="form-control"<?= tmq_profile_disabled('teachingMode') ?>><?= tmq_profile_options('teachingMode', ['online'=>'Online','physical'=>'Physical','both'=>'Both']) ?></select></div>
</div>
<div class="form-group">
<label for="f-teachingState" class="form-label">Teaching State</label>
<div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg><input id="f-teachingState" name="teachingState" class="form-control" type="text" value="<?= tmq_escape(tmq_profile_value('teachingState')) ?>"<?= tmq_profile_disabled('teachingState') ?>></div>
</div>
<div class="form-group">
<label for="f-shortBio" class="form-label">Short Biography</label>
<div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m15 3 6 6-12 12H3v-6Zm-2 2 6 6"/></svg><textarea id="f-shortBio" name="shortBio" class="form-control" rows="4"<?= tmq_profile_disabled('shortBio') ?>><?= tmq_escape(tmq_profile_value('shortBio')) ?></textarea></div>
</div>
<div class="form-group">
<label for="f-availabilityDate" class="form-label">Available Date</label>
<div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 11h18m-13 4h2m4 0h2"/></svg><input id="f-availabilityDate" name="availabilityDate" class="form-control" type="date" value="" disabled></div>
</div>
<div class="form-group">
<label for="f-startTime" class="form-label">Start Time</label>
<div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg><input id="f-startTime" name="startTime" class="form-control" type="time" value="" disabled></div>
</div>
<div class="form-group">
<label for="f-endTime" class="form-label">End Time</label>
<div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg><input id="f-endTime" name="endTime" class="form-control" type="time" value="" disabled></div>
</div>
</div>
<div class="portal-actions">
<button type="submit" class="btn btn-primary">Save Draft</button><a href="tutor-application-approved.php" class="btn btn-outline btn-sm">Back</a>
</div>
</form>
</section>
<section class="portal-card ">
<h2>Initial visibility</h2>
<dl class="portal-details">
<div>
<dt>Profile</dt>
<dd><?= tmq_escape(tmq_profile_detail('Profile')) ?></dd>
</div>
</dl>
<button type="button" class="btn btn-primary" disabled>Publish Profile</button>
<p class="portal-muted">Complete and save the profile before publishing. Manage lesson slots from My Availability; availability is not required to publish.</p>
</section>
<div class="portal-actions"><a href="tutor-profile-ready.php" class="btn btn-outline btn-sm">Continue to profile visibility</a>
</div><section class="portal-card"><h2>Additional profile information</h2><form class="portal-form" method="post" enctype="multipart/form-data" data-profile-form><?= tmq_csrf_field() ?><input type="hidden" name="profile_action" value="additional"><div class="portal-fields"><div class="form-group"><label class="form-label" for="x-profilePicture">Profile Picture (required)</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8" cy="8" r="2"/><path d="m3 18 6-6 4 4 3-3 5 5"/></svg><input id="x-profilePicture" name="profilePicture" class="form-control" type="file" accept="image/png,image/jpeg"<?= tmq_profile_disabled('profilePicture') ?>></div></div><div class="form-group"><label class="form-label" for="x-gender">Gender</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg><select id="x-gender" name="gender" class="form-control"<?= tmq_profile_disabled('gender') ?>><?= tmq_profile_options('gender', ['male'=>'Male','female'=>'Female','prefer_not_to_say'=>'Prefer not to say']) ?></select></div></div><div class="form-group"><label class="form-label" for="x-dateOfBirth">Date of Birth</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 11h18m-13 4h2m4 0h2"/></svg><input id="x-dateOfBirth" name="dateOfBirth" class="form-control" type="date" value="<?= tmq_escape(tmq_profile_value('dateOfBirth')) ?>"<?= tmq_profile_disabled('dateOfBirth') ?>></div></div><div class="form-group"><label class="form-label" for="x-teachingLocation">Teaching Location / Area</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg><input id="x-teachingLocation" name="teachingLocation" class="form-control" type="text" value="<?= tmq_escape(tmq_profile_value('teachingLocation')) ?>"<?= tmq_profile_disabled('teachingLocation') ?>></div></div><div class="form-group"><label class="form-label" for="x-languages">Language Available</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><ellipse cx="12" cy="12" rx="4" ry="10"/><path d="M2 12h20"/></svg><?php tmq_profile_choices('languages'); ?></div></div><div class="form-group full-field"><span id="lesson-focus-label" class="form-label">Lesson Focus</span><div class="lesson-tags" role="group" aria-labelledby="lesson-focus-label"><?php tmq_profile_choices('focuses'); ?></div><p class="field-help">Select the lesson focuses you teach.</p></div></div><button type="submit" class="btn btn-primary">Save Changes</button></form></section><section class="portal-card"><h2>Social networks · optional</h2><form class="portal-form" method="post" enctype="multipart/form-data" data-profile-form><?= tmq_csrf_field() ?><input type="hidden" name="profile_action" value="social"><div class="portal-fields"><div class="form-group"><label class="form-label" for="x-facebook">Facebook</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><ellipse cx="12" cy="12" rx="4" ry="10"/><path d="M2 12h20"/></svg><input id="x-facebook" name="facebook" class="form-control" type="url" value="<?= tmq_escape(tmq_profile_value('facebook')) ?>"<?= tmq_profile_disabled('facebook') ?>></div></div><div class="form-group"><label class="form-label" for="x-instagram">Instagram</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><ellipse cx="12" cy="12" rx="4" ry="10"/><path d="M2 12h20"/></svg><input id="x-instagram" name="instagram" class="form-control" type="url" value="<?= tmq_escape(tmq_profile_value('instagram')) ?>"<?= tmq_profile_disabled('instagram') ?>></div></div><div class="form-group"><label class="form-label" for="x-linkedin">LinkedIn</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><ellipse cx="12" cy="12" rx="4" ry="10"/><path d="M2 12h20"/></svg><input id="x-linkedin" name="linkedin" class="form-control" type="url" value="<?= tmq_escape(tmq_profile_value('linkedin')) ?>"<?= tmq_profile_disabled('linkedin') ?>></div></div><div class="form-group"><label class="form-label" for="x-twitter">X / Twitter</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><ellipse cx="12" cy="12" rx="4" ry="10"/><path d="M2 12h20"/></svg><input id="x-twitter" name="twitter" class="form-control" type="url" value="<?= tmq_escape(tmq_profile_value('twitter')) ?>"<?= tmq_profile_disabled('twitter') ?>></div></div><div class="form-group"><label class="form-label" for="x-tiktok">TikTok</label><div class="profile-field-icon"><svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><ellipse cx="12" cy="12" rx="4" ry="10"/><path d="M2 12h20"/></svg><input id="x-tiktok" name="tiktok" class="form-control" type="url" value="<?= tmq_escape(tmq_profile_value('tiktok')) ?>"<?= tmq_profile_disabled('tiktok') ?>></div></div></div><button type="submit" class="btn btn-primary">Save Social Networks</button></form></section><p class="field-help">Approved qualification changes require a new certificate review.</p>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
