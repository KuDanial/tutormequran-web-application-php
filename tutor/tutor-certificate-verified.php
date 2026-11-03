<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/profile-page.php';
$assetPrefix = '../';
$pageTitle = 'Your verified certificate - TutorMeQuran';
$pageDescription = 'Your TutorMeQuran account and profile information.';
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
<h1>Your verified certificate</h1>
<p>Your saved account and profile information.</p>
</div><a href="tutor-dashboard-verified.php" class="btn btn-outline btn-sm">Back to studio</a>
</header>
<?php require __DIR__ . '/../includes/certificate-panel.php'; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
