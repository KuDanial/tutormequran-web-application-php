<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/availability-page.php';
$assetPrefix = '../';
$pageTitle = 'Edit Availability - TutorMeQuran';
$pageDescription = 'Manage your TutorMeQuran activity.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace" data-availability-server><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Tutor studio</span>
<h1>Edit Availability</h1>
<p>Manage your TutorMeQuran activity.</p>
</div>
</header>

  <section class="portal-card ">
<h2>Availability Details</h2>
<?php if ($slotReady): ?>
<form method="post" class="portal-form availability-editor" data-availability-form data-edit="true" aria-label="Availability details">
<?= tmq_csrf_field() ?>
<p class="field-help">Choose a date, start time and lesson duration. All times are Malaysia time (MYT).</p>
<div class="portal-fields">
<div class="form-group"><label class="form-label" for="date">1. Choose a date</label><input id="date" name="availabilityDate" type="date" class="form-control" value="<?= tmq_escape($slotValues['availabilityDate']) ?>" required></div>
<div class="form-group"><label class="form-label" for="start-time">2. Start time</label><select id="start-time" name="startTime" class="form-control" required><?php $hours=[]; for($h=0;$h<24;$h++){ $key=sprintf("%02d:00",$h); $hours[$key]=$key; } echo tmq_slot_options($hours,$slotValues['startTime']); ?></select><input name="endTime" type="hidden"><p class="field-help">Every lesson starts on the hour. The end time updates with your duration.</p></div>
<div class="form-group"><label class="form-label" for="duration">3. Lesson duration</label><select id="duration" name="durationMinutes" class="form-control" required aria-describedby="duration-help"><?php $durations=[]; for($h=1;$h<=24;$h++){ $durations[$h*60]=$h.($h===1?" hour":" hours"); } echo tmq_slot_options($durations,$slotValues['durationMinutes']); ?></select><p id="duration-help" class="field-help">Choose 1 to 24 hours. Overnight lessons end the next day.</p></div>
<div class="form-group"><label class="form-label" for="mode">4. Class mode</label><select id="mode" name="classType" class="form-control"><?= tmq_slot_options(['online'=>'Online','physical'=>'In person','both'=>'Online or in person'],$slotValues['classType']) ?></select></div>
<div class="form-group"><label class="form-label" for="status">Availability</label><select id="status" name="availabilityStatus" class="form-control"><?= tmq_slot_options(['available'=>'Available','unavailable'=>'Unavailable'],$slotValues['availabilityStatus']) ?></select></div>
</div><aside class="slot-summary"><span class="portal-badge good">Lesson preview</span><p data-slot-summary aria-live="polite"></p></aside>
<p data-availability-message role="status"><?= tmq_escape($slotMessage) ?></p><p class="field-help">Changes are saved to your account. Unavailable slots are retained and still block overlapping intervals.</p>
<div class="portal-actions"><button type="submit" class="btn btn-primary">Save lesson slot</button><a class="btn btn-outline" href="tutor-availability.php">Back to availability</a></div></form>
<?php else: ?><p role="status"><?= tmq_escape($slotMessage) ?></p><a class="btn btn-outline" href="tutor-availability.php">Back to availability</a><?php endif; ?>
</section>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script><script src="../assets/js/lesson-slots.js"></script><script src="../assets/js/availability.js"></script></body></html>
