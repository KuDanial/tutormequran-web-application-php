<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Contact Us - TutorMeQuran';
$pageDescription = 'Coordinate the lesson with your student, then confirm completion before payment.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main student-hub">
<header class="portal-heading"><div><span class="section-subtitle">Tutor studio</span><h1>Contact Us</h1><p>Choose WhatsApp or email to get in touch.</p></div></header><section class="portal-card"><div class="student-list">
<article class="student-list-item"><div><h2>Chat on WhatsApp</h2><p>+60 16-262 4834</p></div><div class="student-list-action"><span class="portal-badge">WhatsApp</span><a class="btn btn-primary btn-sm" href="https://wa.me/60162624834" target="_blank" rel="noopener noreferrer">Open WhatsApp</a></div></article>
<article class="student-list-item"><div><h2>Email</h2><p>support@tutormequran.com.my</p></div><div class="student-list-action"><span class="portal-badge">Email</span><a class="btn btn-outline btn-sm" href="mailto:support@tutormequran.com.my">Write an email</a></div></article>
</div><p class="field-help">Include your booking ID if your question is about a lesson or payment. These are the contact details currently shown on this prototype.</p></section><a class="btn btn-outline btn-sm" href="tutor-help.php">Back to Help Centre</a>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
