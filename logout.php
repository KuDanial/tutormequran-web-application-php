<?php
require_once __DIR__ . '/includes/config.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Use the Log Out button to sign out.');
}
try { tmq_check_csrf(); }
catch (DomainException $error) { exit('Your form expired. Reload the page and try again.'); }
tmq_destroy_session();
tmq_redirect('login.php');
