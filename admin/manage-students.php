<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Manage Students - TutorMeQuran';
$pageDescription = 'Manage your TutorMeQuran activity.';
$currentPage = 'manage-students.php';
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
<h1>Manage Students</h1>
<p>Manage your TutorMeQuran activity.</p>
</div>
</header>

  <div class="portal-table-wrap" role="region" aria-label="Student accounts" tabindex="0">
<table class="portal-table">
<caption>Student accounts</caption>
<thead>
<tr>
<th scope="col">Student Name</th>
<th scope="col">Email</th>
<th scope="col">Phone</th>
<th scope="col">Total Bookings</th>
<th scope="col">Status</th>
<th scope="col">Action</th>
</tr>
</thead>
<tbody>
<tr>
<td>Ahmad Hakimi</td>
<td>ahmad@example.com</td>
<td>012-345 6789</td>
<td>3</td>
<td><span class="portal-badge good">Active</span>
</td>
<td>
<div class="portal-actions"><a class="btn btn-outline btn-sm" href="manage-students-view.php">View</a><a class="btn btn-outline btn-sm" href="manage-students-edit.php">Edit</a><button type="button" class="btn btn-danger btn-sm" data-delete-row data-confirm="Delete student Ahmad Hakimi? This cannot be undone."><i class="fas fa-trash" aria-hidden="true"></i> Delete</button>
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
