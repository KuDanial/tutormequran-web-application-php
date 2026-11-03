<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Submit a request - TutorMeQuran';
$pageDescription = 'Submit a request for your TutorMeQuran account.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main student-hub">
<header class="portal-heading"><div><span class="section-subtitle">Your learning journey</span><h1>Submit a request</h1><p>Tell us what happened so your request can be reviewed.</p></div></header>
<section class="portal-card"><form class="portal-form" data-support-request method="post">
<div class="form-group"><label class="form-label" for="request-category">What do you need help with?</label><select class="form-control" id="request-category" name="category" required>
<option value="">Choose a topic</option><option value="billing">Billing or refund</option><option value="attendance">Attendance issue</option><option value="other">Something else</option></select></div>
<p class="field-help" data-request-guidance>Choose the topic that best describes your request.</p>
<div class="form-group"><label class="form-label" for="request-booking">Booking ID <span class="field-help">(optional)</span></label><input class="form-control" id="request-booking" name="booking" type="text" maxlength="40" placeholder="For example, TMQ-1024"></div>
<div class="form-group"><label class="form-label" for="request-description">Describe your request</label><textarea class="form-control" id="request-description" name="description" required rows="5" maxlength="3000" placeholder="Tell us what happened and what you need help with."></textarea></div>
<div class="form-group"><label class="form-label" for="request-photo">Add a photo <span class="field-help">(optional)</span></label><input class="form-control" id="request-photo" name="photo" type="file" accept="image/jpeg,image/png" aria-describedby="request-photo-help"><small id="request-photo-help">One JPG or PNG, up to 5 MB.</small></div>
<div class="student-attachment" data-attachment hidden><img alt="Selected support photo preview" data-attachment-image><button type="button" class="btn btn-text btn-sm action-danger" data-remove-attachment><span class="action-warning-icon" aria-hidden="true">⚠</span> Remove photo</button></div>
<div class="portal-actions"><button type="submit" class="btn btn-primary">Submit request</button><a class="btn btn-outline btn-sm" href="student-help.php">Back to Help Centre</a></div>
<p class="field-help">Prototype: this form previews your request. It does not send a message or upload your photo.</p>
<noscript><p>Enable JavaScript to preview your request.</p></noscript>
</form><div class="student-request-result" data-request-result role="status" hidden></div></section>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
