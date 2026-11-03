<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Your lesson feedback - TutorMeQuran';
$pageDescription = 'Share what helped you learn.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Your learning journey</span>
<h1>Your lesson feedback</h1>
<p>Share what helped you learn.</p>
</div><a href="student-feedback-addNew.php" class="btn btn-primary btn-sm">Add feedback</a>
</header>
<div class="portal-table-wrap" role="region" aria-label="Submitted feedback" tabindex="0">
<table class="portal-table">
<caption>Submitted feedback</caption>
<thead>
<tr>
<th scope="col">Feedback ID</th>
<th scope="col">Booking ID</th>
<th scope="col">Tutor Name</th>
<th scope="col">Rating</th>
<th scope="col">Comment Preview</th>
<th scope="col">Date</th>
<th scope="col">Action</th>
</tr>
</thead>
<tbody>
<tr>
<td>FB-001</td>
<td>TMQ-1008</td>
<td>Muhammad Farhan</td>
<td>5 / 5</td>
<td>Clear explanations and patient guidance.</td>
<td>6 Oct 2026</td>
<td><a href="#feedback-detail" class="btn btn-outline btn-sm">View</a>
</td>
</tr>
</tbody>
</table>
</div>
<section id="feedback-detail" class="portal-card">
<h2>Feedback FB-001</h2>
<dl class="portal-details">
<div>
<dt>Booking</dt>
<dd>TMQ-1008</dd>
</div>
<div>
<dt>Rating</dt>
<dd>5 / 5</dd>
</div>
<div>
<dt>Comments</dt>
<dd>Clear explanations and patient guidance. I feel more confident applying Tajweed rules.</dd>
</div>
<div>
<dt>Feedback Date</dt>
<dd>6 October 2026</dd>
</div>
</dl>
</section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
