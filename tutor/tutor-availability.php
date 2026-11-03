<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/availability-page.php';
$assetPrefix = '../';
$pageTitle = 'Manage Availability - TutorMeQuran';
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
<h1>Manage Availability</h1>
<p>Manage your TutorMeQuran activity.</p>
</div>
<div class="portal-actions"><a class="btn btn-primary btn-sm" href="tutor-availability-addNew.php">Add Availability</a>
</div>
</header><p data-availability-message role="status"><?= tmq_escape($slotMessage) ?></p><p class="field-help">Flexible lesson durations &middot; Malaysia time (MYT). Your saved lesson slots. Set a slot to Unavailable to retain it without offering it.</p>

  <div class="portal-table-wrap" role="region" aria-label="Availability slots" tabindex="0">
<table class="portal-table">
<caption>Availability slots</caption>
<thead>
<tr>
<th scope="col">Date</th>
<th scope="col">Start Time</th>
<th scope="col">End Time</th>
<th scope="col">Duration</th>
<th scope="col">Mode</th>
<th scope="col">Status</th>
<th scope="col">Action</th>
</tr>
</thead>
<tbody data-availability-list>
<?php foreach ($slots as $entry): ?><tr>
<?php foreach (tmq_slot_labels($entry) as $label): ?><td><?= tmq_escape($label) ?></td><?php endforeach; ?>
<td><div class="portal-actions"><a class="btn btn-outline btn-sm" href="tutor-availability-view.php?slot=<?= (int)$entry['slot_id'] ?>">View</a><a class="btn btn-outline btn-sm" href="tutor-availability-edit.php?slot=<?= (int)$entry['slot_id'] ?>">Edit</a></div></td>
</tr><?php endforeach; ?>
<?php if (!$slots): ?><tr><td colspan="7">No availability slots to display.</td></tr><?php endif; ?>
</tbody>
</table>
</div>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script><script src="../assets/js/lesson-slots.js"></script><script src="../assets/js/availability.js"></script></body></html>
