<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Edit booking - TutorMeQuran';
$pageDescription = 'TMQ-1024 · coordinate changes with your student.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Tutor studio</span>
<h1>Edit booking</h1>
<p>TMQ-1024 · coordinate changes with your student.</p>
</div>
</header>
<section class="booking-progress" aria-label="Booking progress for TMQ-1024" data-progress-booking="TMQ-1024" data-progress-status="requested" data-progress-source="session"><ol class="booking-journey"><li class="current" aria-current="step"><span class="booking-step" aria-label="Booking: current stage"><span class="booking-step-dot" aria-hidden="true">1</span><span class="booking-step-label">Booking</span></span></li><li class=""><span class="booking-step" aria-label="Arrange: upcoming stage"><span class="booking-step-dot" aria-hidden="true">2</span><span class="booking-step-label">Arrange</span></span></li><li class=""><span class="booking-step" aria-label="Class: upcoming stage"><span class="booking-step-dot" aria-hidden="true">3</span><span class="booking-step-label">Class</span></span></li><li class=""><span class="booking-step" aria-label="Payment: upcoming stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li class=""><span class="booking-step" aria-label="Verified: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Waiting for tutor acceptance</p></section>
<section class="portal-card ">
<h2>Lesson details</h2>
<aside class="flow-notice info"><strong>Review schedule changes</strong>
<p>An edited slot must be available. Confirm time and mode changes with your student.</p>
</aside>
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
<button type="button" class="btn btn-primary">Save Changes</button><a href="tutor-booking-view.php" class="btn btn-outline btn-sm">Back</a>
</div>
</form>
</section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
