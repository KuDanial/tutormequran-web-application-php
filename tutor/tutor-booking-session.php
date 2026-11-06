<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/booking-page.php';
$assetPrefix = '../';
$pageTitle = 'Lesson session and completion - TutorMeQuran';
$pageDescription = 'Review your lesson and its current lifecycle state.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace tutor-booking-view"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main student-hub tutor-hub">
<?php require __DIR__ . '/../includes/booking-detail-content.php'; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
