<?php
require_once __DIR__ . '/includes/config.php';
$assetPrefix = '';
$pageTitle = 'FAQ Chat - TutorMeQuran';
$pageDescription = 'Guidance for bookings, tutor applications and payment reviews.';
$homeFooter = false;
$footerHelp = 'help.php';
$footerFaq = 'chatbot.php';
?>
<?php require __DIR__ . '/includes/header.php'; ?><body class="portal-page public-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/includes/public-navbar.php'; ?>
<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">TutorMeQuran</span>
<h1>FAQ Chat</h1>
<p>Guidance for bookings, tutor applications and payment reviews.</p>
</div><a href="help.php" class="btn btn-outline btn-sm">Browse help articles</a>
</header>

  <section class="portal-card portal-chat">
<header class="portal-chat-header"><span class="portal-avatar" aria-hidden="true"><i class="far fa-comments"></i></span>
<div>
<h2>FAQ Assistant</h2>
<p>Booking, receipts and verification</p>
</div>
</header>
<div class="portal-messages" aria-label="Chat message area">
<article class="portal-message"><strong>TutorMeQuran FAQ</strong>
<p>Assalamualaikum! Welcome to TutorMeQuran. I can help with common questions about booking a Quran tutor, DuitNow receipts, tutor verification and cancellations.</p><small>Sample greeting</small>
</article>
</div>
<div class="portal-quick-questions">
<p>Common questions</p>
<button type="button" class="btn btn-outline">How to book a tutor?</button>
<button type="button" class="btn btn-outline">How to upload DuitNow receipt?</button>
<button type="button" class="btn btn-outline">How long tutor verification takes?</button>
<button type="button" class="btn btn-outline">How to cancel booking?</button>
</div>
<div class="portal-chat-compose">
<div>
<label class="form-label" for="chat-message">Your message</label>
<input class="form-control" id="chat-message" type="text" placeholder="Type your question here…">
</div>
<button type="button" class="btn btn-primary">Send</button>
</div>
<p class="portal-muted">FAQ interface preview. Messages and quick questions are not sent in this demo.</p>
</section>

</main>
<?php require __DIR__ . '/includes/footer.php'; ?></body></html>
