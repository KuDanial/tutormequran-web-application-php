<?php
require_once __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/faq-public.php';
$assetPrefix = '';
$pageTitle = 'How can we help you? - TutorMeQuran';
$pageDescription = 'Find answers about learning, tutor applications and DuitNow payments.';
$homeFooter = false;
$footerHelp = 'help.php';
$footerFaq = 'chatbot.php';
?>
<?php require __DIR__ . '/includes/header.php'; ?><body class="portal-page public-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/includes/public-navbar.php'; ?>
<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">TutorMeQuran</span>
<h1>How can we help you?</h1>
<p>Find answers about learning, tutor applications and DuitNow payments.</p>
</div><a href="chatbot.php" class="btn btn-primary btn-sm">Open FAQ Chat</a>
</header>
<?php require __DIR__ . '/includes/faq-accordion.php'; ?>
<section class="portal-card ">
<h2>Need help with a specific booking?</h2>
<p>Include your booking ID when explaining a payment or scheduling issue.</p>
<section class="portal-card ">
<h2>Support request</h2>
<p>Support requests require a Student or Tutor account. Sign in and choose Submit a request in your Help Centre.</p>
<a class="btn btn-primary" href="login.php">Sign in for support</a>
</section>
</section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?></body></html>
