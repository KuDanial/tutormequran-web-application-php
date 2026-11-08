<?php
require_once __DIR__ . '/../includes/config.php';
$faqMode = 'view';
require __DIR__ . '/../includes/faq-admin.php';
$assetPrefix = '../';
$pageTitle = 'FAQ Detail - TutorMeQuran';
$pageDescription = 'Manage your TutorMeQuran activity.';
$currentPage = '';
$adminPrefix = '';
$workspaceLabel = 'Administration';
$showAdminSettings = true;
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page admin-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<div class="admin-layout">
<?php require __DIR__ . '/../includes/admin-sidebar.php'; ?>
<div class="admin-body">
<?php require __DIR__ . '/../includes/admin-header.php'; ?>
<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Operations workspace</span>
<h1>FAQ Detail</h1>
<p>Manage your TutorMeQuran activity.</p>
</div>
<div class="portal-actions"><a class="btn btn-primary btn-sm" href="manage-faq-edit.php?id=<?= (int)$faqId ?>">Edit</a><a class="btn btn-outline btn-sm" href="manage-faq.php">Back</a>
</div>
</header>

  <section class="portal-card ">
<h2>Question & Answer</h2>
<dl class="portal-details">
<div><dt>Question / Keyword</dt><dd><?= tmq_escape($faq['question']) ?></dd></div>
<div><dt>Answer</dt><dd><?= nl2br(tmq_escape($faq['answer'])) ?></dd></div>
<div><dt>Status</dt><dd><span class="portal-badge <?= $faq['status'] === 'active' ? 'good' : '' ?>"><?= tmq_escape(ucfirst($faq['status'])) ?></span></dd></div>
<div><dt>Keywords</dt><dd><?php if (!$faqKeywords): ?>No matching phrases.<?php else: ?><?= tmq_escape(implode(', ', array_slice($faqKeywords, 0, 100))) ?><?php if (count($faqKeywords) > 100): ?> (first 100 shown)<?php endif; ?><?php endif; ?></dd></div>
</dl>
</section>

</main>
<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
</div>
</div><script src="../assets/js/action-confirmation.js"></script></body></html>
