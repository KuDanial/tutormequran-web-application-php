<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/profile-page.php';
$assetPrefix = '../';
$pageTitle = 'Tutor applications - TutorMeQuran';
$pageDescription = 'Your TutorMeQuran account and profile information.';
$currentPage = 'manage-certificates.php';
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
<h1>Tutor applications</h1>
<p>Your saved account and profile information.</p>
</div>
</header>
<?php tmq_profile_notice(); ?>
<div class="portal-table-wrap" role="region" aria-label="Certificate review queue" tabindex="0">
<table class="portal-table"><caption>Certificate review queue</caption>
<thead>
<tr>
<th scope="col">Certificate ID</th>
<th scope="col">Tutor Name</th>
<th scope="col">Email</th>
<th scope="col">Certificate Type</th>
<th scope="col">Certificate Origin</th>
<th scope="col">Year</th>
<th scope="col">Uploaded Date</th>
<th scope="col">Status</th>
<th scope="col">Action</th>
</tr>
</thead><tbody><?php $rows=tmq_profile_rows("SELECT c.*,u.first_name,u.last_name,u.email FROM certificates c JOIN tutors t ON t.tutor_id=c.tutor_id JOIN users u ON u.user_id=t.user_id ORDER BY (c.status='pending') DESC,c.certificate_id DESC"); foreach($rows as $c): ?><tr><td>CERT-<?= (int)$c['certificate_id'] ?></td><td><?= tmq_escape($c['first_name'].' '.$c['last_name']) ?></td><td><?= tmq_escape($c['email']) ?></td><td><?= tmq_escape($c['certificate_type'] ?? $c['certificate_name']) ?></td><td><?= tmq_escape($c['origin'] ?? '') ?></td><td><?= (int)$c['certificate_year'] ?></td><td><?= tmq_escape($c['submitted_at']) ?></td><td><span class="portal-badge pending"><?= tmq_escape($c['status']) ?></span></td><td><a class="btn btn-outline btn-sm" href="manage-certificates-view.php?id=<?= (int)$c['certificate_id'] ?>">Review</a></td></tr><?php endforeach; if(!$rows): ?><tr><td colspan="9">No certificate submissions.</td></tr><?php endif; ?></tbody></table>
</div>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
