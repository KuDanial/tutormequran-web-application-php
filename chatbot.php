<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/faq-chatbot.php';
$chatQuestion = '';
$chatAnswer = null;
$chatError = '';
$chatSubmitted = false;
header('Cache-Control: no-store');
try {
    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
        header('Allow: GET, POST'); http_response_code(405); exit('Method not allowed.');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        tmq_check_csrf();
        $chatQuestion = tmq_faq_text($_POST['question'] ?? null, 'Question', 500);
        $chatAnswer = tmq_chatbot_answer($chatQuestion);
        $chatSubmitted = true;
    }
} catch (DomainException $error) {
    if (http_response_code() !== 403) { http_response_code(422); }
    $chatError = $error->getMessage();
} catch (Throwable $error) {
    http_response_code(503);
    $chatError = 'FAQ answers are temporarily unavailable. Please try again later.';
}
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
<div class="portal-messages" aria-label="Chat message area" aria-live="polite">
<?php if ($chatError !== ''): ?><p role="alert"><?= tmq_escape($chatError) ?></p><?php endif; ?>
<?php if ($chatSubmitted): ?>
<article class="portal-message"><strong>Your question</strong><p><?= nl2br(tmq_escape($chatQuestion)) ?></p></article>
<article class="portal-message"><strong>TutorMeQuran FAQ</strong>
<?php if ($chatAnswer !== null): ?>
<h3><?= tmq_escape($chatAnswer['question']) ?></h3>
<p><?= nl2br(tmq_escape($chatAnswer['answer'])) ?></p>
<?php else: ?>
<p>Sorry, I could not find a suitable answer for that question. Please <a href="help.php">check the FAQ or find support options</a> for more help.</p>
<?php endif; ?>
</article>
<?php else: ?>
<article class="portal-message"><strong>TutorMeQuran FAQ</strong>
<p>Assalamualaikum! Ask a question to find an answer in the TutorMeQuran FAQ.</p>
</article>
<?php endif; ?>
</div>
<form method="post" action="chatbot.php" class="portal-chat-compose">
<?= tmq_csrf_field() ?>
<div>
<label class="form-label" for="chat-message">Your message</label>
<input class="form-control" id="chat-message" name="question" type="text" required placeholder="Type your question here…" aria-describedby="chat-limit">
</div>
<button type="submit" class="btn btn-primary">Send</button>
</form>
<p class="portal-muted" id="chat-limit">Ask up to 500 characters. Answers come from our FAQ using simple rules. This is not an AI chatbot; conversations are not saved.</p>
</section>

</main>
<?php require __DIR__ . '/includes/footer.php'; ?></body></html>
