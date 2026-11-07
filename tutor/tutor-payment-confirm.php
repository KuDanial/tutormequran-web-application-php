<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/payment-review-page.php';
$assetPrefix = '../';
$pageTitle = 'Payment records - TutorMeQuran';
$pageDescription = 'Payment evidence, review and settlement history.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main student-hub tutor-hub">
<?php require __DIR__ . '/../includes/payment-review-content.php'; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?></body></html>
