<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Edit your booking - TutorMeQuran';
$pageDescription = 'TMQ-1024 · confirm changes with your tutor.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Your learning journey</span>
<h1>Edit your booking</h1>
<p>TMQ-1024 · confirm changes with your tutor.</p>
</div>
</header>
<section class="booking-progress" aria-label="Booking progress for TMQ-1024" data-progress-booking="TMQ-1024" data-progress-status="requested" data-progress-source="session"><ol class="booking-journey"><li class="current" aria-current="step"><span class="booking-step" aria-label="Booking: current stage"><span class="booking-step-dot" aria-hidden="true">1</span><span class="booking-step-label">Booking</span></span></li><li class=""><span class="booking-step" aria-label="Arrange: upcoming stage"><span class="booking-step-dot" aria-hidden="true">2</span><span class="booking-step-label">Arrange</span></span></li><li class=""><span class="booking-step" aria-label="Class: upcoming stage"><span class="booking-step-dot" aria-hidden="true">3</span><span class="booking-step-label">Class</span></span></li><li class=""><span class="booking-step" aria-label="Payment: upcoming stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li class=""><span class="booking-step" aria-label="Verified: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Waiting for tutor acceptance</p></section>
<section class="portal-card ">
<h2>Lesson details</h2>
<form class="portal-form">
<button type="submit" hidden disabled aria-hidden="true">
</button>
<div class="portal-fields">
<div class="form-group">
<label for="f-appointmentDate" class="form-label">Appointment Date</label>
<div class="locked-field"><i class="fas fa-lock" aria-hidden="true"></i><input id="f-appointmentDate" class="form-control" type="date" value="2026-10-20" disabled aria-describedby="appointment-date-help"><input type="hidden" name="appointmentDate" value="2026-10-20"></div>
<p id="appointment-date-help" class="field-help">This date is fixed by your tutor’s availability and cannot be changed.</p>
</div>
<div class="form-group">
<label for="f-startTime" class="form-label">Start Time</label>
<div class="locked-field"><i class="fas fa-lock" aria-hidden="true"></i><input id="f-startTime" name="startTime" class="form-control" type="time" value="20:00" readonly aria-describedby="slot-help"></div>
</div>
<div class="form-group">
<label for="f-endTime" class="form-label">End Time</label>
<div class="locked-field"><i class="fas fa-lock" aria-hidden="true"></i><input id="f-endTime" name="endTime" class="form-control" type="time" value="21:00" readonly aria-describedby="slot-help"></div>
</div>
<div class="form-group">
<fieldset class="lesson-type-picker" aria-describedby="class-type-help"><legend class="form-label">Lesson Type</legend><div class="lesson-type-options"><label class="lesson-type-option selected"><input type="radio" name="classType" value="Online" checked><span><i class="fas fa-video" aria-hidden="true"></i><strong>Online</strong><small>Meet remotely</small></span></label><label class="lesson-type-option"><input type="radio" name="classType" value="Physical"><span><i class="fas fa-location-dot" aria-hidden="true"></i><strong>Physical</strong><small>Meet in person</small></span></label></div><p id="class-type-help" class="field-help">Only the lesson type can be changed. Confirm the new arrangement with your tutor.</p></fieldset>
</div>
<div class="form-group form-group-wide"><span class="form-label">Selected lesson slot</span><div class="slot-btn lesson-slot-option selected slot-read-only"><input type="radio" checked disabled aria-label="Selected lesson slot: 20 October 2026, 8 to 9 PM MYT"><span class="slot-day">20 Oct</span><span class="slot-time">8:00 PM – 9:00 PM</span><span class="slot-status-badge">Selected</span></div><p id="slot-help" class="field-help">This time slot was selected from your tutor’s availability and is read-only.</p></div>
<div class="form-group">
<label for="f-bookingNotes" class="form-label">Lesson Notes</label>
<textarea id="f-bookingNotes" name="bookingNotes" class="form-control" rows="4" readonly>Focus on Tajweed and pronunciation.</textarea>
</div>
</div>
<div class="portal-actions">
<button type="button" class="btn btn-primary">Save Changes</button><a href="student-booking-view.php" class="btn btn-outline btn-sm">Back</a>
</div>
</form>
</section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
