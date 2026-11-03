<?php require_once __DIR__ . '/profiles.php'; $headerProfile=tmq_profile_user((int)$authUser['user_id']); ?>
<?php $tutorApplicant = !tmq_tutor_approved((int)$authUser['user_id']); ?>
<nav class="navbar" id="navbar">
    <div class="container nav-container">
      <a href="../index.php" class="logo-link" id="nav-logo">
        <img src="<?= tmq_escape($assetPrefix) ?>assets/img/TutorMeQuran_LOGO.png" alt="TutorMeQuran Logo" class="logo-img">
        <div class="logo-text">
          TutorMeQuran
          <span>Verified Quran Tutors</span>
        </div>
      </a>

      <!-- Desktop Links -->
      <ul class="nav-links">
        <li><a href="../index.php" class="nav-link">Home</a>
</li>
        <li><a href="../tutors.php" class="nav-link">Browse</a>
</li>
        <li><a href="../why-us.php" class="nav-link">Why Us</a>
</li>
        <li><a href="../help.php" class="nav-link">Help</a>
</li>
      </ul>

      <!-- Action Buttons -->

<details class="profile-menu"><summary aria-label="Open tutor profile menu"><span class="profile-menu-avatar" aria-hidden="true"><img src="<?= tmq_escape(tmq_photo_url($headerProfile)) ?>" alt="" width="38" height="38"></span><span class="profile-menu-name"><?= tmq_escape($authUser['first_name'] . ' ' . $authUser['last_name']) ?></span><span aria-hidden="true">▾</span></summary><nav class="profile-menu-panel student-menu-panel tutor-menu-panel" aria-label="Tutor navigation">
<strong>Tutor Account</strong>
<section class="student-menu-group" aria-labelledby="menu-dashboard"><h2 id="menu-dashboard"><a href="<?= $tutorApplicant ? 'tutor-dashboard.php' : 'tutor-dashboard-verified.php' ?>">Dashboard</a></h2>
<a href="tutor-profile-view.php">My Profile</a><a href="tutor-notifications.php">Notifications</a>
<details class="student-menu-sub"><summary>Payments</summary><div><a href="tutor-payment-qr.php">DuitNow QR</a><a href="tutor-payments.php#pending">Pending confirmation</a><a href="tutor-payments.php#received">Received</a></div></details></section>
<section class="student-menu-group" aria-labelledby="menu-management"><h2 id="menu-management">Management</h2>
<details class="student-menu-sub"><summary>My Bookings</summary><div><a href="tutor-bookings.php#pending">Pending bookings</a><a href="tutor-bookings.php#past">Past bookings</a></div></details>
<a href="tutor-availability.php">My Availability</a><a href="<?= $tutorApplicant ? 'tutor-certificate-view.php' : 'tutor-certificate-verified.php' ?>">My Certificate</a><a href="tutor-feedbacks.php">Student Feedback</a></section>
<section class="student-menu-group" aria-labelledby="menu-support"><h2 id="menu-support">Support</h2>
<details class="student-menu-sub"><summary>Help Centre</summary><div><a href="tutor-support-request.php">Submit a request</a><a href="tutor-contact.php">Contact Us</a></div></details><a href="tutor-faq.php">FAQ</a></section>
<form method="post" action="<?= tmq_escape(tmq_url('logout.php')) ?>" class="auth-logout"><?= tmq_csrf_field() ?><button type="submit" class="student-menu-logout action-danger" data-confirm="Log out of your account? Any unsaved changes may be lost."><span class="action-warning-icon" aria-hidden="true">↪</span> Log Out</button></form></nav></details>
    </div>
  </nav>