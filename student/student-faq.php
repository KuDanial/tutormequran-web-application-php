<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'FAQ - TutorMeQuran';
$pageDescription = 'FAQ for your TutorMeQuran account.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main student-hub">
<header class="portal-heading"><div><span class="section-subtitle">Your learning journey</span><h1>FAQ</h1><p>Quick answers about your TutorMeQuran account.</p></div></header><div class="faq-accordion">
<details><summary>How do I find and book a tutor?</summary><p>Browse tutors, check their hourly rate and teaching mode, then choose an available slot. <a href="../tutors.php">Find a tutor</a>.</p></details><details><summary>Where are my pending and past bookings?</summary><p>Open My Bookings under Management in your profile menu. Pending bookings contains upcoming lessons and bookings still in progress. Past bookings contains completed or cancelled lessons.</p></details><details><summary>How do online and physical lessons work?</summary><p>Agree on the meeting link or venue with your tutor through the booking conversation. Online lessons use an external platform such as Google Meet or WhatsApp.</p></details><details><summary>When and how do I pay?</summary><p>In this booking flow, pay after the class is complete. Open the payment step to see your tutor’s DuitNow QR, pay using your banking app, and upload the successful transaction receipt.</p></details><details><summary>Who verifies my receipt?</summary><p>Your tutor confirms that the funds were received, or reports a payment issue. Admin reviews the receipt and records the final verification.</p></details><details><summary>Can I edit or cancel a booking?</summary><p>Open the booking to edit or cancel it and coordinate changes with your tutor. Use the Help Centre if you need assistance.</p></details><details><summary>How do I ask about a refund or an attendance issue?</summary><p><a href="student-support-request.php">Submit a request</a>, choose Billing or refund or Attendance issue, and explain what happened. You can add a photo. A request does not automatically approve or process a refund.</p></details><details><summary>Where can I edit my profile?</summary><p>Open <a href="student-profile-view.php">My Profile</a>, then choose Edit profile. You can update your details and profile picture.</p></details>
</div><div class="portal-actions"><a class="btn btn-outline btn-sm" href="student-help.php">Visit Help Centre</a><a class="btn btn-outline btn-sm" href="../chatbot.php">Open FAQ Chat</a></div>

</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
