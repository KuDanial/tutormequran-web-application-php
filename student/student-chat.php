<?php
require_once __DIR__ . '/../includes/config.php';
$assetPrefix = '../';
$pageTitle = 'Message Tutor - TutorMeQuran';
$pageDescription = 'A clear view of your appointment, fee and payment progress.';
$homeFooter = false;
$footerHelp = 'student-help.php';
$footerFaq = 'student-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page student-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/student-header.php'; ?>

<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Your learning journey</span>
<h1>Message Tutor</h1>
<p>Agree on location and class mode before the class · sample booking TMQ-1025.</p>
</div>
</header>
<section class="portal-card"><h2>Booking conversation</h2><dl class="portal-details"><div><dt>Booking ID</dt><dd>TMQ-1025 · sample physical booking</dd></div><div><dt>Student</dt><dd>Ahmad Hakimi</dd></div><div><dt>Tutor</dt><dd>Ustaz Muhammad Farhan</dd></div><div><dt>Date & Time</dt><dd>21 October 2026 · 8:00 PM–9:00 PM MYT</dd></div><div><dt>Class Mode</dt><dd>Physical · Online change pending</dd></div><div><dt>Location</dt><dd>Shah Alam · exact venue to be agreed</dd></div><div><dt>Payment</dt><dd>Not due · pay after class completion</dd></div></dl></section><section class="portal-card booking-chat-card"><h2>Message Ustaz Muhammad Farhan</h2><div class="booking-messages" data-booking-messages role="log" aria-label="Conversation" aria-live="polite"><article class="chat-bubble"><strong>Ahmad · 10:00 AM</strong><p>Assalamualaikum. Could you confirm the physical class location before the class?</p></article><article class="chat-bubble chat-reply"><strong>Ustaz Farhan · 10:05 AM</strong><p>Waalaikumussalam. I teach in Shah Alam. Let us agree on a suitable venue and confirm the address here first.</p></article><article class="chat-bubble"><strong>Ahmad · 10:10 AM</strong><p>That is too far for me to travel. Could this lesson be online instead? I have requested a mode change.</p></article><article class="chat-bubble chat-reply"><strong>Ustaz Farhan · 10:12 AM</strong><p>I will review the request. Please wait for the booking mode to be confirmed before the class.</p></article></div><form class="portal-chat-compose" data-booking-chat data-author="You - Student"><label class="form-label" for="booking-message">Your message</label><textarea id="booking-message" class="form-control" rows="3" required maxlength="2000" placeholder="Discuss the lesson or meeting point..."></textarea><div class="chat-compose-actions"><button class="btn btn-outline btn-sm" type="button" data-location-toggle aria-expanded="false" aria-controls="chat-location"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Share location</button><button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane" aria-hidden="true"></i> Send message</button></div>
<div id="chat-location" class="chat-location-panel" hidden><h3>Share a meeting point</h3><label class="form-label" for="location-address">Venue or full address</label><input id="location-address" class="form-control" maxlength="500" placeholder="Enter the venue and address"><div class="portal-actions"><button type="button" class="btn btn-primary btn-sm" data-location-address>Share this venue</button><button type="button" class="btn btn-outline btn-sm" data-location-current>Use my current location</button></div><p class="field-help">Current location asks for browser permission. Review the map before sharing.</p><div data-location-preview hidden><a target="_blank" rel="noopener noreferrer" data-location-map>Preview location on map</a><button type="button" class="btn btn-primary btn-sm" data-location-send>Share this location</button></div><p data-location-status role="status"></p></div></form><p class="field-help">Preview conversation only. Messages and locations appear on this page and are not sent.</p></section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script><script src="../assets/js/booking-preview.js"></script></body></html>
