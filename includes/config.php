<?php
// Shared presentation and request bootstrap. Guards run before any page output.
function tmq_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
require_once __DIR__ . '/auth.php';
tmq_bootstrap_auth();
