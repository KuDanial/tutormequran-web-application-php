<?php
require_once __DIR__ . '/../includes/config.php';
$faqMode = 'list';
require __DIR__ . '/../includes/faq-admin.php';
$assetPrefix = '../';
$pageTitle = 'Manage FAQ - TutorMeQuran';
$pageDescription = 'Manage your TutorMeQuran activity.';
$currentPage = 'manage-faq.php';
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
<h1>Manage FAQ</h1>
<p>Manage your TutorMeQuran activity.</p>
</div>
<div class="portal-actions"><a class="btn btn-primary btn-sm" href="manage-faq-addNew.php">Add New FAQ</a>
</div>
</header>

  <div class="portal-table-wrap" role="region" aria-label="FAQ answers" tabindex="0">
<table class="portal-table">
<caption>FAQ answers</caption>
<thead>
<tr>
<th scope="col">Keyword / Question</th>
<th scope="col">Answer Preview</th>
<th scope="col">Status</th>
<th scope="col">Action</th>
</tr>
</thead>
<tbody>
<?php foreach (array_slice($faqRows, 0, 50) as $faq): ?>
<tr data-faq-id="<?= (int)$faq['faq_id'] ?>">
<td><?= tmq_escape($faq['question']) ?></td>
<td><?= tmq_escape(mb_substr($faq['answer'], 0, 150, 'UTF-8')) ?></td>
<td><span class="portal-badge <?= $faq['status'] === 'active' ? 'good' : '' ?>"><?= tmq_escape(ucfirst($faq['status'])) ?></span></td>
<td><div class="portal-actions"><a class="btn btn-outline btn-sm" href="manage-faq-view.php?id=<?= (int)$faq['faq_id'] ?>">View</a><a class="btn btn-outline btn-sm" href="manage-faq-edit.php?id=<?= (int)$faq['faq_id'] ?>">Edit</a></div></td>
</tr>
<?php endforeach; ?>
<?php if (!$faqRows): ?><tr><td colspan="4">No FAQs are available.</td></tr><?php endif; ?>
</tbody>
</table>
</div>

<?php if (count($faqRows) > 50): ?><a class="btn btn-outline btn-sm" href="?after=<?= (int)$faqRows[49]['faq_id'] ?>">More FAQs</a><?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
