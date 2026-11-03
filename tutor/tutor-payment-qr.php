<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/profile-page.php';
$assetPrefix = '../';
$pageTitle = 'DuitNow QR - TutorMeQuran';
$pageDescription = 'Your TutorMeQuran account and profile information.';
$tutorApplicant = false;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main student-hub tutor-hub">
<header class="portal-heading"><div><span class="section-subtitle">Tutor studio</span><h1>DuitNow QR</h1><p>Your saved account and profile information.</p></div></header>
<?php tmq_profile_notice(); ?>
<section class="portal-card"><form  class="portal-form" method="post" enctype="multipart/form-data" data-profile-form><?= tmq_csrf_field() ?><input type="hidden" name="profile_action" value="qr"><div class="form-group"><label class="form-label" for="duitnow-qr-file">Your DuitNow QR image</label><input id="duitnow-qr-file" name="duitnowQr" class="form-control" type="file" accept="image/png,image/jpeg" required aria-describedby="duitnow-qr-help"></div><p id="duitnow-qr-help" class="field-help">JPG or PNG, up to 5 MB. Use the original QR supplied by your bank and check that the recipient is you.</p><?php if (!empty($profile['duitnow_qr_path'])): ?><img src="<?= tmq_escape(tmq_url('profile-file.php?kind=qr&id=' . (int)$profile['tutor_id'])) ?>" class="duitnow-tutor-preview" alt="Your DuitNow QR" width="260" height="260"><?php endif; ?><div class="portal-actions"><button type="submit" class="btn btn-primary">Save DuitNow QR</button><a class="btn btn-outline btn-sm" href="tutor-payments.php">View payments</a></div><p data-duitnow-status role="status" class="field-help"></p></form></section><p class="field-help">Your QR provides payment instructions. Saving it does not confirm that a student has paid.</p>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
