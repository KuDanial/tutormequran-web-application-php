<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/profiles.php';
try {
    $tutorAccount = tmq_own_tutor((int)$authUser['user_id']);
    $query = tmq_db()->prepare('SELECT COUNT(*) FROM certificates WHERE tutor_id=?');
    $query->execute([$tutorAccount['tutor_id']]);
    $hasCertificate = (int)$query->fetchColumn() > 0;
} catch (Throwable $error) {
    http_response_code(503); exit('Tutor account is temporarily unavailable. Please try again later.');
}
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
<p class="booking-current-label"><?= $hasCertificate ? 'View your certificate status' : 'Account created — certificate not submitted' ?></p>
</section>
<aside class="flow-notice warm"><strong><?= $hasCertificate ? 'Tutor account' : 'Application incomplete' ?></strong>
<p>You can manage your profile and availability. Public listing requires an approved displayed certificate, a complete profile and Public visibility. No approval is granted by account registration.</p>
</aside>
<div>
<section class="portal-card ">
<h2>Application TU-<?= tmq_escape((string)$tutorAccount['tutor_id']) ?></h2>
<dl class="portal-details">
<div>
<dt>Submitted</dt>
<dd><?= tmq_escape($tutorAccount['application_submitted_at'] ? (new DateTimeImmutable($tutorAccount['application_submitted_at'], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))->format('d M Y') : 'Not submitted yet') ?></dd>
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
<dd><?= tmq_public_tutor($tutorAccount) ? 'Listed publicly' : 'Not listed publicly' ?></dd>
</div>
</dl>
<div class="portal-actions"><a href="tutor-certificate-view.php" class="btn btn-primary btn-sm">View application</a>
</div>
</section>

</div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
