<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Payment reviews - TutorMeQuran';
$pageDescription = 'Tutor acknowledgement supports your review; you record the final payment status.';
$currentPage = 'manage-payments.php';
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
<h1>Payment reviews</h1>
<p>Tutor acknowledgement supports your review; you record the final payment status.</p>
</div>
</header>
<div class="portal-table-wrap" role="region" aria-label="DuitNow submissions" tabindex="0">
<table class="portal-table">
<caption>DuitNow submissions</caption>
<thead>
<tr>
<th scope="col">Payment ID</th>
<th scope="col">Booking ID</th>
<th scope="col">Student Name</th>
<th scope="col">Tutor Name</th>
<th scope="col">Amount</th>
<th scope="col">Uploaded Date</th>
<th scope="col">Tutor Confirmation</th>
<th scope="col">Status</th>
<th scope="col">Action</th>
</tr>
</thead>
<tbody>
<tr>
<td>PAY-1024</td>
<td>TMQ-1024</td>
<td>Ahmad Hakimi</td>
<td>Muhammad Farhan</td>
<td>RM 40.00</td>
<td>11 Oct 2026</td>
<td><span class="portal-badge pending">Awaiting confirmation</span>
</td>
<td><span class="portal-badge pending">Pending</span>
</td>
<td><a href="manage-payments-view.php" class="btn btn-outline btn-sm">Review</a>
</td>
</tr>
</tbody>
</table>
</div>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
