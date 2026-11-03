<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Help Centre - TutorMeQuran';
$pageDescription = 'Help Centre for your TutorMeQuran account.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main student-hub">
<header class="portal-heading"><div><span class="section-subtitle">Your learning journey</span><h1>Help Centre</h1><p>Choose how you would like us to help.</p></div></header><div class="student-help-choices">
<a class="student-choice" href="student-support-request.php"><h2>Submit a request</h2><p>Billing, refund questions, attendance or another issue.</p><span>Start a request →</span></a>
<a class="student-choice" href="student-contact.php"><h2>Contact Us</h2><p>Get in touch through WhatsApp or email.</p><span>View contact options →</span></a>
</div><p class="student-help-link">Looking for a quick answer? <a href="student-faq.php">Read the FAQ</a>.</p>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
