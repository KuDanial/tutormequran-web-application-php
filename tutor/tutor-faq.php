<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/faq-public.php';
$assetPrefix = '../';
$pageTitle = 'FAQ - TutorMeQuran';
$pageDescription = 'Coordinate the lesson with your student, then confirm completion before payment.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main student-hub tutor-hub">
<header class="portal-heading"><div><span class="section-subtitle">Tutor studio</span><h1>FAQ</h1><p>Answers about teaching and payments.</p></div></header>
<?php require __DIR__ . '/../includes/faq-accordion.php'; ?><div class="portal-actions"><a class="btn btn-outline btn-sm" href="tutor-help.php">Help Centre</a></div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
