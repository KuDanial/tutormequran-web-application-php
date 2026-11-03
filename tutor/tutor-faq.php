<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'FAQ - TutorMeQuran';
$pageDescription = 'Coordinate the lesson with your student, then confirm completion before payment.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main student-hub tutor-hub">
<header class="portal-heading"><div><span class="section-subtitle">Tutor studio</span><h1>FAQ</h1><p>Answers about teaching and payments.</p></div></header>
<div class="faq-accordion"><details><summary>How do I accept or reject a booking?</summary><p>Open My Bookings, select the request, and choose Accept booking or Reject booking. A rejection requires a reason.</p></details><details><summary>Where do I upload my DuitNow QR?</summary><p>Open Payments → DuitNow QR in your profile menu. Upload the original QR image supplied by your bank.</p></details><details><summary>How do I confirm a student payment?</summary><p>Open Payments → Pending confirmation. Review the receipt, check your bank account and confirm funds received. Admin performs the final verification.</p></details><details><summary>What if I did not receive the payment?</summary><p>Use Report a payment issue from the payment page. The payment stays pending until the issue is reviewed.</p></details><details><summary>How do online lessons happen?</summary><p>Agree on an external platform, such as Google Meet or WhatsApp, in the booking conversation.</p></details><details><summary>When can I publish my profile?</summary><p>An admin must approve your Ijazah certificate before your completed teaching profile can be published.</p></details></div><div class="portal-actions"><a class="btn btn-outline btn-sm" href="tutor-help.php">Help Centre</a></div>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
