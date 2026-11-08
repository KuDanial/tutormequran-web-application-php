<?php
declare(strict_types=1);
require_once __DIR__ . '/faqs.php';

function tmq_chatbot_normalize(string $text): string
{
    // Reuse FAQ normalization; user questions may exceed the keyword's 100-character limit.
    return tmq_faq_phrase($text) ?? mb_strtolower(preg_replace('/\s+/u', ' ', $text), 'UTF-8');
}

function tmq_chatbot_tokens(string $text): array
{
    preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);
    return $matches[0];
}

function tmq_chatbot_meaningful(array $tokens): array
{
    // Small, explicit stop list, not a learned language model.
    $common = explode(' ', 'a an and are as at be by can could do does for from how i in is it me my of on or please the this to what when where which who why will with would you your help need want about tell saya anda dan di ke dari untuk yang apa bagaimana boleh ingin');
    return array_values(array_unique(array_filter($tokens,
        static fn(string $token): bool => mb_strlen($token, 'UTF-8') >= 3 && !in_array($token, $common, true))));
}

function tmq_chatbot_answer(string $question): ?array
{
    $normalized = tmq_chatbot_normalize($question);
    $tokens = tmq_chatbot_tokens($normalized);
    $meaningful = tmq_chatbot_meaningful($tokens);
    $sentence = ' ' . implode(' ', $tokens) . ' ';
    $best = null;
    $bestRank = [0, 0];
    $db = tmq_db();
    // Only keyword data is scanned, never a second copy of the answers.
    $query = $db->prepare("SELECT k.faq_id, k.keyword FROM faq_keywords k JOIN faqs f ON f.faq_id=k.faq_id WHERE f.status='active' ORDER BY k.faq_id ASC, k.keyword ASC");
    $query->execute();
    while ($row = $query->fetch()) {
        $keyword = tmq_chatbot_normalize($row['keyword']);
        $rank = [0, 0];
        if ($normalized === $keyword) {
            $rank = [3, 0];
        } else {
            $keywordTokens = tmq_chatbot_tokens($keyword);
            $words = tmq_chatbot_meaningful($keywordTokens);
            $overlap = count(array_intersect($meaningful, $words));
            // Partial matches require at least two meaningful words, never just "how" or "help".
            if (count($words) >= 2 && str_contains($sentence, ' ' . implode(' ', $keywordTokens) . ' ')) {
                $rank = [2, count($words)];
            } elseif ($overlap >= 2 && $overlap * 5 >= count($words) * 3 && $overlap * 5 >= count($meaningful) * 3) {
                $rank = [1, intdiv(2000 * $overlap, count($words) + count($meaningful))];
            }
        }
        // Rows arrive by faq_id ASC; equal scores keep the lower FAQ ID.
        if ($rank > $bestRank) { $bestRank = $rank; $best = (int)$row['faq_id']; }
    }
    $query->closeCursor();
    if ($best === null) { return null; }
    // Recheck visibility when reading the winning answer; no cached answers.
    $query = $db->prepare("SELECT question, answer FROM faqs WHERE faq_id=? AND status='active'");
    $query->execute([$best]);
    return $query->fetch() ?: null;
}
