<?php
require_once __DIR__ . '/../includes/config.php';
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
<tr>
<td>How to book a tutor?</td>
<td>Browse tutors, open a profile and choose an available slot…</td>
<td><span class="portal-badge good">Active</span>
</td>
<td>
<div class="portal-actions"><a class="btn btn-outline btn-sm" href="manage-faq-view.php">View</a><a class="btn btn-outline btn-sm" href="manage-faq-edit.php">Edit</a><button type="button" class="btn btn-danger btn-sm" data-delete-row data-confirm="Delete this FAQ question? This cannot be undone."><i class="fas fa-trash" aria-hidden="true"></i> Delete</button>
</div>
</td>
</tr>
</tbody>
</table>
</div>

</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script><script src="../assets/js/admin-crud.js"></script></body></html>
