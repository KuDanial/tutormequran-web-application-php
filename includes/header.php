<?php
// Each page supplies its metadata and root-relative asset prefix.
// $pageHead is trusted, page-authored markup (the existing homepage style block).
if (isset($authUser) && in_array($authUser['role'], ['student', 'tutor'], true)) {
    require_once __DIR__ . '/profiles.php';
    try {
        $headerProfile = tmq_profile_user((int)$authUser['user_id']);
        if ($authUser['role'] === 'tutor') {
            $tutorApplicant = !tmq_tutor_approved((int)$authUser['user_id']);
        }
    } catch (Throwable $error) {
        http_response_code(503);
        exit('Account details are temporarily unavailable. Please try again later.');
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= tmq_escape($pageTitle) ?></title><meta name="description" content="<?= tmq_escape($pageDescription) ?>"><?php if (!empty($pageKeywords)): ?><meta name="keywords" content="<?= tmq_escape($pageKeywords) ?>"><?php endif; ?><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><link rel="stylesheet" href="<?= tmq_escape($assetPrefix) ?>assets/css/style.css"><?php foreach ($extraStyles ?? [] as $stylesheet): ?><link rel="stylesheet" href="<?= tmq_escape($assetPrefix . $stylesheet) ?>"><?php endforeach; ?><?= $pageHead ?? '' ?><link rel="stylesheet" href="<?= tmq_escape($assetPrefix) ?>assets/css/site-contact.css"><script src="<?= tmq_escape($assetPrefix) ?>assets/js/site-contact.js" defer></script><link rel="stylesheet" href="<?= tmq_escape($assetPrefix) ?>assets/css/auth.css"></head>
