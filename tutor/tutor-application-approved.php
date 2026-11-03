<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/profile-page.php';
$assetPrefix = '../';
$pageTitle = 'Your certificate is approved - TutorMeQuran';
$pageDescription = 'Your TutorMeQuran account and profile information.';
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
<h1>Your tutor application</h1>
<p>Your saved account and profile information.</p>
</div>
<div class="portal-actions"><a href="tutor-profile-edit.php" class="btn btn-primary btn-sm">Edit my profile</a><a href="tutor-dashboard.php" class="btn btn-outline btn-sm">View application status</a>
</div>
</header><?php tmq_profile_notice(); ?><section class="portal-card"><h2>Application status</h2><p><?= tmq_escape($applicationStatus) ?></p><p><?= tmq_escape((string)($certificateHistory[0]['admin_remark'] ?? 'No review remark recorded.')) ?></p><p>Approved qualifications remain valid when a later submission is pending or rejected.</p><div class="portal-actions"><a class="btn btn-primary btn-sm" href="tutor-certificate-view.php">View certificate history</a><a class="btn btn-outline btn-sm" href="tutor-profile-setup.php">Teaching profile</a></div></section></main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
