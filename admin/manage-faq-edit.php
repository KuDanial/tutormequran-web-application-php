<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Edit FAQ - TutorMeQuran';
$pageDescription = 'Manage your TutorMeQuran activity.';
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
<h1>Edit FAQ</h1>
<p>Manage your TutorMeQuran activity.</p>
</div>
</header>

  <section class="portal-card ">
<h2>FAQ Answer</h2>
<form class="portal-form" aria-label="FAQ Answer">
<button type="submit" hidden disabled aria-hidden="true">
</button>
<div class="portal-fields">
<div class="form-group">
<label class="form-label" for="question-keyword">Question / Keyword</label>
<input id="question-keyword" name="questionKeyword" class="form-control" type="text" value="How to book a tutor?">
</div>
<div class="form-group">
<label class="form-label" for="answer">Answer</label>
<textarea id="answer" name="answer" class="form-control" rows="4">Browse tutors, open a tutor profile, choose an available date and time, then complete the booking form. Your booking details will appear in My Bookings.</textarea>
</div>
<div class="form-group">
<label class="form-label" for="status">Status</label>
<select id="status" name="faqStatus" class="form-control">
<option selected>Active</option>
<option>Inactive</option>
</select>
</div>
</div>
<div class="portal-actions">
<button type="button" class="btn btn-primary">Save Changes</button><a class="btn btn-outline btn-sm" href="manage-faq-view.php">Back</a>
</div>
</form>
</section>

</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
