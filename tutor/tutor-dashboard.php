<?php
require_once __DIR__ . '/../includes/config.php';
$query = tmq_db()->prepare('SELECT tutor_id, application_submitted_at FROM tutors WHERE user_id=?');
$query->execute([$authUser['user_id']]);
$tutorAccount = $query->fetch();
$query = tmq_db()->prepare('SELECT COUNT(*) FROM certificates WHERE tutor_id=?');
$query->execute([$tutorAccount['tutor_id']]);
$hasCertificate = (int)$query->fetchColumn() > 0;
$assetPrefix = '../';
$pageTitle = 'Tutor account - TutorMeQuran';
$pageDescription = 'Your tutor account and application progress.';
$tutorApplicant = true;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Tutor studio</span>
<h1><?= $hasCertificate ? 'Your tutor application' : 'Complete your tutor application' ?></h1>
<p>Assalamualaikum, <?= tmq_escape($authUser['first_name']) ?>. Your account is ready.<?= $hasCertificate ? ' View your certificate status for the next step.' : ' Submit your certificate and complete your teaching profile.' ?></p>
</div><a href="tutor-account-edit.php" class="btn btn-outline btn-sm">Update personal details</a>
</header>
<section class="booking-progress application-progress" aria-label="Tutor application progress">
<ol class="booking-journey">
<li class="<?= $hasCertificate ? 'done' : 'current' ?>"><span class="booking-step"><span class="booking-step-dot" aria-hidden="true">&#10003;</span><span class="booking-step-label">Apply</span></span></li>
<li class="<?= $hasCertificate ? 'current' : '' ?>"><span class="booking-step"><span class="booking-step-dot" aria-hidden="true">2</span><span class="booking-step-label">Admin verification</span></span></li>
</ol>
<p class="booking-current-label"><?= $hasCertificate ? 'View your certificate status' : 'Account created ? certificate not submitted' ?></p>
</section>
<aside class="flow-notice warm"><strong><?= $hasCertificate ? 'Tutor account' : 'Application incomplete' ?></strong>
<p>Your account is ready. Teaching tools will open once an admin verifies your certificate. No approval is granted by account registration.</p>
</aside>
<div>
<section class="portal-card ">
<h2>Application TU-<?= tmq_escape((string)$tutorAccount['tutor_id']) ?></h2>
<dl class="portal-details">
<div>
<dt>Submitted</dt>
<dd><?= tmq_escape(substr($tutorAccount['application_submitted_at'], 0, 10)) ?></dd>
</div>
<div>
<dt>Certificate</dt>
<dd><?= $hasCertificate ? 'View certificate status' : 'Not submitted yet' ?></dd>
</div>
<div>
<dt>Status</dt>
<dd><span class="portal-badge pending"><?= $hasCertificate ? 'View certificate status' : 'Application incomplete' ?></span>
</dd>
</div>
<div>
<dt>Public Listing</dt>
<dd>Not listed</dd>
</div>
</dl>
<div class="portal-actions"><a href="tutor-certificate-view.php" class="btn btn-primary btn-sm">View application</a>
</div>
</section>

</div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
