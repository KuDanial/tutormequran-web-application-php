<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function tmq_faq_text(mixed $value, string $label, int $limit, bool $bytes = false): string
{
    if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
        throw new DomainException($label . ' must be valid UTF-8 text.');
    }
    $value = preg_replace('/\A\s+|\s+\z/u', '', $value);
    if ($value === '' || ($bytes ? strlen($value) : mb_strlen($value, 'UTF-8')) > $limit) {
        throw new DomainException($label . ' is required (maximum ' . $limit . ($bytes ? ' bytes).' : ' characters).'));
    }
    return $value;
}

// The approved single Question / Keyword field supplies an optional phrase.
// Long questions remain valid; never truncate them to manufacture a keyword.
function tmq_faq_phrase(string $question): ?string
{
    $phrase = mb_strtolower(preg_replace('/\s+/u', ' ', $question), 'UTF-8');
    return mb_strlen($phrase, 'UTF-8') <= 100 ? $phrase : null;
}

function tmq_faq_id(mixed $value): int
{
    if (!is_string($value) || !preg_match('/\A[1-9][0-9]{0,9}\z/', $value) || (float)$value > 4294967295) {
        throw new DomainException('Select a valid FAQ.');
    }
    return (int)$value;
}

function tmq_faq_list(bool $public, int $after): array
{
    $q = tmq_db()->prepare('SELECT faq_id, question, answer, status FROM faqs WHERE faq_id > ?'
        . ($public ? " AND status = 'active'" : '') . ' ORDER BY faq_id ASC LIMIT 51');
    $q->execute([$after]);
    return $q->fetchAll();
}

function tmq_faq_save(array $user, ?int $id, array $post): int
{
    $question = tmq_faq_text($post['questionKeyword'] ?? null, 'Question', 500);
    $answer = tmq_faq_text($post['answer'] ?? null, 'Answer', 65535, true);
    $status = tmq_faq_text($post['faqStatus'] ?? null, 'Status', 16);
    if (!in_array($status, ['active', 'inactive'], true)) { throw new DomainException('Choose Active or Inactive.'); }
    $db = tmq_db();
    $db->beginTransaction();
    try {
        // Lock the active account while saving; submitted identities have no authority.
        $q = $db->prepare("SELECT a.admin_id FROM admins a JOIN users u ON u.user_id=a.user_id WHERE u.user_id=? AND u.role='admin' AND u.account_status='active' FOR UPDATE");
        $q->execute([$user['user_id']]);
        $admin = $q->fetchColumn();
        if (!$admin) { throw new DomainException('Active Admin access required.', 403); }
        $old = null;
        if ($id !== null) {
            $q = $db->prepare('SELECT question FROM faqs WHERE faq_id=? FOR UPDATE');
            $q->execute([$id]);
            $old = $q->fetchColumn();
            if ($old === false) { throw new DomainException('FAQ not found.', 404); }
            $q = $db->prepare('UPDATE faqs SET question=?, answer=?, status=?, updated_by_admin_id=?, updated_at=UTC_TIMESTAMP() WHERE faq_id=?');
            $q->execute([$question, $answer, $status, $admin, $id]);
        } else {
            $q = $db->prepare('INSERT INTO faqs (question,answer,status,created_by_admin_id,updated_by_admin_id,created_at,updated_at) VALUES (?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())');
            $q->execute([$question, $answer, $status, $admin, $admin]);
            $id = (int)$db->lastInsertId();
        }
        $phrase = tmq_faq_phrase($question);
        // Synchronize only the old question-derived phrase; retain other approved phrases.
        if ($old !== null && tmq_faq_phrase($old) !== $phrase) {
            $q = $db->prepare('DELETE FROM faq_keywords WHERE faq_id=? AND keyword=?');
            $q->execute([$id, tmq_faq_phrase($old)]);
        }
        if ($phrase !== null) {
            // Let the composite PK and its actual collation resolve equivalent phrases.
            $q = $db->prepare('INSERT INTO faq_keywords (faq_id,keyword) VALUES (?,?) ON DUPLICATE KEY UPDATE keyword=keyword');
            $q->execute([$id, $phrase]);
        }
        $db->commit();
        return $id;
    } catch (Throwable $error) {
        if ($db->inTransaction()) { $db->rollBack(); }
        throw $error;
    }
}
