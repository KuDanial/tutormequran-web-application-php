<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Edit booking TMQ-1024 - TutorMeQuran';
$pageDescription = 'Coordinate changes with both the student and tutor.';
$currentPage = '';
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
<h1>Edit booking TMQ-1024</h1>
<p>Coordinate changes with both the student and tutor.</p>
</div>
</header>
<section class="portal-card ">
<h2>Booking details</h2>
<form class="portal-form">
<button type="submit" hidden disabled aria-hidden="true">
</button>
<div class="portal-fields">
<div class="form-group">
<label for="f-appointmentDate" class="form-label">Appointment Date</label>
<input id="f-appointmentDate" name="appointmentDate" class="form-control" type="date" value="2026-10-20">
</div>
<div class="form-group">
<label for="f-startTime" class="form-label">Start Time</label>
<input id="f-startTime" name="startTime" class="form-control" type="time" value="20:00">
</div>
<div class="form-group">
<label for="f-endTime" class="form-label">End Time</label>
<input id="f-endTime" name="endTime" class="form-control" type="time" value="21:00">
</div>
<div class="form-group">
<label for="f-classType" class="form-label">Class Type</label>
<select id="f-classType" name="classType" class="form-control">
<option selected>Online</option>
<option>Physical</option>
</select>
</div>
<div class="form-group">
<label for="f-bookingNotes" class="form-label">Lesson Notes</label>
<textarea id="f-bookingNotes" name="bookingNotes" class="form-control" rows="4">Focus on Tajweed and pronunciation.</textarea>
</div>
</div>
<div class="portal-actions">
<button type="button" class="btn btn-primary">Save Changes</button><a href="manage-bookings-view.php" class="btn btn-outline btn-sm">Back</a>
</div>
</form>
</section>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
