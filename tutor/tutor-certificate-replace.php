<?php
require_once __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/profile-page.php';
$assetPrefix = '../';
$pageTitle = 'Replace application certificate - TutorMeQuran';
$pageDescription = 'Your TutorMeQuran account and profile information.';
$tutorApplicant = true;
$homeFooter = false;
$footerHelp = 'tutor-help.php';
$footerFaq = 'tutor-faq.php';
?>
<?php require __DIR__ . '/../includes/header.php'; ?><body class="portal-page tutor-workspace"><a href="#main-content" class="portal-skip">Skip to content</a>
<?php require __DIR__ . '/../includes/tutor-header.php'; ?>

<main id="main-content" class="container portal-main">
<header class="portal-heading">
<div><span class="section-subtitle">Tutor studio</span>
<h1>Replace application certificate</h1>
<p>Your saved account and profile information.</p>
</div>
</header>
<?php tmq_profile_notice(); ?>
<section class="portal-card"><h2>Qualification review</h2><p class="field-help">Use this form for an application correction or a higher qualification. Your current approved qualification remains locked and unchanged while a new submission is reviewed.</p></section><section class="portal-card"><h2>Certificate submission</h2><form class="portal-form" method="post" enctype="multipart/form-data" data-profile-form><?= tmq_csrf_field() ?><input type="hidden" name="profile_action" value="certificate"><div class="form-group"><label for="replacesCertificate" class="form-label">Submission to correct (corrections only)</label><select class="form-control" id="replacesCertificate" name="replacesCertificate"><option value="">No earlier submission</option><?php foreach ($certificateHistory as $item): if (!in_array($item['status'], ['pending','rejected'], true)) { continue; } ?><option value="<?= (int)$item['certificate_id'] ?>"><?= tmq_escape($item['certificate_name'] . ' ? ' . $item['status']) ?></option><?php endforeach; ?></select></div><div class="portal-fields"><div class="form-group"><label class="form-label" for="x-submissionPurpose">Submission Purpose</label><select id="x-submissionPurpose" name="submissionPurpose" class="form-control"><option value="initial">Initial qualification</option><option value="application_correction">Application correction</option><option value="higher_qualification">Higher qualification</option></select></div><div class="form-group"><label class="form-label" for="x-certificateType">Certificate Type</label><input id="x-certificateType" name="certificateType" class="form-control" type="text" value=""></div><div class="form-group"><label class="form-label" for="x-qualificationLevel">Qualification Level</label><select id="x-qualificationLevel" name="qualificationLevel" class="form-control"><option value="ijazah">Ijazah</option><option value="advanced_ijazah">Advanced Ijazah</option><option value="diploma">Diploma</option><option value="bachelor">Bachelor?s degree</option><option value="master">Master?s degree</option><option value="doctorate">Doctorate</option></select></div><div class="form-group"><label class="form-label" for="x-certificateName">Certificate Name</label><input id="x-certificateName" name="certificateName" class="form-control" type="text" value=""></div><div class="form-group"><label class="form-label" for="x-certificateYear">Certificate Year</label><input id="x-certificateYear" name="certificateYear" class="form-control" type="number" value=""></div><div class="form-group"><label class="form-label" for="x-certificateFile">Certificate File</label><input id="x-certificateFile" name="certificateFile" class="form-control" type="file" accept=".pdf,.jpg,.jpeg,.png" required></div><div class="form-group"><label class="form-label" for="x-certificateDescription">Notes</label><textarea id="x-certificateDescription" name="certificateDescription" class="form-control" rows="3"></textarea></div></div><button type="submit" class="btn btn-primary">Submit for Approval</button></form></section><p class="field-help">One file per submission. An approved certificate is never overwritten by an unreviewed upload. Preserve prior approved evidence when the backend is implemented.</p>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?><script src="../assets/js/student-workspace.js"></script><script src="../assets/js/tutor-workflow.js"></script><script src="../assets/js/action-confirmation.js"></script></body></html>
