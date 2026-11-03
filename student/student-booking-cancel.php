<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Cancel Booking - TutorMeQuran';
$pageDescription = 'Manage your TutorMeQuran activity.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Your learning journey</span>
<h1>Cancel Booking</h1>
<p>Manage your TutorMeQuran activity.</p>
</div>
</header>
<section class="booking-progress" aria-label="Booking progress for TMQ-1024" data-progress-booking="TMQ-1024" data-progress-status="requested" data-progress-source="session"><ol class="booking-journey"><li class="current" aria-current="step"><span class="booking-step" aria-label="Booking: current stage"><span class="booking-step-dot" aria-hidden="true">1</span><span class="booking-step-label">Booking</span></span></li><li class=""><span class="booking-step" aria-label="Arrange: upcoming stage"><span class="booking-step-dot" aria-hidden="true">2</span><span class="booking-step-label">Arrange</span></span></li><li class=""><span class="booking-step" aria-label="Class: upcoming stage"><span class="booking-step-dot" aria-hidden="true">3</span><span class="booking-step-label">Class</span></span></li><li class=""><span class="booking-step" aria-label="Payment: upcoming stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li class=""><span class="booking-step" aria-label="Verified: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Waiting for tutor acceptance</p></section>

  <section class="portal-card ">
<h2>Booking Summary</h2>
<dl class="portal-details">
<div>
<dt>Booking ID</dt>
<dd>TMQ-1024</dd>
</div>
<div>
<dt>Student</dt>
<dd>Ahmad Hakimi</dd>
</div>
<div>
<dt>Tutor</dt>
<dd>Ustaz Muhammad Farhan</dd>
</div>
<div>
<dt>Date</dt>
<dd>20 October 2026</dd>
</div>
<div>
<dt>Time</dt>
<dd>8:00 PM – 9:00 PM (MYT)</dd>
</div>
<div>
<dt>Class Mode</dt>
<dd>Online</dd>
</div>
<div>
<dt>Booking Status</dt>
<dd><span class="portal-badge good">Confirmed</span>
</dd>
</div>
</dl>
</section>
<section class="portal-card ">
<h2>Cancellation Reason</h2><p class="action-warning"><span aria-hidden="true">⚠</span> Cancelling releases the lesson slot. Payment or refund concerns need a separate review.</p>
<p class="portal-muted">Cancelling would release this lesson slot. In this interface demo, the booking will remain unchanged.</p>
<form class="portal-form" aria-label="Cancellation Reason">
<button type="submit" hidden disabled aria-hidden="true">
</button>
<div class="portal-fields">
<div class="form-group">
<label class="form-label" for="reason">Reason</label>
<textarea id="reason" class="form-control" rows="4">
</textarea>
</div>
</div>
<div class="portal-actions">
<button type="button" class="btn btn-danger action-danger" data-confirm="Cancel this booking? This releases the lesson slot. Any payment or refund issue must be reviewed separately."><span class="action-warning-icon" aria-hidden="true">⚠</span> Confirm Cancel</button><a class="btn btn-outline btn-sm" href="student-booking-view.php">Back</a>
</div>
</form>
</section>
<section class="portal-card"><h2>Cancellation policy</h2><ul class="clean-list"><li>If Student cancels early, booking status becomes Cancelled by Student.</li><li>If Tutor cancels early, booking status becomes Cancelled by Tutor.</li><li>If cancellation happens on the booking day, it should be marked as Late Cancellation.</li><li>Admin can review dispute cases if payment issue happens.</li></ul><p class="field-help">Early means before the booking date; the day before is early. On the booking date (Malaysia time), record Late Cancellation and who cancelled. No automatic refund or cancellation fee is applied by this interface.</p></section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
