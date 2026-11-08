<?php
require_once __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/public-tutors.php';
$assetPrefix = '';
$pageTitle = 'A teacher for your Quran journey - TutorMeQuran';
$pageDescription = 'Public tutor profile.';
$homeFooter = false;
$footerHelp = 'help.php';
$footerFaq = 'chatbot.php';
?>
<?php require __DIR__ . '/includes/header.php'; ?><body class="portal-page public-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/includes/public-navbar.php'; ?>
<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">TutorMeQuran</span>
<h1>A teacher for your Quran journey</h1>
<p>Public tutor profile.</p>
</div><a href="tutors.php" class="btn btn-outline btn-sm">Back to tutors</a>
</header>
<?php if(!$publicTutor): ?><section class="portal-card"><h2>Tutor unavailable</h2><p><?= tmq_escape($publicError) ?></p></section><?php else: $t=$publicTutor; ?>
<div class="profile-layout"><div><section class="portal-card"><h2>Learn with <?= tmq_escape(tmq_public_name($t)) ?></h2><div class="portal-person"><img class="profile-photo" src="<?= tmq_escape(tmq_photo_url($t)) ?>" width="160" height="160" alt="Tutor profile picture"><div><span class="portal-badge good">Verified</span><p><?= tmq_escape($t['teaching_state']) ?></p></div></div><p class="profile-bio"><?= tmq_escape($t['bio']) ?></p><dl class="portal-details">
<?php foreach(['Qualification'=>$t['certificate_name'],'Teaching Mode'=>ucfirst($t['teaching_mode']),'Teaching Location'=>$t['teaching_area'].', '.$t['teaching_state'],'Lesson Focus'=>implode(' · ',array_column($t['focuses'],'name')),'Language'=>implode(', ',array_column($t['languages'],'name')),'Certificate'=>'Reviewed by TutorMeQuran Admin'] as $label=>$value): ?><div><dt><?= tmq_escape($label) ?></dt><dd><?= tmq_escape($value) ?></dd></div><?php endforeach; ?></dl>
<div class="portal-actions"><?php foreach(['facebook','instagram','linkedin','twitter','tiktok'] as $network): if(!empty($t[$network.'_url'])): ?><a class="btn btn-outline btn-sm" rel="noopener noreferrer" href="<?= tmq_escape($t[$network.'_url']) ?>"><?= tmq_escape(ucfirst($network)) ?></a><?php endif; endforeach; ?></div></section>
<section class="portal-card"><h2>Available lessons</h2><p>Choose an available lesson on the Student booking page.</p></section>
<section class="portal-card"><h2>What students say</h2>
<?php if(!$publicFeedback['summary']['total']): ?><p>No feedback yet.</p>
<?php else: ?><p>Average rating: <?= tmq_escape(number_format((float)$publicFeedback['summary']['average'],2)) ?> / 5 &middot; <?= (int)$publicFeedback['summary']['total'] ?> review(s)</p>
<p>Latest Student reviews from lessons confirmed by both participants.</p>
<?php foreach($publicFeedback['rows'] as $review): ?><blockquote class="review-quote"><p style="white-space: pre-wrap"><?= tmq_escape($review['comment']??'No comment provided.') ?></p><footer>Student &middot; <?= (int)$review['rating'] ?> / 5 &middot; <?= tmq_escape(tmq_slot_local($review['created_at'])->format('d M Y')) ?></footer></blockquote><?php endforeach; ?>
<?php endif; ?></section></div>
<aside><section class="portal-card"><h2>A clear price before you book</h2><p class="profile-price">RM <?= tmq_escape($t['hourly_rate']) ?><span> / hour</span></p><dl class="portal-details"><div><dt>Lesson Fee</dt><dd>RM <?= tmq_escape($t['hourly_rate']) ?> ? lesson hours</dd></div><div><dt>Payment Method</dt><dd>DuitNow</dd></div></dl><div class="portal-actions"><a class="btn btn-primary btn-sm" href="student/student-booking-addNew.php?tutor=<?= (int)$t['tutor_id'] ?>">Choose a lesson</a></div><p class="portal-muted">Physical lesson locations will be agreed with your tutor.</p></section></aside></div><?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?></body></html>
