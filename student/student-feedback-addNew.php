<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Reflect on your lesson - TutorMeQuran';
$pageDescription = 'One review per completed booking helps other students choose.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Your learning journey</span>
<h1>Reflect on your lesson</h1>
<p>One review per completed booking helps other students choose.</p>
</div>
</header>
<section class="portal-card ">
<h2>Lesson feedback</h2>
<aside class="flow-notice info"><strong>Review a completed lesson</strong>
<p>TMQ-1008 already has your feedback. Only the remaining completed booking is shown here.</p>
</aside>
<form class="portal-form">
<button type="submit" hidden disabled aria-hidden="true">
</button>
<div class="portal-fields">
<div class="form-group">
<label for="f-bookingID" class="form-label">Completed Booking</label>
<select id="f-bookingID" name="bookingID" class="form-control">
<option selected>TMQ-0992 · Muhammad Farhan · 29 Sep 2026</option>
</select>
</div>
<div class="form-group">
<label for="f-rating" class="form-label">Rating</label>
<select id="f-rating" name="rating" class="form-control">
<option selected>5 — Excellent</option>
<option>4 — Good</option>
<option>3 — Satisfactory</option>
<option>2 — Fair</option>
<option>1 — Poor</option>
</select>
</div>
<div class="form-group">
<label for="f-comments" class="form-label">Comments</label>
<textarea id="f-comments" name="comments" class="form-control" rows="4">
</textarea>
</div>
</div>
<div class="portal-actions">
<button type="button" class="btn btn-primary">Submit Feedback</button><a href="student-feedbacks.php" class="btn btn-outline btn-sm">Back</a>
</div>
</form>
</section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
