<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/profiles.php';
try {
    $dashboardTutor = tmq_own_tutor((int)$authUser['user_id']);
    $dashboardApproved = tmq_tutor_approved((int)$authUser['user_id']);
} catch (Throwable $error) {
    http_response_code(503); exit('Tutor account is temporarily unavailable. Please try again later.');
}
$assetPrefix = '../';
$pageTitle = 'Welcome to your tutor studio - TutorMeQuran';
$pageDescription = 'Your teaching profile, bookings and payment reviews.';
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
<div><span class="dashboard-eyebrow">TUTOR STUDIO</span><h1>Assalamualaikum, <?= tmq_escape($authUser['first_name']) ?>.</h1><p>Keep your teaching profile current, plan your slots and support your students’ progress.</p><div class="tutor-welcome-actions"><a href="tutor-profile-edit.php" class="btn dashboard-browse">Update Profile <i class="fas fa-arrow-right" aria-hidden="true"></i></a><a href="tutor-availability.php" class="btn tutor-availability-link">Manage availability</a></div>
</div><div class="welcome-art" aria-hidden="true"><i class="fas fa-book-open"></i><span>Teach. Guide. Inspire.</span></div>
</header>
<div class="portal-stats">
<article class="portal-card portal-stat tutor-dashboard-card"><div class="tutor-stat-copy"><p>Qualification</p><strong><?= $dashboardApproved ? 'Approved' : 'Not approved' ?></strong><a href="tutor-certificate-view.php">View certificate history</a></div></article>
<article class="portal-card portal-stat tutor-dashboard-card"><div class="tutor-stat-copy"><p>Profile visibility</p><strong><?= tmq_escape(ucfirst($dashboardTutor['profile_visibility'])) ?></strong><span><?= tmq_public_tutor($dashboardTutor) ? 'Listed publicly' : 'Not listed publicly' ?></span><a href="tutor-profile-visibility.php">Manage visibility</a></div></article>
<article class="portal-card portal-stat tutor-dashboard-card"><div class="tutor-stat-copy"><p>Student feedback</p><a href="tutor-feedbacks.php">View received feedback</a></div></article>
</div>
<div class="dashboard-content-grid tutor-dashboard-content">
<section class="dashboard-lesson tutor-next-lesson" aria-labelledby="next-lesson-title">
<div class="dashboard-section-heading"><h2 id="next-lesson-title">Your lessons</h2><a href="tutor-bookings.php">All bookings <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
<p>View your bookings for the current lesson schedule, student, arrangement and completion status.</p>
<a href="tutor-bookings.php" class="btn btn-primary">View bookings <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
</section>
<section class="dashboard-preparation tutor-attention-card" aria-labelledby="attention-title"><span class="dashboard-icon"><i class="fas fa-receipt" aria-hidden="true"></i></span><span class="dashboard-eyebrow">PAYMENTS</span><h2 id="attention-title">Review payment evidence.</h2>
<p>Open your payment records to review current receipts and acknowledgement status.</p>
<a href="tutor-payments.php" class="btn btn-primary btn-sm">View payments <i class="fas fa-arrow-right" aria-hidden="true"></i></a></section>
</div>
<div class="dashboard-help"><div><i class="far fa-circle-question" aria-hidden="true"></i><span><strong>We're here to help.</strong> Get support with your lessons or account.</span></div><a href="tutor-help.php">Visit the Help Centre <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
