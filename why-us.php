<?php
require_once __DIR__ . '/includes/config.php';
$assetPrefix = '';
$pageTitle = 'Trust at every step of your journey - TutorMeQuran';
$pageDescription = 'A structured way to find qualified Quran tutors, plan lessons and track payments.';
$homeFooter = false;
$footerHelp = 'help.php';
$footerFaq = 'chatbot.php';
?>
<?php require __DIR__ . '/includes/header.php'; ?><body class="portal-page public-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/includes/public-navbar.php'; ?>
<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">TutorMeQuran</span>
<h1>Trust at every step of your journey</h1>
<p>A structured way to find qualified Quran tutors, plan lessons and track payments.</p>
</div>
</header>
<div class="grid grid-3">
<section class="portal-card ">
<h2>Qualifications before listings</h2>
<div class="feature-icon-wrapper flex-center"><i class="fas fa-certificate" aria-hidden="true"></i>
</div>
<p>Tutors submit one Ijazah certificate with their application. An admin reviews it before pricing, availability and publishing become available.</p>
</section>
<section class="portal-card ">
<h2>A schedule you can plan around</h2>
<div class="feature-icon-wrapper flex-center"><i class="fas fa-calendar-check" aria-hidden="true"></i>
</div>
<p>Choose a lesson slot and see the date, start time, end time and class mode together. The completed system will check slot availability to prevent overlapping bookings.</p>
</section>
<section class="portal-card ">
<h2>Clear hourly pricing</h2>
<div class="feature-icon-wrapper flex-center"><i class="fas fa-tag" aria-hidden="true"></i>
</div>
<p>View the tutor’s rate and total lesson fee before booking. There is no distance-based pricing or hidden payment-gateway flow.</p>
</section>
<section class="portal-card ">
<h2>A visible payment journey</h2>
<div class="feature-icon-wrapper flex-center"><i class="fas fa-receipt" aria-hidden="true"></i>
</div>
<p>Upload your DuitNow receipt, track the tutor’s acknowledgement and see the admin’s verification decision in one place.</p>
</section>
<section class="portal-card ">
<h2>Tutors control their listing</h2>
<div class="feature-icon-wrapper flex-center"><i class="fas fa-eye" aria-hidden="true"></i>
</div>
<p>After verification, tutors complete their profile and choose Draft, Private or Public. Existing bookings remain available when a profile is unlisted.</p>
</section>
<section class="portal-card ">
<h2>Built for Quran learning</h2>
<div class="feature-icon-wrapper flex-center"><i class="fas fa-book-open" aria-hidden="true"></i>
</div>
<p>Designed for adult learners and independent Quran tutors in Malaysia, with online or physical lessons arranged around your routine.</p>
</section>
</div>
<div class="cta-banner">
<h2 class="cta-title">Make room for your Quran journey</h2>
<p class="cta-desc">Find a verified tutor or apply to share your knowledge.</p>
<div class="cta-actions"><a href="tutors.php" class="btn btn-light btn-sm">Find a tutor</a><a href="register.php#tutor-application" class="btn btn-outline-white btn-sm">Apply to teach</a>
</div>
</div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?></body></html>
