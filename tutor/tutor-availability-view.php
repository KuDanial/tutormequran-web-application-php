<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/availability-page.php';
$assetPrefix = '../';
$pageTitle = 'Availability Detail - TutorMeQuran';
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
<h1>Availability Detail</h1>
<p>Manage your TutorMeQuran activity.</p>
</div>
<div class="portal-actions"><?php if ($slot): ?><a class="btn btn-primary btn-sm" data-edit-slot href="tutor-availability-edit.php?slot=<?= (int)$slot['slot_id'] ?>">Edit</a><?php endif; ?><a class="btn btn-outline btn-sm" href="tutor-availability.php">Back</a>
</div>
</header><p data-availability-message role="status"><?= tmq_escape($slotMessage) ?></p>

  <section class="portal-card ">
<h2>Lesson Slot</h2>
<?php if ($slot): ?><dl class="portal-details" data-availability-detail>
<?php $labels=tmq_slot_labels($slot); foreach (['Date','Start Time','End Time','Duration','Mode','Status'] as $i=>$heading): ?>
<div><dt><?= tmq_escape($heading) ?></dt><dd><?= tmq_escape($labels[$i]) ?></dd></div>
<?php endforeach; ?></dl><p class="field-help">All times are Malaysia time (MYT).</p><?php endif; ?>
</section>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script><script src="../assets/js/lesson-slots.js"></script><script src="../assets/js/availability.js"></script></body></html>
