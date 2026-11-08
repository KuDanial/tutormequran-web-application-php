# Chatbot Handoff

## Completed

Phase 3H-2B-2 connects the existing public `chatbot.php` interface to a free, simple, database-backed rule-based FAQ chatbot. No AI/ML, LLM, embeddings, paid/external API, external search or third-party chatbot service. The existing layout/classes remain; prototype quick-question buttons and preview copy are replaced by a working POST form and current-request response. No JavaScript or CSS changes.

## Files Created

- `includes/faq-chatbot.php`: shared normalization, token rules and read-only matcher.
- `tests/chatbot.test.py`: focused disposable integration tests.
- `docs/chatbot-handoff.md`.

## Files Modified

- `chatbot.php`: POST/CSRF handling, validation, matched answer/fallback and escaped rendering.

## Chatbot Model

One valid question produces either an existing FAQ answer or a controlled fallback. The chatbot never fabricates answers or attempts arbitrary world knowledge. Invalid requests receive controlled feedback; database unavailability receives a controlled 503. The implementation is ordinary PHP string/token matching and SQL reads, with no learned model or external dependencies.

## FAQ Knowledge Source

Only ACTIVE `faqs` can answer. `faq_keywords` supplies all matching phrases; questions themselves are not an alternative matching index. Answers come directly from the winning FAQ row, not duplicated arrays/files. The final answer query rechecks active status. Each request reads current database content, so Admin edits/deactivation naturally apply without cache invalidation.

FAQs with normalized questions longer than 100 characters can match only through any existing valid keyword records. No keyword insertion/truncation or Phase 3H-2B-1 management changes. No schema changes, including tables, columns, indexes, constraints or approved design/SQL files.

## Matching

Reuse `tmq_faq_text` for UTF-8, surrounding Unicode whitespace trimming and length validation. Reuse `tmq_faq_phrase` normalization, with the same whitespace-collapse/lowercase expression for user questions exceeding the keyword limit. Stored data is never modified for matching.

Exact matching compares normalized strings. Partial matching tokenizes using a fixed Unicode letter/number expression; submitted text is never treated as a regular expression. A small explicit English/Malay common-word list and minimum token length of three Unicode characters determine meaningful words; duplicates do not inflate scores.

Phrase containment requires the keyword's entire token sequence inside the question, with token boundaries and at least two meaningful keyword words. Punctuation acts as a token separator. Token overlap requires at least two distinct meaningful shared words and at least 60% coverage of both the question's and keyword's meaningful words. One common/trivial token cannot produce a partial match. Exact administrator-defined keywords remain eligible even if short.

## Ranking

1. Exact normalized keyword match: tier 3.
2. Phrase containment: tier 2; more distinct meaningful keyword words wins.
3. Token overlap: tier 1; score is the integer floor of `2000 * shared_words / (question_words + keyword_words)`.

Higher tier always wins, then higher within-tier score, then lower `faq_id`. Active keyword rows are read in `faq_id ASC, keyword ASC` order; equal ranks retain the earlier FAQ. A FAQ's best keyword rank determines its eligibility. No fuzzy libraries or Levenshtein scan.

## Fallback

Unknown valid questions receive: “Sorry, I could not find a suitable answer for that question. Please check the FAQ or find support options for more help.” The fixed link is `help.php`, the existing public FAQ page with sign-in guidance for support. No support request is created and no external/user-supplied URL is used.

## Public / Account Access

The existing chatbot remains public. Searches found no separate Student/Tutor chatbot pages. Existing public navigation and Student FAQ links already point to `chatbot.php`; authenticated Student, Tutor and Admin users can use that same route and knowledge source. No authentication or shared protected-page code changed. No role-specific knowledge or Admin metadata is exposed.

## Input Validation

POST is used so questions are not placed in the URL. The form uses existing session CSRF conventions. Question must be a scalar, valid UTF-8 string, nonempty after trimming, at most 500 Unicode characters. Invalid/missing/array/oversized input returns 422 without truncation. CSRF failures return 403; unsupported methods return 405. GET displays the form and never submits a query-string question.

## Output Safety

User question, matched FAQ question, answer and errors are escaped with `tmq_escape`. Answer/question line breaks use `nl2br` only after escaping. HTML/script content is displayed as text. Responses contain no FAQ author/editor IDs, Admin identities, keyword internals or inactive answers. Database errors reveal no SQL, schema name, driver diagnostics or stack traces.

## No Persistence

Only the current POST response displays the question/answer. Nothing is written to conversation tables, application tables, files, analytics or session history. The existing session stores its usual CSRF/authentication state only. GET starts with the greeting again. Responses use `Cache-Control: no-store`.

## Security

Fixed SQL structure, prepared statements, input bounds, UTF-8 validation, active filtering/recheck, escaped output, POST and CSRF. No interpretation as SQL, HTML, regex, PHP or commands. No external API calls. No FAQ, keyword, support, notification, booking/payment/chat, feedback, certificate, availability or profile mutations. No domain side effects and no conversation storage.

## Test Results

2026-11-09: disposable MariaDB 10.4.32 on loopback port 33327, random disposable databases only. Real database and retained PID 21932/runtime untouched.

- `tests/chatbot.test.py`: **67 assertions passed, zero failures in the final run**. Covers exact priority/correct FAQ, active filtering, case/Unicode whitespace, phrases, meaningful overlap, deterministic ties, unrelated fallback, input types/limits/UTF-8, user and FAQ XSS, fixed existing fallback route, GET/POST/CSRF, public and authenticated access, current Admin updates/deactivation, long-question keywords, unchanged snapshots of every application table/schema, no session conversation history and controlled database failure. The normal PHP test server disables common outbound network helpers and URL file access; no external API is needed. An initial regex-like fixture also contained a legitimate two-word keyword; it was corrected to test regex non-interpretation without an independent valid phrase match.
- `tests/faqs.test.py`: **144 assertions passed**.
- PHP lint: `chatbot.php` and `includes/faq-chatbot.php` passed.
- `git diff --check`: passed (Git may report its existing LF-to-CRLF conversion notice).
- Authentication/support regressions: not run; no authentication, shared protected-page or support code changed.
- JavaScript tests: not applicable; no JavaScript changed.

Both suites dropped their own databases. The task-created MariaDB instance shut down normally; its shutdown log and process exit were verified before removing only its temporary runtime and marker. Full historical regression is deferred to Phase 3I final integration.

## Deferred

Full historical regression deferred to Phase 3I. No reports, exports, final integration, FAQ management redesign or additional features. AI/ML, external APIs and conversation persistence are outside this project's chatbot scope.

## Known Issues

No blocking issues identified. Rules deliberately favor fallback over weak matches; spelling errors, synonyms and some languages/short words require appropriate Admin keywords. Token overlap is lexical and does not understand negation or intent. Matching scans active keyword rows, appropriate for this small FAQ knowledge base; no large-corpus search subsystem is introduced. No browser screenshot comparison was performed. The unrelated retained PID 21932 cleanup issue is untouched.
