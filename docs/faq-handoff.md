# FAQ Handoff

## Completed

Phase 3H-2B-1 connects the existing public, Student/Tutor FAQ accordions and Admin FAQ pages to `faqs` and `faq_keywords`. Existing styling is retained. Chatbot NOT implemented yet. No schema changes or approved design changes. No shared core application code changed.

## Files Created

- `includes/faqs.php`: bounded queries, validation, transactional FAQ/keyword persistence.
- `includes/faq-public.php`: read-only active FAQ controller.
- `includes/faq-accordion.php`: shared escaped public/account rendering.
- `includes/faq-admin.php`: Admin controller.
- `includes/faq-form.php`: shared create/edit form.
- `tests/faqs.test.py`: focused disposable integration coverage.
- `docs/faq-handoff.md`.

## Files Modified

- `help.php`, `student/student-faq.php`, `tutor/tutor-faq.php`: replace static FAQ samples with database content.
- `admin/manage-faq.php`: actual records, pagination and record links; remove prototype deletion.
- `admin/manage-faq-addNew.php`, `admin/manage-faq-edit.php`: persisted forms.
- `admin/manage-faq-view.php`: actual question, answer, status and escaped keywords.
- `tests/authentication.test.py`: expect 404 for FAQ detail/edit without a record ID, retaining all role checks.

## FAQ Model

Actual schema inspected in `database/tutormequran.sql` and verified through disposable MariaDB SHOW COLUMNS / SHOW CREATE TABLE. Approved design sections 3.20 and 3.21 support the existing UI.

`faqs`: unsigned INT `faq_id` primary key; required `question` VARCHAR(500), `answer` TEXT, `status` VARCHAR(16) with binary active/inactive CHECK; required `created_by_admin_id` and `updated_by_admin_id` foreign keys to `admins`; required DATETIME `created_at` and `updated_at`. Status and Admin foreign keys are indexed. No category, position, question uniqueness or per-author access restriction exists. Application timestamps use UTC; creation author is retained and latest editor comes from the authenticated Admin.

`faq_keywords`: unsigned INT `faq_id` FK to `faqs` and required `keyword` VARCHAR(100); composite primary key `(faq_id, keyword)` and index `(keyword, faq_id)`. Text uses utf8mb4_unicode_ci; status uses utf8mb4_bin. All relevant FKs use RESTRICT. No tables, columns, constraints, indexes, SQL schema file or approved design were changed.

## Public FAQ

Visibility rule: only `status='active'`. All three existing accordions use the same source, without invented role/category filtering. Questions and answers are escaped; answer line breaks are preserved with escaped text plus `nl2br`. Empty databases show an explicit empty state, never demo records.

Deterministic `faq_id ASC` cursor pagination fetches at most 51 records, displays 50 and provides a continuation link. IDs are validated unsigned integers. No existing search/filter UI required connecting. Public FAQ endpoints accept GET only. No chatbot matching or chatbot page changes.

## Admin Management

Authenticated active Admins may list/view all FAQs, create, edit and activate/deactivate using the existing status field. This is shared Admin management, not creator-only ownership. No hard-delete endpoint. Missing detail/edit records return 404. Other methods and POSTs to read-only pages return 405. Saves redirect to the saved record.

## Keywords

The existing Question / Keyword input supplies an optional phrase, consistent with design section 3.21; no separate keyword editor was invented. After question trimming, Unicode whitespace runs become one space and Unicode letters are lowercased. Punctuation and markup remain plain text. If the normalized phrase fits 100 Unicode characters, it is inserted for that FAQ; longer questions remain valid up to 500 characters and produce no derived phrase. Nothing is truncated.

On edit, the old question-derived phrase is removed only from the edited FAQ when it changes; the new eligible phrase is added. Other stored phrases are retained. Composite-PK collation handles equivalent duplicates, including case/accent equivalence. Different FAQs may share phrases. The detail view displays at most 100 phrases and labels overflow. Submitted keyword/FAQ ownership fields cannot redirect writes: the authorized route record is used for every keyword statement. FAQ and keyword writes commit or roll back together. These are future chatbot matching data only.

## Validation

Question/answer/status require scalar strings and valid UTF-8. Unicode surrounding whitespace is trimmed; required empty values fail. Question limit is 500 Unicode characters; TEXT answer limit is 65,535 UTF-8 bytes. Status uses the exact active/inactive allowlist. Optional derived keywords respect 100 Unicode characters after normalization. Oversized input is rejected without silent truncation. Stored HTML/script text is preserved and escaped in lists, detail pages, accordions and form values. Invalid input returns controlled 422 responses.

## Authorization

Existing pre-output role guards protect Admin and account pages. Guest, Student and Tutor mutation attempts are denied. Every save rechecks and locks the actual active Admin account/subtype inside its transaction; submitted role, user, author or editor IDs have no authority. Public users can only read eligible answers. No non-FAQ domain writes occur.

## Security

POST and existing CSRF are required for every mutation. SQL uses prepared statements and fixed query structure. Updates lock the target FAQ; keyword changes are always scoped to its ID. No HTML execution, raw SQL diagnostics, hard deletion or notification generation. Unexpected database errors roll back and return a controlled 503. Existing session serialization applies; there is no invented optimistic-version field or cross-session form deduplication.

## Test Results

Run on 2026-11-09 using a task-created MariaDB 10.4.32 instance bound to loopback port 33326. Every suite used a randomly named disposable database and dropped it afterward. The instance then shut down normally; its shutdown log and process exit were verified before removing only its temporary runtime and marker. The real database and retained PID 21932/runtime were untouched.

- `tests/faqs.test.py`: **144 assertions passed**, zero failures in the final run. Covers active/empty listing, inactive hiding, bounded deterministic continuation, escaped XSS across public/account/Admin views, Admin access/create/edit/status, required fields, scalar types, schema boundaries, UTF-8, CSRF, methods, unauthorized roles, keyword normalization/collation duplicates, foreign submitted IDs, inactive Admin denial, and full snapshots of every other table (including populated bookings, support requests and notifications).
- `tests/authentication.test.py`: **798 assertions passed**, 96 protected pages.
- `tests/support_requests.test.py`: **117 assertions passed**.
- PHP lint: all 12 created/modified PHP files passed.
- `git diff --check`: passed.

Earlier focused runs exposed test fixture issues (notification column name and PowerShell-corrupted Unicode literals); those fixtures were corrected before the successful run. No application regression failure required broader testing. Full historical regression is deferred to final integration.

## Deferred

Chatbot NOT implemented yet: no matching, ranking, replies, conversation storage or AI integration. No independent keyword editor, new FAQ filters/categories, reports, exports, support changes, notifications or final integration. Full historical regression deferred to final integration.

## Known Issues

No blocking FAQ issues identified. Questions longer than the normalized keyword limit intentionally have no derived phrase; other stored keywords remain usable by future integration. Concurrent independent Admin edits use last committed edit semantics; no schema version field exists. Visual styling was preserved but no browser screenshot comparison was performed. The unrelated retained PID 21932 cleanup issue remains outside this phase.
