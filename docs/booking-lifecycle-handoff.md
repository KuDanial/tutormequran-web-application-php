# Booking Lifecycle Handoff

## Completed

Phase 3E-3: database-backed Student/Tutor cancellation, exceptional Admin cancellation, and two-sided lesson completion. Existing page shells and booking navigation retained. No schema changes or payment implementation.

## Files Created

- `includes/booking-cancellations.php`
- `includes/booking-completion.php`
- `includes/booking-lifecycle-content.php`
- `tests/booking_lifecycle.test.py`
- `docs/booking-lifecycle-handoff.md`

## Files Modified

- `includes/bookings.php`: scoped reads include cancellation actor/metadata and completion timestamps.
- `includes/booking-changes.php`: reuse lock protocol for explicit Admin cancellation; block material changes after any completion confirmation.
- `includes/booking-page.php`: shared cancellation/session controller, POST/CSRF, redirects and controlled errors.
- `includes/booking-detail-content.php`, `includes/booking-list-content.php`: real lifecycle state, history and action navigation.
- `student/student-booking-cancel.php`, `student/student-booking-session.php`, `student/student-booking-completed.php`.
- `tutor/tutor-booking-cancel.php`, `tutor/tutor-booking-session.php`, `tutor/tutor-booking-completed.php`.
- `admin/manage-bookings-cancel.php`, `admin/manage-bookings-closed.php`: exceptional cancellation and explicitly deferred, read-only closure page.
- `tests/booking_management.test.py`: permit implemented lifecycle links, retain payment/closing deferral assertion.
- `tests/booking_changes.test.py`: clear completion timestamps when resetting the synthetic terminal-state fixture to an uncompleted accepted booking.

## Student Cancellation

Own requested or accepted booking only, through the lesson's Malaysia calendar date. Canonical POST action `lifecycle_action=cancel` on `student/student-booking-cancel.php?id=...`. No terminal booking cancellation. Required trimmed plain-text reason; markup stripped and UTF-8 validity/65,535-byte TEXT limit enforced. All output escaped.

## Tutor Cancellation

Own accepted booking only, with the same date rules. Tutor requested bookings continue to use the existing Reject action. Canonical Tutor cancellation endpoint is `tutor/tutor-booking-cancel.php?id=...`.

## Admin Exceptional Cancellation

`admin/manage-bookings-cancel.php?id=...` permits authenticated active Admin cancellation of requested/accepted bookings for a documented manual exception, including after the lesson date. Rejected/cancelled/completed/closed remain ineligible. Actual schema uses `cancelled_by_user_id` (INT UNSIGNED FK), not `cancelled_by`; UI derives Student/Tutor/Admin from that user's role. Stores `cancellation_reason` (TEXT), `cancelled_at` (UTC DATETIME) and `cancellation_timing` (VARCHAR(24)). No participant can use the Admin endpoint.

## Cancellation Timing

`early` means current Asia/Kuala_Lumpur calendar date is before the lesson start date. `same_day` means equal dates, including after the lesson's start/end on that date. This phase's explicit date-based requirement supersedes the older proposal's before-start restriction. No rolling 24-hour rule. `after_lesson_date` is Admin exceptional handling only. Eligibility, classification and cancellation timestamp use one server instant captured after locking. No automatic cancellation fee, refund or penalty; rate snapshot and expected amount remain unchanged.

## Slot Release

Set booking status to cancelled; the existing generated `occupied_slot_id` becomes NULL. Never delete/recreate availability, alter its interval/status, or remove the booking's historical `slot_id`. Future available slots are reusable through existing creation validation. Past/unavailable slots are not offered for resale.

## Pending Change Interaction

Lock the pending request and withdraw it in the cancellation transaction, as approved design rule 22 requires. Preserve all original/proposed history. Set `responded_at`, leave counterpart-only `responded_by_user_id` NULL, and record an automatic-withdrawal explanation in `response_note`. The booking records the actual cancellation actor/reason. Cancelled proposals cannot later be accepted. Arrangement details and both arrangement timestamps remain historical data.

## Student Completion

Canonical mutation is POST `lifecycle_action=complete` on `student/student-booking-session.php?id=...`, with CSRF and the displayed agreement version. Require own accepted booking, both arrangement timestamps and effective scheduled end reached. Sets only `student_completed_at`; first confirmation leaves accepted status. Session page shows current schedule, arrangement and each participant's completion. Completed page is read-only.

## Tutor Completion

Same rules at `tutor/tutor-booking-session.php?id=...`, setting only `tutor_completed_at`. One shared helper implements both roles. Repeated confirmation preserves the first timestamp; repeats on an already completed booking are idempotent. Closed/cancelled/rejected/requested mutations fail. Stale agreement versions fail before writing.

## Completed Transition

The second valid confirmation atomically stores its timestamp and derives status completed from both completion timestamps. Retain both timestamps, original rate, expected amount, schedule, mode, arrangement and agreement version. Completed is NOT closed. Both confirmations leave the required future feedback eligibility state; neither feedback nor payments are created.

## Slot Occupancy After Completion

Completed booking remains historically occupying its slot through the unchanged generated occupancy expression. No manual release or availability update. Closed transition belongs exclusively to later Admin payment verification.

## Concurrency

Reuse Student row -> Tutor row -> ascending relevant slots -> booking/change records, with post-lock identity, ownership, active actor, locator, status, timing/version and pending-state checks. Cancellation locks only its current slot: pending targets are unheld and no target slot is altered. Existing change acceptance locks both relevant slots. Guarded updates and rollback prevent partial transitions; lock/deadlock conflicts return controlled responses. Concurrent completion stores both timestamps; duplicates establish one timestamp. Cancellation versus second completion has one terminal winner. Cancellation versus proposal acceptance/arrangement confirmation preserves a coherent serialized history. End-time/future-start eligibility and the completion guard prevent later material changes.

## Security

Existing authenticated session/RBAC/account guards, scoped reads, prepared statements, POST-only mutations, CSRF, escaped output and transactional row locks. Submitted identities, cancellation actor, counterpart timestamps, status and amounts have no authority. GET never mutates. Foreign/nonexistent booking IDs share a controlled 404; SQL diagnostics are not displayed. No real application data was changed.

## Test Results

- Lifecycle: 243 assertions passed, including the same-day participant cancellation/second-completion race.
- Phase 3E-2 coordination: 172 passed.
- Phase 3E-1 changes: 181 passed.
- Phase 3D-2B management: 100 passed.
- Phase 3D-2A creation: 75 passed.
- Phase 3D-1 availability: 66 passed.
- Phase 3C profiles: 207 passed.
- Phase 3B authentication: 763 passed, covering all 91 protected pages.
- Total: 1,807 integration assertions; zero final failures. Existing JavaScript: 10 passed. All 16 created/modified PHP files linted successfully; `git diff --check` passed.

All mutations used randomly named disposable databases on a separately initialized MariaDB 10.4.32 temporary data directory, bound to 127.0.0.1:33317 (`TMQ_DB_PORT=33317`). Each suite removed its own database/uploads; the final instance contained only bootstrap system/test databases. Normal shutdown succeeded, the log recorded Shutdown complete, and process exit was verified before deleting only the task-created temporary directory and marker. No interaction with the pre-existing MariaDB instance, repair, real database mutation or schema change occurred. Initial fixture issues (Admin profile URL and stale synthetic completion timestamps) were corrected before the passing final runs.

## Deferred

Payment creation, receipts, Tutor payment acknowledgement, Admin payment verification/closed transition, refunds/fees/penalties, no-show/dispute automation, chat, notifications, feedback backend, support/FAQ/chatbot, reports and exports. Admin closure page explicitly states deferral and rejects POST.

## Known Issues

No workflow/schema blocker found. The pre-existing MariaDB/XAMPP process issue was left untouched. HTTP-rendered pages and backend state were tested; no visual browser screenshot comparison was performed. Before 01:00 Malaysia time no same-date whole-hour lesson can already have ended: the lifecycle suite explains this if that one participant terminal race is ineligible; independent Admin terminal races still run.
