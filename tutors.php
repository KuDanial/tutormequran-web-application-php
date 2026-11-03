<?php
require_once __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/public-tutors.php';
$assetPrefix = '';
$pageTitle = 'Find your next Quran tutor - TutorMeQuran';
$pageDescription = 'Verified qualifications. Clear hourly rates. Lessons that fit your routine.';
$homeFooter = false;
$footerHelp = 'help.php';
$footerFaq = 'chatbot.php';
?>
<?php require __DIR__ . '/includes/header.php'; ?><body class="portal-page public-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/includes/public-navbar.php'; ?>
<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">TutorMeQuran</span>
<h1>Find your next Quran tutor</h1>
<p>Verified qualifications. Clear hourly rates. Lessons that fit your routine.</p>
</div><a href="register.php#tutor-application" class="btn btn-outline btn-sm">Apply to teach</a>
</header>
<section class="search-box" aria-label="Find your fit">
<form method="get">

<div class="search-grid">
<div class="search-field">
<label for="f-searchTutor"><i class="fas fa-search" aria-hidden="true"></i> Search Tutor</label>
<div class="search-input-wrapper">
<i class="fas fa-user-circle" aria-hidden="true"></i>
<input id="f-searchTutor" name="searchTutor" type="search" placeholder="Search by name, expertise..." value="<?= tmq_escape(tmq_input($_GET, 'searchTutor')) ?>">
</div>
</div>
<div class="search-field">
<label for="f-filterMode"><i class="fas fa-laptop-house" aria-hidden="true"></i> Teaching Mode</label>
<div class="search-input-wrapper">
<i class="fas fa-map-marker-alt" aria-hidden="true"></i>
<select id="f-filterMode" name="filterMode"><option value="" <?= tmq_input($_GET, 'filterMode') === '' ? 'selected' : '' ?>>Any mode</option><option value="online" <?= tmq_input($_GET, 'filterMode') === 'online' ? 'selected' : '' ?>>Online</option><option value="physical" <?= tmq_input($_GET, 'filterMode') === 'physical' ? 'selected' : '' ?>>Physical</option></select>
</div>
</div>
<div class="search-field">
<label for="f-filterState"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> State</label>
<div class="search-input-wrapper">
<i class="fas fa-map-marker-alt" aria-hidden="true"></i>
<select id="f-filterState" name="filterState"><option value="" <?= tmq_input($_GET, 'filterState') === '' ? 'selected' : '' ?>>Any state</option><option value="Johor" <?= tmq_input($_GET, 'filterState') === 'Johor' ? 'selected' : '' ?>>Johor</option><option value="Kedah" <?= tmq_input($_GET, 'filterState') === 'Kedah' ? 'selected' : '' ?>>Kedah</option><option value="Kelantan" <?= tmq_input($_GET, 'filterState') === 'Kelantan' ? 'selected' : '' ?>>Kelantan</option><option value="Melaka" <?= tmq_input($_GET, 'filterState') === 'Melaka' ? 'selected' : '' ?>>Melaka</option><option value="Negeri Sembilan" <?= tmq_input($_GET, 'filterState') === 'Negeri Sembilan' ? 'selected' : '' ?>>Negeri Sembilan</option><option value="Pahang" <?= tmq_input($_GET, 'filterState') === 'Pahang' ? 'selected' : '' ?>>Pahang</option><option value="Penang" <?= tmq_input($_GET, 'filterState') === 'Penang' ? 'selected' : '' ?>>Penang</option><option value="Perak" <?= tmq_input($_GET, 'filterState') === 'Perak' ? 'selected' : '' ?>>Perak</option><option value="Perlis" <?= tmq_input($_GET, 'filterState') === 'Perlis' ? 'selected' : '' ?>>Perlis</option><option value="Sabah" <?= tmq_input($_GET, 'filterState') === 'Sabah' ? 'selected' : '' ?>>Sabah</option><option value="Sarawak" <?= tmq_input($_GET, 'filterState') === 'Sarawak' ? 'selected' : '' ?>>Sarawak</option><option value="Selangor" <?= tmq_input($_GET, 'filterState') === 'Selangor' ? 'selected' : '' ?>>Selangor</option><option value="Terengganu" <?= tmq_input($_GET, 'filterState') === 'Terengganu' ? 'selected' : '' ?>>Terengganu</option><option value="Kuala Lumpur" <?= tmq_input($_GET, 'filterState') === 'Kuala Lumpur' ? 'selected' : '' ?>>Kuala Lumpur</option><option value="Labuan" <?= tmq_input($_GET, 'filterState') === 'Labuan' ? 'selected' : '' ?>>Labuan</option><option value="Putrajaya" <?= tmq_input($_GET, 'filterState') === 'Putrajaya' ? 'selected' : '' ?>>Putrajaya</option></select>
</div>
</div>
<div class="search-field">
<label for="f-filterRate"><i class="fas fa-money-bill-wave" aria-hidden="true"></i> Hourly Budget</label>
<div class="search-input-wrapper">
<i class="fas fa-ring" aria-hidden="true"></i>
<select id="f-filterRate" name="filterRate"><option value="" <?= tmq_input($_GET, 'filterRate') === '' ? 'selected' : '' ?>>Any rate</option><option value="below30" <?= tmq_input($_GET, 'filterRate') === 'below30' ? 'selected' : '' ?>>Below RM 30</option><option value="30to50" <?= tmq_input($_GET, 'filterRate') === '30to50' ? 'selected' : '' ?>>RM 30?50</option><option value="above50" <?= tmq_input($_GET, 'filterRate') === 'above50' ? 'selected' : '' ?>>Above RM 50</option></select>
</div>
</div>
<button type="submit" class="btn btn-primary btn-sm">Search Tutors</button>
</div>
</form>
</section>
<div class="results-heading">
<h2>Available to book</h2><span>1 public tutor · sample directory</span>
</div>
<?php if($publicError): ?><aside class="flow-notice warm"><p><?= tmq_escape($publicError) ?></p></aside><?php endif; ?>
<?php foreach($publicTutors as $t): ?>
<article class="portal-card tutor-listing"><div class="tutor-portrait"><img class="profile-photo" src="<?= tmq_escape(tmq_photo_url($t)) ?>" width="160" height="160" alt="Tutor profile picture"><small>QURAN TEACHING</small></div><div class="listing-content"><span class="portal-badge good">Verified</span><h2><?= tmq_escape(tmq_public_name($t)) ?></h2><p class="listing-specialty"><?= tmq_escape(implode(' · ',array_column($t['focuses'],'name'))) ?></p><p><?= tmq_escape($t['bio']) ?></p><div class="listing-meta"><span><?= tmq_escape($t['teaching_state']) ?></span><span><?= tmq_escape(ucfirst($t['teaching_mode'])) ?></span></div></div><div class="listing-book"><strong>RM <?= tmq_escape($t['hourly_rate']) ?><span>/ hour</span></strong><p>Lesson availability will be added later.</p><a href="tutor-detail.php?id=<?= (int)$t['tutor_id'] ?>" class="btn btn-primary btn-sm">View profile</a></div></article>
<?php endforeach; if(!$publicTutors && !$publicError): ?><section class="portal-card"><h2>No tutors to display</h2><p>No public tutor profiles match these filters yet.</p></section><?php endif; ?>
<aside class="flow-notice info"><strong>Every public listing starts with verification</strong>
<p>Tutors apply with an Ijazah certificate. After admin approval, they complete their profile and choose whether to publish. Draft and Private profiles are not listed.</p>
</aside>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?></body></html>
