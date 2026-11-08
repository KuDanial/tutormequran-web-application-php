<?php
require_once __DIR__ . '/faqs.php';
$faqError = '';
$faq = ['question'=>'', 'answer'=>'', 'status'=>'active'];
$faqKeywords = [];
$faqId = null;
try {
    $faqUser = tmq_require_role('admin');
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, ['GET', 'POST'], true) || ($method === 'POST' && !in_array($faqMode, ['add', 'edit'], true))) {
        header('Allow: ' . (in_array($faqMode, ['add', 'edit'], true) ? 'GET, POST' : 'GET'));
        http_response_code(405); exit('Method not allowed.');
    }
    if ($method === 'POST') { tmq_check_csrf(); }
    if (in_array($faqMode, ['edit', 'view'], true)) {
        if (!isset($_GET['id'])) { throw new DomainException('FAQ not found.', 404); }
        $faqId = tmq_faq_id($_GET['id']);
        $q = tmq_db()->prepare('SELECT * FROM faqs WHERE faq_id=?');
        $q->execute([$faqId]);
        $faq = $q->fetch();
        if (!$faq) { throw new DomainException('FAQ not found.', 404); }
    }
    if ($method === 'POST') {
        if (tmq_input($_POST, 'faq_action') !== 'save') { throw new DomainException('Invalid FAQ action.'); }
        $faqId = tmq_faq_save($faqUser, $faqId, $_POST);
        tmq_redirect('admin/manage-faq-view.php?id=' . $faqId);
    }
    if ($faqMode === 'list') {
        $faqAfter = isset($_GET['after']) ? tmq_faq_id($_GET['after']) : 0;
        $faqRows = tmq_faq_list(false, $faqAfter);
    }
    if ($faqMode === 'view') {
        $q = tmq_db()->prepare('SELECT keyword FROM faq_keywords WHERE faq_id=? ORDER BY keyword LIMIT 101');
        $q->execute([$faqId]);
        $faqKeywords = $q->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (DomainException $error) {
    $code = in_array($error->getCode(), [403, 404], true) ? $error->getCode() : (http_response_code() === 403 ? 403 : 422);
    http_response_code($code);
    if ($code !== 422 || !in_array($faqMode, ['add', 'edit'], true)) { exit(tmq_escape($error->getMessage())); }
    $faqError = $error->getMessage();
    $faq = ['question'=>tmq_input($_POST, 'questionKeyword'), 'answer'=>tmq_input($_POST, 'answer'), 'status'=>tmq_input($_POST, 'faqStatus')];
} catch (Throwable $error) {
    http_response_code(503); exit('FAQs are temporarily unavailable. Please try again later.');
}
