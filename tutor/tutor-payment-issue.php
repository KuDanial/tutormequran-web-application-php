<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Report a payment issue - TutorMeQuran';
$pageDescription = 'Send the payment mismatch for admin review.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main booking-simple">
<header class="portal-heading">
<div><span class="section-subtitle">Tutor studio</span>
<h1>Report a payment issue</h1>
<p>Send the payment mismatch for admin review.</p>
</div>
</header><section class="booking-progress" aria-label="Booking process" data-progress-booking="TMQ-1024" data-progress-status="issue" data-progress-source="page"><ol class="booking-journey"><li class="done"><span class="booking-step" aria-label="Booking: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Booking</span></span></li><li class="done"><span class="booking-step" aria-label="Arrange: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Arrange</span></span></li><li class="done"><span class="booking-step" aria-label="Class: complete"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Class</span></span></li><li class="current" aria-current="step"><span class="booking-step" aria-label="Payment: current stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li class=""><span class="booking-step" aria-label="Verified: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Payment issue - review required</p></section><div class="booking-quick-summary"><span><strong>TMQ-1024</strong></span><span>20 Oct · 8–9 PM</span><span>RM 40.00</span></div>
<section class="portal-card ">
<h2>Payment issue</h2>
<form class="portal-form" data-payment-issue>
<div class="portal-fields">
<div class="form-group">
<label for="f-tutorPaymentIssue" class="form-label">Issue</label>
<select id="f-tutorPaymentIssue" name="tutorPaymentIssue" class="form-control">
<option selected>Payment not received</option>
<option>Amount does not match</option>
<option>Receipt reference does not match</option>
</select>
</div>
<div class="form-group">
<label for="f-tutorPaymentRemark" class="form-label">Explanation</label>
<textarea id="f-tutorPaymentRemark" name="tutorPaymentRemark" class="form-control" rows="4" required maxlength="1000">
</textarea>
</div>
</div>
<div class="portal-actions">
<button type="submit" class="btn btn-primary">Report Issue</button><a href="tutor-payment-view.php" class="btn btn-outline btn-sm">Back</a>
</div>
<p data-workflow-error role="status"></p></form>
</section>
<aside class="flow-notice info"><strong>Payment remains pending</strong>
<p>An issue requires admin review. Do not request another transfer until the payment has been checked.</p>
</aside>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
