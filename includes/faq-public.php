<?php
require_once __DIR__ . '/faqs.php';
try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        header('Allow: GET'); http_response_code(405); exit('Method not allowed.');
    }
    $faqAfter = isset($_GET['after']) ? tmq_faq_id($_GET['after']) : 0;
    $faqRows = tmq_faq_list(true, $faqAfter);
} catch (DomainException $error) {
    http_response_code(422); exit(tmq_escape($error->getMessage()));
} catch (Throwable $error) {
    http_response_code(503); exit('FAQs are temporarily unavailable. Please try again later.');
}
