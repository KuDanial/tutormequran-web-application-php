<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/faq-public.php';
$assetPrefix = '../';
$pageTitle = 'FAQ - TutorMeQuran';
$pageDescription = 'FAQ for your TutorMeQuran account.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main student-hub">
<header class="portal-heading"><div><span class="section-subtitle">Your learning journey</span><h1>FAQ</h1><p>Quick answers about your TutorMeQuran account.</p></div></header><?php require __DIR__ . '/../includes/faq-accordion.php'; ?><div class="portal-actions"><a class="btn btn-outline btn-sm" href="student-help.php">Visit Help Centre</a><a class="btn btn-outline btn-sm" href="../chatbot.php">Open FAQ Chat</a></div>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
