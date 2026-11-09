<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/profile-page.php';
$assetPrefix = '../';
$pageTitle = 'Tutor accounts - TutorMeQuran';
$pageDescription = 'Your TutorMeQuran account and profile information.';
$currentPage = 'manage-tutors.php';
$adminPrefix = '';
$workspaceLabel = 'Administration';
$showAdminSettings = true;
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page admin-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<div class="admin-layout">
<?php require __DIR__ . '/../includes/admin-sidebar.php'; ?>
<div class="admin-body">
<?php require __DIR__ . '/../includes/admin-header.php'; ?>
<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Operations workspace</span>
<h1>Tutor accounts</h1>
<p>Your saved account and profile information.</p>
</div>
</header>
<?php tmq_profile_notice(); ?>
<div class="portal-table-wrap" role="region" aria-label="Tutor directory" tabindex="0">
<table class="portal-table"><caption>Tutor directory</caption>
<thead>
<tr>
<th scope="col">Tutor ID</th>
<th scope="col">Tutor Name</th>
<th scope="col">Email</th>
<th scope="col">State</th>
<th scope="col">Hourly Rate</th>
<th scope="col">Certificate Status</th>
<th scope="col">Visibility</th>
<th scope="col">Action</th>
</tr>
</thead><tbody><?php $rows=$profileRows; foreach($rows as $t): ?><tr><td>TU-<?= (int)$t['tutor_id'] ?></td><td><?= tmq_escape($t['first_name'].' '.$t['last_name']) ?></td><td><?= tmq_escape($t['email']) ?></td><td><?= tmq_escape($t['teaching_state'] ?? 'Not set') ?></td><td><?= tmq_escape($t['hourly_rate'] ?? 'Not set') ?></td><td><?= tmq_escape($t['certificate_status'] ?? 'No displayed qualification') ?></td><td><?= tmq_escape($t['profile_visibility']) ?></td><td><div class="portal-actions"><a class="btn btn-outline btn-sm" href="manage-tutors-view.php?id=<?= (int)$t['tutor_id'] ?>">View</a><a class="btn btn-outline btn-sm" href="manage-tutors-edit.php?id=<?= (int)$t['tutor_id'] ?>">Edit</a></div></td></tr><?php endforeach; if(!$rows): ?><tr><td colspan="8">No tutor accounts.</td></tr><?php endif; ?></tbody></table>
</div>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script><script src="../assets/js/admin-crud.js"></script></body></html>
