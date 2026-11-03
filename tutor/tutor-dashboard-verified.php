<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Welcome to your tutor studio - TutorMeQuran';
$pageDescription = 'Assalamualaikum, Ustaz Farhan. Your certificate is verified.';
$extraStyles = ['assets/css/student-dashboard.css'];
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main">
<header class="dashboard-welcome tutor-dashboard-welcome">
<div><span class="dashboard-eyebrow">TUTOR STUDIO · VERIFIED</span><h1>Assalamualaikum, Ustaz Farhan.</h1><p>Keep your teaching profile current, plan your slots and support your students’ progress.</p><div class="tutor-welcome-actions"><a href="tutor-profile-edit.php" class="btn dashboard-browse">Update Profile <i class="fas fa-arrow-right" aria-hidden="true"></i></a><a href="tutor-availability.php" class="btn tutor-availability-link">Manage availability</a></div>
</div><div class="welcome-art" aria-hidden="true"><i class="fas fa-book-open"></i><span>Teach. Guide. Inspire.</span></div>
</header>
<div class="portal-stats">
<article class="portal-card portal-stat tutor-dashboard-card"><span class="dashboard-card-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></span><div class="tutor-stat-copy"><p>Total bookings</p><strong>24</strong><span>All-time bookings</span></div>
</article>
<article class="portal-card portal-stat tutor-dashboard-card"><span class="dashboard-card-icon"><i class="fas fa-wallet" aria-hidden="true"></i></span><div class="tutor-stat-copy"><p>Total earning</p><strong>RM 960</strong><span>Sample earnings</span></div>
</article>
<article class="portal-card portal-stat tutor-dashboard-card"><span class="dashboard-card-icon"><i class="fas fa-star" aria-hidden="true"></i></span><div class="tutor-stat-copy"><p>Average rating</p><strong>—</strong><span>No ratings yet</span></div>
</article>
<article class="portal-card portal-stat tutor-dashboard-card"><span class="dashboard-card-icon"><i class="fas fa-eye" aria-hidden="true"></i></span><div class="tutor-stat-copy"><p>Profile visibility</p><strong>Public</strong><span>You control your listing</span></div>
</article>
</div>
<div class="dashboard-content-grid tutor-dashboard-content">
<section class="dashboard-lesson tutor-next-lesson" aria-labelledby="next-lesson-title">
<div class="dashboard-section-heading"><h2 id="next-lesson-title">Next lesson</h2><a href="tutor-bookings.php">All bookings <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
<div class="lesson-overview"><div class="dashboard-date"><span>OCT</span><strong>20</strong><span>2026</span></div><div><span class="dashboard-eyebrow">ONLINE LESSON · TMQ-1024</span><h3>Quran recitation &amp; Tajweed</h3><p><i class="far fa-clock" aria-hidden="true"></i> 8–9 PM MYT · 1 hour</p></div></div>
<div class="dashboard-tutor"><span class="tutor-initials" aria-hidden="true">AH</span><div><span>Your student</span><strong>Ahmad Hakimi</strong></div></div>
<section class="booking-progress booking-progress-compact" aria-label="Booking progress for TMQ-1024" data-progress-booking="TMQ-1024" data-progress-status="session" data-progress-source="session"><ol class="booking-journey"><li class="done"><span class="booking-step" aria-label="Booking: complete"><span class="booking-step-dot" aria-hidden="true">✓</span><span class="booking-step-label">Booking</span></span></li><li class="done"><span class="booking-step" aria-label="Arrange: complete"><span class="booking-step-dot" aria-hidden="true">✓</span><span class="booking-step-label">Arrange</span></span></li><li class="current" aria-current="step"><span class="booking-step" aria-label="Class: current stage"><span class="booking-step-dot" aria-hidden="true">3</span><span class="booking-step-label">Class</span></span></li><li><span class="booking-step" aria-label="Payment: upcoming stage"><span class="booking-step-dot" aria-hidden="true">4</span><span class="booking-step-label">Payment</span></span></li><li><span class="booking-step" aria-label="Verified: upcoming stage"><span class="booking-step-dot" aria-hidden="true">5</span><span class="booking-step-label">Verified</span></span></li></ol><p class="booking-current-label" aria-live="polite">Class arranged · confirm completion after the lesson</p></section>
<a href="tutor-booking-view.php" class="btn btn-primary">Open booking <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
</section>
<section class="dashboard-preparation tutor-attention-card" aria-labelledby="attention-title"><span class="dashboard-icon"><i class="fas fa-receipt" aria-hidden="true"></i></span><span class="dashboard-eyebrow">ACTION NEEDED</span><h2 id="attention-title">A receipt is ready to review.</h2>
<aside class="flow-notice info"><strong>Ahmad uploaded a receipt</strong><p>TMQ-1024 · RM 40.00 · awaiting your confirmation</p></aside>
<a href="tutor-payment-view.php" class="btn btn-primary btn-sm">Review receipt <i class="fas fa-arrow-right" aria-hidden="true"></i></a></section>
</div>
<div class="dashboard-help"><div><i class="far fa-circle-question" aria-hidden="true"></i><span><strong>We're here to help.</strong> Get support with your lessons or account.</span></div><a href="tutor-help.php">Visit the Help Centre <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/booking-progress.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
