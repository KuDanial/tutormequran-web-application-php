<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/feedback-page.php';
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
<?php require __DIR__ . '/../includes/feedback-content.php'; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
