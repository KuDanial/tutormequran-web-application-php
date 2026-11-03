<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Student Dashboard - TutorMeQuran';
$pageDescription = 'Your Quran lessons, account shortcuts and TutorMeQuran community statistics.';
$extraStyles = ['assets/css/student-dashboard.css'];
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace student-dashboard"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main student-hub">
<header class="dashboard-welcome">
  <div><span class="dashboard-eyebrow">YOUR LEARNING SPACE</span><h1>Assalamualaikum, <?= tmq_escape($authUser['first_name']) ?>.</h1><p>A little progress, every lesson. Continue your Quran journey with us.</p><a href="../tutors.php" class="btn dashboard-browse">Find a tutor <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
  <div class="welcome-art" aria-hidden="true"><i class="fas fa-book-open"></i><span>Learn. Practise. Grow.</span></div>
</header>

<section class="dashboard-community" aria-labelledby="community-title">
  <div class="community-stats">
    <article class="community-stat"><span class="dashboard-icon"><i class="fas fa-chalkboard-user" aria-hidden="true"></i></span><div><h3>Total tutors</h3><strong>12</strong><p>Registered tutors across the platform</p></div></article>
    <article class="community-stat"><span class="dashboard-icon student-stat-icon"><i class="fas fa-user-graduate" aria-hidden="true"></i></span><div><h3>Total students</h3><strong>120</strong><p>Learners in our Quran community</p></div></article>
  </div>
</section>

<div class="dashboard-content-grid">
  <section class="dashboard-lesson" aria-labelledby="lesson-title">
    <div class="dashboard-section-heading"><h2 id="lesson-title">Your lesson</h2><a href="student-bookings.php#pending">All bookings <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
    <div class="lesson-overview"><div class="dashboard-date"><span>OCT</span><strong>20</strong><span>2026</span></div><div><span class="dashboard-eyebrow">ONLINE LESSON &middot; TMQ-1024</span><h3>Quran recitation &amp; Tajweed</h3><p><i class="far fa-clock" aria-hidden="true"></i> 8&ndash;9 PM MYT &middot; 1 hour</p></div></div>
    <div class="dashboard-tutor"><span class="tutor-initials" aria-hidden="true">MF</span><div><span>Your tutor</span><strong>Ustaz Muhammad Farhan</strong></div></div>
    <section class="booking-progress booking-progress-compact" aria-label="Booking progress for TMQ-1024" data-progress-booking="TMQ-1024" data-progress-status="requested" data-progress-source="session"><ol class="booking-journey"><li class="current" aria-current="step"><span class="booking-step" aria-label="Booking: current stage"><span class="booking-step-dot" aria-hidden="true">1</span><span class="booking-step-label">Booking</span></span></li><li><span class="booking-step" aria-label="Arrange: upcoming stage"><span class="booking-step-dot" aria-hidden="true">2</span><span class="booking-step-label">Arrange</span></span></li><li><span class="booking-step" aria-label="Class: upcoming stage"><span class="booking-step-dot" aria-hidden="true">3</span><span class="booking-step-label">Class</span></span></li><li><span class="booking-step" aria-label="Payment: upcoming stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li><span class="booking-step" aria-label="Verified: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Waiting for tutor acceptance</p></section>
    <a href="student-booking-view.php" class="btn btn-primary">View booking <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
  </section>
  <aside class="dashboard-preparation" aria-labelledby="preparation-title"><span class="dashboard-icon"><i class="fas fa-book-quran" aria-hidden="true"></i></span><span class="dashboard-eyebrow">MAKE TIME FOR YOURSELF</span><h2 id="preparation-title">A little preparation<br>goes a long way.</h2><p>Make the most of your time with your tutor.</p><ul><li><i class="fas fa-check" aria-hidden="true"></i> Keep your Quran and notes nearby</li><li><i class="fas fa-check" aria-hidden="true"></i> Find a quiet, comfortable space</li><li><i class="fas fa-check" aria-hidden="true"></i> Note any questions for your tutor</li></ul></aside>
</div>

<section class="dashboard-account" aria-labelledby="account-title"><div class="dashboard-section-heading"><h2 id="account-title">Your account, at a glance</h2></div><nav class="dashboard-shortcuts" aria-label="Your account">
  <a href="student-bookings.php#pending"><span class="dashboard-icon"><i class="far fa-calendar-check" aria-hidden="true"></i></span><strong>My bookings</strong><span>Manage upcoming and past lessons</span><i class="fas fa-arrow-right shortcut-arrow" aria-hidden="true"></i></a>
  <a href="student-invoices.php#pending"><span class="dashboard-icon"><i class="fas fa-file-invoice" aria-hidden="true"></i></span><strong>Invoices &amp; receipts</strong><span>Review pending fees and payments</span><i class="fas fa-arrow-right shortcut-arrow" aria-hidden="true"></i></a>
  <a href="student-notifications.php"><span class="dashboard-icon"><i class="far fa-bell" aria-hidden="true"></i></span><strong>Notifications</strong><span>Stay up to date with your bookings</span><i class="fas fa-arrow-right shortcut-arrow" aria-hidden="true"></i></a>
</nav></section>
<div class="dashboard-help"><div><i class="far fa-circle-question" aria-hidden="true"></i><span><strong>We're here to help.</strong> Get support with your lessons or account.</span></div><a href="student-help.php">Visit the Help Centre <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
