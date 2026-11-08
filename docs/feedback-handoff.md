# Feedback Handoff

## Completed

Phase 3H-1 implements booking feedback only, using the existing approved schema. Student submission/history, Tutor received history, booking navigation and public Tutor reviews now use persisted records. No schema changes or approved SQL/design document changes.

## Files Created

- `includes/feedback.php`
- `includes/feedback-page.php`
- `includes/feedback-content.php`
- `tests/feedback.test.py`
- `docs/feedback-handoff.md`

## Files Modified

- `includes/bookings.php`: include the existing feedback ID in scoped booking reads.
- `includes/booking-detail-content.php`, `includes/booking-list-content.php`: eligible submission or recorded-feedback navigation.
- `student/student-feedback-addNew.php`, `student/student-feedbacks.php`, `tutor/tutor-feedbacks.php`: replace demo content with shared feedback controller/presentation.
- `includes/public-tutors.php`, `tutor-detail.php`: eligible real reviews and derived rating; remove the public detail heading's demo Tutor name.

## Feedback Model

Student -> Tutor only. The approved `feedback` table has `feedback_id`, unique `booking_id`, required `rating`, nullable `comment`, and `created_at`. Author and receiving Tutor derive exclusively from the booking's Student and Tutor relationships. There is no Tutor-authored review model, moderation state, or independently editable participant field.

## Eligibility

Both `student_completed_at IS NOT NULL` AND `tutor_completed_at IS NOT NULL` are required. Payment verification is NOT required and payments are not queried by feedback submission. A completed booking can be eligible before a payment even exists; a closed booking remains eligible. Accepted bookings with both confirmations also satisfy the timestamp rule. Requested, rejected and cancelled bookings are excluded even if inconsistent historical rows carry both timestamps. Passing the calendar end never establishes eligibility.

## Participant Authorization

Existing authenticated active-account/RBAC guards run before output. The Student submission route is `student/student-feedback-addNew.php?id=<booking_id>`. Booking reads are session-scoped. Inside the transaction, submission rechecks the current booking and resolves an active Student subtype from the authenticated user ID. Foreign and missing bookings return controlled not-found responses. Submitted Student/Tutor/user/reviewer/reviewee IDs, role, booking_id body field and timestamps have no authority. Admin and Tutor cannot submit on behalf of a Student.

Student history contains only their authored feedback; Tutor history contains only feedback received on their own bookings. Detail IDs are booking IDs, never an unscoped feedback lookup.

## Rating

Required canonical integer input 1 through 5, matching the actual `TINYINT UNSIGNED` and `ck_feedback_rating`. Missing, array, fractional, scientific-notation, out-of-range and nonnumeric values are rejected server-side. No clamping or rounding.

## Review Text

Comment is optional because the actual column is nullable. Validate scalar UTF-8, trim surrounding whitespace and store empty text as NULL. Enforce the actual MariaDB TEXT capacity of 65,535 bytes after trimming, including multibyte input. Never silently truncate or enlarge the column. Preserve ordinary text and markup characters verbatim; every output uses escaping, so stored HTML/scripts display as text. Preserved newlines display through `white-space: pre-wrap`.

## Duplicate Protection

The actual `UNIQUE(booking_id)` is the final authority. INSERT does not rely on SELECT-before-INSERT. Duplicate-key exceptions roll back and become a controlled 409 response; stale forms, double-clicks and concurrent requests cannot replace the original record. A refresh after a conflict displays recorded feedback instead of an active form. A detail request ignores list pagination so a forged page number cannot hide existing feedback and reopen a form.

## Immutability

No edit, delete, rating replacement or Admin rewrite. Only the explicit `feedback_action=submit` POST is supported on the Student submission page. History routes reject mutations. The form explains permanence before submission.

## Booking Integration

Student booking lists/details/completion pages offer Leave feedback only when both confirmations exist, the status is eligible, and no feedback exists. After submission, Student and Tutor booking pages link to their scoped read-only history. The shared detail template loads the helper itself, including when rendered from change/coordination pages. The Student feedback center also lists actual eligible bookings. Both history and eligible lists use bounded 50-row pages with a 51st row to detect continuation.

## Tutor/Public Display

The approved Student-review purpose and existing public Tutor profile's What students say section establish the intended public display. Replace its deferred placeholder with real feedback. Only the existing active, public, complete, approved Tutor profile gate permits public display. Calculate COUNT and AVG from eligible feedback through its booking, never store an aggregate or show demo ratings. Display the latest 20 reviews, with rating, plain-text comment and Malaysia-local date. Aggregate includes all eligible reviews. Public reviews omit Student names, booking IDs, private lesson data and contact details. Submission explains this visibility; participant history retains the authorized counterpart's name. Empty profiles show No feedback yet.

## Concurrency

Use one transaction and explicitly lock only the booking row. Revalidate Student ownership/account and both completion timestamps after obtaining the lock, then INSERT feedback and commit. No explicit payment, availability or participant row locks; normal FK/unique-index enforcement still applies. Payment verification's existing booking-first lock order is compatible: feedback succeeds whether verification closes the eligible booking before or after it. Deadlock/lock-timeout errors return a controlled retry conflict. Independent HTTP workers/sessions and a held booking lock exercise simultaneous duplicates, payment verification and stale-form rejection after an ineligible state wins. Lock-wait observation is scoped to the disposable database.

## Security

Prepared statements, POST-only mutation, existing CSRF and active-account guards, participant checks, server validation, escaped output, fixed redirects and controlled error handling. A disposable injected database failure verifies rollback without exposing SQL diagnostics. Server UTC records `created_at`; display converts to Malaysia time. No notification type was added: the existing 20-event catalogue and notification schema are unchanged.

Feedback has no booking/payment side effects: it does not change status, completion timestamps, arrangement, agreement version, availability, closure, evidence revisions, receipt data, chat or notifications. Tests compare complete domain-table snapshots as well as an existing unverified payment and already-verified payment/notification state.

## Test Results

| Suite | Passed assertions |
| --- | ---: |
| Phase 3H-1 feedback | 144 |
| Phase 3G-2 notifications | 305 |
| Phase 3G-1 chat | 218 |
| Phase 3F-2 payment verification | 331 |
| Phase 3F-1 payment submission | 244 |
| Phase 3E-3 lifecycle | 243 |
| Phase 3E-2 coordination | 172 |
| Phase 3E-1 booking changes | 181 |
| Phase 3D-2B booking management | 100 |
| Phase 3D-2A booking creation | 75 |
| Phase 3D-1 availability | 66 |
| Phase 3C profiles | 207 |
| Phase 3B authentication (96 protected pages) | 798 |

Total: 3,084 integration assertions and 10 existing JavaScript tests passed; zero final failures. Counts represent one final run per suite, not accumulated reruns. All 11 created/modified PHP files pass lint; `git diff --check` passes. Approved schema/design files are unchanged. An initial booking-change regression exposed a missing helper include in the shared detail template; that was fixed and the suite passed unchanged.

Focused coverage includes approved direction, active-account/role/ownership denial, both completion timestamps, completed before payment/closed eligibility, cancelled/rejected denial, rating bounds/types, optional/oversized/invalid UTF-8 comments, exact TEXT byte boundary, escaped stored markup, CSRF/GET rules, forged identities, unique-key enforcement, immutable history, real public aggregate/privacy, independent concurrent requests, payment verification compatibility and domain-table snapshots. An injected disposable database failure tests controlled rollback. Conflict responses refresh current eligibility before rendering.

Every suite created and dropped its own randomly named disposable database and cleaned its own HTTP servers/uploads. Final SHOW DATABASES contained only bootstrap system/test databases; no restricted test DB accounts remained. `mysqladmin` normal shutdown on 127.0.0.1:33323 succeeded and process exit was verified. Only the exact task-created temporary runtime directory and matching marker were then removed after checking their resolved paths. No real application data, existing database process or XAMPP configuration was changed.

## Deferred

Support, FAQ backend, chatbot, reports, exports, notification redesign, email/SMS/push, payment changes and booking lifecycle redesign. No feedback editing/deletion, Tutor-authored reviews, moderation or new notification events.

## Known Issues

The known pre-existing XAMPP/MariaDB issue remains untouched. Tests use installed MariaDB 10.4.32 binaries with a separate task-created temporary data directory and loopback port 33323. No fake feedback or test accounts are created in the real application database. Public reviews show the latest 20; participant list pagination may shift if new records arrive. HTTP-rendered output/security is tested; no visual browser screenshot comparison was performed.
