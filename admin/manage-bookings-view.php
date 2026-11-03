<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Booking TMQ-1024 - TutorMeQuran';
$pageDescription = 'Manage the appointment and follow its payment review.';
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
<h1>Booking TMQ-1024</h1>
<p>Manage the appointment and follow its payment review.</p>
</div>
<div class="portal-actions"><a href="manage-bookings-edit.php" class="btn btn-primary btn-sm">Edit booking</a><a href="manage-bookings-cancel.php" class="btn btn-outline btn-sm action-danger"><span class="action-warning-icon" aria-hidden="true">⚠</span> Cancel booking</a><a href="manage-bookings.php" class="btn btn-outline btn-sm">Back</a>
</div>
</header>
<section class="booking-progress" aria-label="Booking progress for TMQ-1024" data-progress-booking="TMQ-1024" data-progress-status="session" data-progress-source="session"><ol class="booking-journey"><li class="done"><span class="booking-step" aria-label="Booking: complete"><span class="booking-step-dot" aria-hidden="true">✓</span><span class="booking-step-label">Booking</span></span></li><li class="done"><span class="booking-step" aria-label="Arrange: complete"><span class="booking-step-dot" aria-hidden="true">✓</span><span class="booking-step-label">Arrange</span></span></li><li class="current" aria-current="step"><span class="booking-step" aria-label="Class: current stage"><span class="booking-step-dot" aria-hidden="true">3</span><span class="booking-step-label">Class</span></span></li><li><span class="booking-step" aria-label="Payment: upcoming stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li><span class="booking-step" aria-label="Verified: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Class arranged · confirm completion after the lesson</p></section>
<section class="portal-card ">
<h2>Booking details</h2>
<dl class="portal-details">
<div>
<dt>Booking ID</dt>
<dd>TMQ-1024</dd>
</div>
<div>
<dt>Booked On</dt>
<dd>10 October 2026, 10:15 AM</dd>
</div>
<div>
<dt>Student</dt>
<dd>Ahmad Hakimi · ST-001</dd>
</div>
<div>
<dt>Tutor</dt>
<dd>Muhammad Farhan · TU-001</dd>
</div>
<div>
<dt>Appointment Date</dt>
<dd>20 October 2026</dd>
</div>
<div>
<dt>Start Time</dt>
<dd>8:00 PM (MYT)</dd>
</div>
<div>
<dt>End Time</dt>
<dd>9:00 PM (MYT)</dd>
</div>
<div>
<dt>Class Type</dt>
<dd>Online</dd>
</div>
<div>
<dt>Booking Status</dt>
<dd><span class="portal-badge good">Confirmed</span>
</dd>
</div>
<div>
<dt>Lesson Fee</dt>
<dd>RM 40.00 · 1 hour</dd>
</div>
</dl>
</section>
<section class="portal-card ">
<h2>Payment status</h2>
<dl class="portal-details">
<div>
<dt>Payment ID</dt>
<dd>PAY-1024</dd>
</div>
<div>
<dt>Booking ID</dt>
<dd>TMQ-1024</dd>
</div>
<div>
<dt>Payment Amount</dt>
<dd>RM 40.00</dd>
</div>
<div>
<dt>Payment Date</dt>
<dd>11 October 2026</dd>
</div>
<div>
<dt>Receipt</dt>
<dd>duitnow-TMQ-1024.jpg</dd>
</div>
<div>
<dt>Tutor Confirmation</dt>
<dd><span class="portal-badge pending">Awaiting confirmation</span>
</dd>
</div>
<div>
<dt>Admin Payment Status</dt>
<dd><span class="portal-badge pending">Pending</span>
</dd>
</div>
</dl>
<div class="portal-actions"><a href="manage-payments-view.php" class="btn btn-outline btn-sm">Review payment</a>
</div>
</section>
</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
