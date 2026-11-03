<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Cancel booking - TutorMeQuran';
$pageDescription = 'Review the lesson and explain the cancellation.';
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
<h1>Cancel booking</h1>
<p>Review the lesson and explain the cancellation.</p>
</div>
</header>
<section class="booking-progress" aria-label="Booking progress for TMQ-1024" data-progress-booking="TMQ-1024" data-progress-status="requested" data-progress-source="session"><ol class="booking-journey"><li class="current" aria-current="step"><span class="booking-step" aria-label="Booking: current stage"><span class="booking-step-dot" aria-hidden="true">1</span><span class="booking-step-label">Booking</span></span></li><li class=""><span class="booking-step" aria-label="Arrange: upcoming stage"><span class="booking-step-dot" aria-hidden="true">2</span><span class="booking-step-label">Arrange</span></span></li><li class=""><span class="booking-step" aria-label="Class: upcoming stage"><span class="booking-step-dot" aria-hidden="true">3</span><span class="booking-step-label">Class</span></span></li><li class=""><span class="booking-step" aria-label="Payment: upcoming stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li class=""><span class="booking-step" aria-label="Verified: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Waiting for tutor acceptance</p></section>
<section class="portal-card ">
<h2>Booking summary</h2>
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
<h2>Reason for cancellation</h2>
<form class="portal-form">
<button type="submit" hidden disabled aria-hidden="true">
</button>
<div class="portal-fields">
<div class="form-group">
<label for="f-cancellationReason" class="form-label">Cancellation Reason</label>
<textarea id="f-cancellationReason" name="cancellationReason" class="form-control" rows="4">
</textarea>
</div>
</div>
<div class="portal-actions">
<button type="button" class="btn action-danger" data-confirm="Cancel this booking? This releases the lesson slot. Any payment or refund issue must be reviewed separately."><span class="action-warning-icon" aria-hidden="true">⚠</span> Confirm Cancellation</button><a href="tutor-booking-view.php" class="btn btn-outline btn-sm">Back</a>
</div>
</form>
</section>
<aside class="flow-notice warm"><strong>Payment follow-up</strong>
<p>Cancelling a booking does not automatically refund a transfer. Any payment issue needs admin review.</p>
</aside><section class="portal-card"><h2>Cancellation policy</h2><ul class="clean-list"><li>If Student cancels early, booking status becomes Cancelled by Student.</li><li>If Tutor cancels early, booking status becomes Cancelled by Tutor.</li><li>If cancellation happens on the booking day, it should be marked as Late Cancellation.</li><li>Admin can review dispute cases if payment issue happens.</li></ul><p class="field-help">Early means before the booking date; the day before is early. On the booking date (Malaysia time), record Late Cancellation and who cancelled. No automatic refund or cancellation fee is applied by this interface.</p></section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
