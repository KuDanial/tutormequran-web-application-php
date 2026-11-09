<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Student Dashboard - TutorMeQuran';
$pageDescription = 'Your Quran lessons and account shortcuts.';
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

<div class="dashboard-content-grid">
  <section class="dashboard-lesson" aria-labelledby="lesson-title">
    <div class="dashboard-section-heading"><h2 id="lesson-title">Your lesson</h2><a href="student-bookings.php?status=requested">All bookings <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
    <p>View your bookings for the current lesson schedule, tutor, arrangement and completion status.</p>
    <a href="student-bookings.php" class="btn btn-primary">View bookings <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
  </section>
  <aside class="dashboard-preparation" aria-labelledby="preparation-title"><span class="dashboard-icon"><i class="fas fa-book-quran" aria-hidden="true"></i></span><span class="dashboard-eyebrow">MAKE TIME FOR YOURSELF</span><h2 id="preparation-title">A little preparation<br>goes a long way.</h2><p>Make the most of your time with your tutor.</p><ul><li><i class="fas fa-check" aria-hidden="true"></i> Keep your Quran and notes nearby</li><li><i class="fas fa-check" aria-hidden="true"></i> Find a quiet, comfortable space</li><li><i class="fas fa-check" aria-hidden="true"></i> Note any questions for your tutor</li></ul></aside>
</div>

<section class="dashboard-account" aria-labelledby="account-title"><div class="dashboard-section-heading"><h2 id="account-title">Your account, at a glance</h2></div><nav class="dashboard-shortcuts" aria-label="Your account">
  <a href="student-bookings.php?status=requested"><span class="dashboard-icon"><i class="far fa-calendar-check" aria-hidden="true"></i></span><strong>My bookings</strong><span>Manage upcoming and past lessons</span><i class="fas fa-arrow-right shortcut-arrow" aria-hidden="true"></i></a>
  <a href="student-invoices.php"><span class="dashboard-icon"><i class="fas fa-file-invoice" aria-hidden="true"></i></span><strong>Invoices &amp; receipts</strong><span>Review pending fees and payments</span><i class="fas fa-arrow-right shortcut-arrow" aria-hidden="true"></i></a>
  <a href="student-notifications.php"><span class="dashboard-icon"><i class="far fa-bell" aria-hidden="true"></i></span><strong>Notifications</strong><span>Stay up to date with your bookings</span><i class="fas fa-arrow-right shortcut-arrow" aria-hidden="true"></i></a>
</nav></section>
<div class="dashboard-help"><div><i class="far fa-circle-question" aria-hidden="true"></i><span><strong>We're here to help.</strong> Get support with your lessons or account.</span></div><a href="student-help.php">Visit the Help Centre <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
