# Booking Coordination Handoff

## Completed

Phase 3E-2: database-backed participant coordination pages, physical/online arrangement proposals through the existing change-request workflow, separate individual confirmation, stale-version protection and derived completion. Existing layout/classes retained; demo chat, location sharing, browser-only progression and later completion links removed from these two pages. No schema/design changes. Prior working-tree changes preserved.

## Files Created

- `includes/booking-arrangements.php`: canonical arrangement validation, derived state and transactional individual confirmation.
- `includes/booking-arrangement-content.php`: current details, participant confirmations and arrangement proposal form.
- `assets/js/booking-arrangement.js`: optional mode-appropriate fields for combined proposals; PHP remains authoritative.
- `tests/booking_coordination.test.py`.
- `docs/booking-coordination-handoff.md`.

## Files Modified

- `includes/bookings.php`: existing scoped reads include arrangement fields/version.
- `includes/booking-changes.php`: arrangement-only/combined proposals, expanded no-op detection and acceptance validation; reuse existing history/consent/locking.
- `includes/booking-change-page.php`: coordination actions through the existing controller.
- `includes/booking-change-content.php`: arrangement history comparison and optional combined-proposal fields.
- `includes/booking-detail-content.php`: current arrangement/derived state and coordination action.
- `student/student-booking-coordinate.php`, `tutor/tutor-booking-coordinate.php`: real shared controller/content.
- `student/student-booking-edit.php`, `tutor/tutor-booking-edit.php`: load optional combined-form enhancement.
- `tests/booking_management.test.py`: allow implemented coordination links while retaining deferred completion/cancellation assertions.

## Coordination Access

Use the existing `?id=<booking_id>` route. Authenticated Student/Tutor relationships scope all reads and writes. Other participants receive controlled 404 responses; Admin cannot use participant endpoints/helpers. Non-accepted/past owned bookings remain viewable as history but cannot receive new proposals or confirmations. Admin detail cheaply shows arrangement/version/confirmation information through the shared read-only content; no Admin mutation added.

## Physical Arrangement

Venue text is trimmed, nonempty and at most 500 characters. Initial and replacement venues require counterpart acceptance. Applicable effective field is `venue_address`; online fields are cleared when a physical agreement is applied. Original/proposed values remain in history. No maps, geocoding, distance or location pricing.

## Online Arrangement

Require nonempty platform (maximum 80 characters) and valid HTTP/HTTPS URL (maximum 2,048 characters) for new arrangement details and confirmation. No provider whitelist, external request, reachability check or integration. Effective physical venue is cleared on online acceptance. URLs are displayed as escaped text.

## Arrangement Change Requests

Reuse `booking_change_requests`, its existing conditional pending uniqueness and requester/counterpart metadata. `propose_arrangement` derives slot/mode from the locked current booking and checks the displayed agreement version. The edit form supports `include_arrangement=1` for a single combined schedule/mode/arrangement request. No-op detection compares arrangement values as well as slot/mode. A pending request changes neither the agreement nor occupancy, and prevents a second schedule/mode/arrangement request.

Schedule/mode-only proposals may still leave initial arrangement details unset, preserving Phase 3E-1 behavior; confirmation remains unavailable until complete details are agreed. Explicit arrangement proposals require all applicable fields. Only fields applicable to the proposed mode become effective.

## Accept / Reject / Withdraw

Reuse Phase 3E-1 transactions, participation, accepted/future eligibility, base version, before-value checks and controlled stale responses. Acceptance revalidates arrangement values and relevant slot/mode/overlap rules. Arrangement-only acceptance leaves slot, interval and amount columns untouched. Combined acceptance applies everything once, recalculating duration price only from the immutable original rate. Both cases increment version once and clear both confirmations; neither participant is automatically confirmed.

Rejection/withdrawal retain request history and leave the effective agreement, version, price, occupancy and confirmations unchanged. Existing requester/counterpart rules, optional response notes and withdrawal timestamp convention are preserved.

## Individual Confirmation

POST `confirm_arrangement` on the participant's coordination page with CSRF and the displayed `agreement_version`. Require the active acting participant, accepted/future booking, exact current version and complete valid arrangement. The authenticated role selects the sole writable confirmation column. Student cannot set Tutor confirmation or vice versa. Repeated same-version confirmations return success idempotently without rewriting timestamps, changing version or resetting the counterpart. No Unconfirm action.

## Agreement Version

Accepted schedule, mode, venue, platform or URL changes increment `agreement_version` exactly once and clear both `student_arranged_at` and `tutor_arranged_at` transactionally. Stale confirmation forms return a controlled conflict asking the participant to review the latest arrangement. Coordination proposal forms also reject stale displayed versions. A pending proposal does not invalidate the effective agreement: participants may confirm current details; subsequent acceptance resets both confirmations.

## Arrangement Completion

Completion is derived exclusively from both `student_arranged_at` and `tutor_arranged_at` being non-NULL for the current agreement. One timestamp alone is incomplete. The UI shows awaiting details/participant confirmation or Arrangement confirmed. No `arranged`, `confirmed` or `coordinated` booking status is created: the booking remains `accepted`. No class-completion or payment progression is implemented.

## Concurrency

Confirmation shares the approved Student → Tutor → ascending relevant slots → booking lock order with change acceptance, rechecking identity, eligibility and version after locks. A confirmation that wins first is cleared by the subsequent accepted change; one that loses is rejected as stale. Guarded confirmation updates touch only the acting participant's NULL timestamp. Independent PHP/PDO workers with a start barrier cover same-participant confirmations, Student/Tutor confirmations, arrangement Accept/Reject/Withdraw races and acceptance racing an old confirmation. Phase 3E-1 creation/response, target occupancy and overlap protections remain intact.

## Security

Prepared statements, existing auth/RBAC/account guards, scoped ownership, session-derived actors, POST/CSRF, escaped output, bounded text, HTTP/HTTPS URL validation, transaction rollback and controlled errors. Forged identity/confirmation fields have no authority. GET never proposes/confirms/responds. UTC timestamps reuse Malaysia display helpers. Real application users/bookings/certificates/settings were not modified.

## Test Results

- Coordination: 172 assertions passed.
- Phase 3E-1 changes: 181 assertions passed.
- Booking management: 100 assertions passed.
- Booking creation: 75 assertions passed.
- Availability: 66 assertions passed.
- Profiles: 207 assertions passed.
- Authentication: 763 assertions passed, all 91 protected pages.
- Total: 1,564 integration assertions passed; zero failures.
- Existing JavaScript: 10 passed; new JavaScript syntax check passed.
- PHP lint: all 11 created/modified PHP files passed. `git diff --check` passed.

All suites used their own randomly named disposable databases and cleaned them up. Because the pre-existing MariaDB process refused connections, testing used the installed MariaDB 10.4.32 binaries with a newly initialized temporary data directory on loopback port 33316 (`TMQ_DB_PORT=33316`). No service/configuration change, recovery, repair, real-data copy or interaction with the pre-existing process was performed. The isolated test instance shut down normally, logged Shutdown complete and exited; only its verified task-created temporary directory was then removed.

## Deferred

Cancellation, lesson completion/no-shows, disputes/refunds, payment/receipts, chat, notifications, feedback, support/FAQ/chatbot backends, reports/exports and Admin corrections remain deferred.

## Known Issues

No workflow or schema blocker found. The pre-existing MariaDB process was still listed and refused connections at the initial check; this environment issue was left untouched. No visual browser screenshot comparison was performed; HTTP-rendered forms/history and backend mutations were tested. The optional combined-form JavaScript only controls field visibility/required state; server validation works without it.
