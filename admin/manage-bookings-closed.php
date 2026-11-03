<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Booking verified & closed - TutorMeQuran';
$pageDescription = 'TMQ-1024 · the lesson and payment review are complete.';
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
<main id="main-content" class="container portal-main booking-simple">
<header class="portal-heading"><div><span class="section-subtitle">Your lesson</span><h1>Booking verified & closed</h1><p>TMQ-1024 · the lesson and payment review are complete.</p></div></header>

<nav class="booking-progress" aria-label="Booking progress"><ol class="booking-journey"><li class="done"><a href="../student/student-booking-view.php" aria-label="Booked: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Booked</span></a></li><li class="done"><a href="../student/student-booking-coordinate.php" aria-label="Arrange: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Arrange</span></a></li><li class="done"><a href="../student/student-booking-session.php" aria-label="Class: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Class</span></a></li><li class="done"><a href="../student/student-payment-upload.php" aria-label="Payment: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Payment</span></a></li><li class="done" aria-current="step"><a href="../student/student-payment-verified.php" aria-label="Done: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Done</span></a></li></ol><p class="booking-current-label">Verified and closed</p></nav><div class="booking-quick-summary"><span><strong>TMQ-1024</strong></span><span>20 Oct · 8–9 PM</span><span>RM 40.00</span></div>
<aside class="flow-notice info"><strong>Final review complete</strong><p>The session was completed, the tutor acknowledged RM 40.00, and admin verified the receipt. This booking is now closed.</p></aside><section class="portal-card"><h2>Closure record</h2><details class="booking-record"><summary>View booking details</summary><dl class="portal-details"><div><dt>Booking</dt><dd>TMQ-1024</dd></div><div><dt>Student</dt><dd>Ahmad Hakimi</dd></div><div><dt>Tutor</dt><dd>Ustaz Muhammad Farhan</dd></div><div><dt>Lesson</dt><dd>20 October 2026 · 8:00–9:00 PM MYT</dd></div><div><dt>Focus</dt><dd>Quran recitation & Tajweed</dd></div><div><dt>Lesson fee</dt><dd>RM 40.00 · 1 hour</dd></div></dl></details><details class="booking-record"><summary>View booking details</summary><dl class="portal-details"><div><dt>Session completion</dt><dd>Confirmed by student and tutor</dd></div><div><dt>Payment</dt><dd>PAY-1024 · RM 40.00 · Verified</dd></div><div><dt>Tutor acknowledgement</dt><dd>20 October 2026 · 9:20 PM MYT</dd></div><div><dt>Admin verification</dt><dd>21 October 2026 · 10:00 AM MYT</dd></div><div><dt>Booking status</dt><dd>Closed</dd></div></dl></details><div class="portal-actions"><a href="manage-bookings.php" class="btn btn-outline btn-sm">All bookings</a></div></section>

</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
