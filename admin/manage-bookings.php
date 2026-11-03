<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Booking management - TutorMeQuran';
$pageDescription = 'Oversee student and tutor appointments.';
$currentPage = 'manage-bookings.php';
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
<h1>Booking management</h1>
<p>Oversee student and tutor appointments.</p>
</div>
</header>
<div class="portal-table-wrap" role="region" aria-label="System bookings" tabindex="0">
<table class="portal-table">
<caption>System bookings</caption>
<thead>
<tr>
<th scope="col">Booking ID</th>
<th scope="col">Student</th>
<th scope="col">Tutor</th>
<th scope="col">Date</th>
<th scope="col">Time</th>
<th scope="col">Class Type</th>
<th scope="col">Booking Status</th>
<th scope="col">Payment Status</th>
<th scope="col">Action</th>
</tr>
</thead>
<tbody>
<tr>
<td>TMQ-1024</td>
<td>Ahmad Hakimi</td>
<td>Muhammad Farhan</td>
<td>20 Oct 2026</td>
<td>8:00 PM – 9:00 PM</td>
<td>Online</td>
<td><span class="portal-badge good">Confirmed</span>
</td>
<td><span class="portal-badge pending">Pending</span>
</td>
<td><a href="manage-bookings-view.php" class="btn btn-outline btn-sm">View</a>
</td>
</tr>
</tbody>
</table>
</div>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
