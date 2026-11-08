<?php
require_once __DIR__.'/../includes/config.php';
require __DIR__.'/../includes/chat-page.php';
$assetPrefix='../'; $pageTitle='Booking conversation - TutorMeQuran';
$pageDescription='Private booking conversation'; $homeFooter=false; $tutorApplicant=false;
$footerHelp='tutor-help.php'; $footerFaq='tutor-faq.php';
require __DIR__.'/../includes/header.php';
?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__.'/../includes/tutor-header.php'; ?>
<main id="main-content" class="container portal-main"><?php require __DIR__.'/../includes/chat-content.php'; ?></main>
<?php require __DIR__.'/../includes/footer.php'; ?></body></html>
