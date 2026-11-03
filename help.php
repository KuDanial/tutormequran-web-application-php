<?php
require_once __DIR__ . '/includes/config.php';
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
<div class="faq-accordion"><details><summary>What if my payment is too low or I need to change class mode?</summary><p>Admin can record Underpaid, Rejected or Reupload Required. Check the expected fee and outstanding balance before another transfer. Use the booking chat to discuss a location or online mode request. The original arrangement remains until the tutor accepts a change.</p></details><details><summary>How are cancellations recorded?</summary><p>Before the booking date, record Cancelled by Student or Cancelled by Tutor. On the booking date in Malaysia time, record Late Cancellation and who cancelled. Admin reviews payment disputes; cancellation does not automatically refund a transfer.</p></details><details id="booking"><summary>How do I book a tutor?</summary>
<p>Browse the public tutor directory, open a verified profile and select an available slot. Review the hourly rate, class mode and lesson time before confirming your booking.</p></details><details id="payments"><summary>How do I upload my DuitNow receipt?</summary>
<p>Open My Bookings, select your booking and choose Upload Payment. Check the recipient and amount, transfer directly to the tutor, then attach your receipt. The tutor acknowledges receipt of funds and the admin records the final payment status.</p></details><details id="verification"><summary>How long does tutor verification take?</summary>
<p>The admin reviews your certificate after you apply. No review time is guaranteed in this prototype. Check your application status for the decision and any correction requested.</p></details><details id="pricing"><summary>When can I set my hourly rate?</summary>
<p>After your certificate is verified, open your teaching profile to add pricing, qualifications, biography and availability. Those teaching tools remain locked while your application is pending or rejected.</p></details><details id="visibility"><summary>What do Draft, Private and Public mean?</summary>
<p>Draft keeps an unfinished profile unlisted. Private keeps a completed profile unlisted and pauses new bookings. Public makes a verified, completed profile discoverable and bookable. Existing bookings remain accessible when a listing is made private.</p></details><details id="cancellation"><summary>Can students and tutors edit or cancel bookings?</summary>
<p>Both can open a booking and choose Edit or Cancel. Coordinate changes with the other person. Cancelling a booking does not automatically refund a DuitNow transfer; payment issues require admin review.</p></details><details id="issues"><summary>What if the tutor has not received my payment?</summary>
<p>The tutor can report a mismatch instead of confirming receipt. The payment stays pending for admin review. Check the original transaction before making another transfer.</p></details><details id="eligibility"><summary>Who can use TutorMeQuran?</summary>
<p>Students and independent Quran tutors aged 18 and above who reside in Malaysia. Lessons focus on Quran-related subjects.</p></details><details id="lessons"><summary>Where do online lessons happen?</summary>
<p>Students and tutors agree on an external platform for their lesson. TutorMeQuran handles booking and payment records and does not provide built-in video calls.</p></details>
</div>
<section class="portal-card ">
<h2>Need help with a specific booking?</h2>
<p>Include your booking ID when explaining a payment or scheduling issue.</p>
<section class="portal-card ">
<h2>Support request</h2>
<form class="portal-form">
<button type="submit" hidden disabled aria-hidden="true">
</button>
<div class="portal-fields">
<div class="form-group">
<label for="f-supportEmail" class="form-label">Your Email</label>
<input id="f-supportEmail" name="supportEmail" class="form-control" type="email" value="">
</div>
<div class="form-group">
<label for="f-supportBooking" class="form-label">Booking ID (if applicable)</label>
<input id="f-supportBooking" name="supportBooking" class="form-control" type="text" value="">
</div>
<div class="form-group">
<label for="f-supportMessage" class="form-label">Your Message</label>
<textarea id="f-supportMessage" name="supportMessage" class="form-control" rows="4">
</textarea>
</div>
</div>
<div class="portal-actions">
<button type="button" class="btn btn-primary">Send Request</button>
</div>
</form>
</section>
</section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?></body></html>
