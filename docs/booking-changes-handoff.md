# Booking Changes Handoff

## Completed

Phase 3E-1: Student/Tutor booking change requests, counterpart acceptance/rejection, requester withdrawal, retained history, schedule and mode changes. Existing page shells/styles are retained. Schema and approved database documents are unchanged. Pre-existing Phase 3D-2B working-tree changes were preserved.

## Files Created

- `includes/booking-changes.php`: scoped reads, target validation and transactional writes.
- `includes/booking-change-page.php`: shared edit-page controller.
- `includes/booking-change-content.php`: request form, comparison/history and response controls.
- `tests/booking_changes.test.py`.
- `docs/booking-changes-handoff.md`.

## Files Modified

- `student/student-booking-edit.php`, `tutor/tutor-booking-edit.php`: replace demo forms with real booking changes.
- `includes/booking-page.php`, `includes/booking-detail-content.php`: participant detail pages show history and change controls through their existing shared includes; Admin remains unchanged/read-only.
- `includes/booking-creation.php`: extract the existing Student overlap query into a shared helper with an optional excluded booking ID.
- `tests/booking_management.test.py`: allow the newly implemented change link while retaining the assertions that deferred coordination/cancellation links are absent.

## Request Creation

POST to the role's booking-edit page with `?id=<booking_id>`. Requires an owned accepted booking before its start, a nonempty reason (maximum 4,000 characters), a meaningful schedule/mode change, active participants, and a real future available slot of the same Tutor. Proposed intervals come entirely from availability records. Existing money, timezone, ownership and mode helpers are reused. Overlap is checked at creation and again at acceptance. Prices, participants, requester and agreement version are derived server-side.

Schedule changes validate Tutor and target-slot mode support. Approved design rule 17 explicitly permits mode-only counterpart consent based on Tutor capability without changing the historical slot's advertised mode. Both physical-to-online and online-to-physical work. No custom interval, new booking or new availability record is created.

## Student/Tutor Access

Both edit and detail pages use authenticated user-to-participant relationships. Detail pages display the effective agreement, before/proposed schedule, mode and expected amount, requester, reason, status and response time/note. Only the other participant gets Accept/Reject controls; only the requester gets Withdraw. Request IDs are also bound to the owned booking. Admin cannot use participant change helpers or endpoints.

## Pending Request Behavior

Pending requests leave the original booking entirely unchanged and hold no target slot. Another Student can legitimately book the target. One pending request is enforced by a locked application check plus the existing generated `pending_booking_id` UNIQUE constraint. Original/proposed slot, mode, expected amount and existing arrangement details are retained in the approved columns.

## Accept Flow

Under the transaction, recheck participant identity/accounts, accepted/future booking, pending request, base agreement version and before-values, target ownership/status/time/occupancy, mode support and Student overlap. Apply the stored proposal only after its amount is recalculated and matches. Preserve booking ID, accepted status, original Tutor decision timestamp and hourly-rate snapshot. Record the counterpart and response timestamp/note.

If the target was taken, acceptance returns a controlled conflict; both bookings remain unchanged and the proposal remains pending. The requester may withdraw it, or the counterpart may reject it. No automatic substitute, expiry or replacement booking exists.

## Reject Flow

The other participant may POST rejection with an optional response note (maximum 4,000 characters). Store rejected status, counterpart ID, response time/note; leave the booking, slots, mode and price unchanged. A still-pending proposal can be rejected even when its schedule has passed or the requester is inactive; the acting account must remain active.

## Withdrawal Flow

Only the original requester may POST pending-to-withdrawn. Store the withdrawal time in `responded_at`; leave `responded_by_user_id` NULL because that field represents the counterpart. The requester already identifies the withdrawal actor. Booking and slots remain unchanged; history remains. Withdrawal can clean up a pending request after its proposed/current start has passed.

## Slot Occupancy

The existing generated `occupied_slot_id` transfers occupancy when the accepted booking's `slot_id` changes. The old slot becomes reusable; the target becomes occupied. No availability row is deleted, rewritten or recreated. Existing availability helpers continue to protect referenced historical intervals.

## Price Recalculation

Reuse integer-sen `tmq_booking_amount()` with the original `hourly_rate_snapshot` and target whole-hour duration. Never use the Tutor's current profile price, including in slot-option previews. Mode-only changes retain the exact expected amount. Before/proposed amounts remain in each change record.

## Agreement Version / Arrangement Reset

Every accepted schedule/mode change increments existing `agreement_version` and clears both `student_arranged_at` and `tutor_arranged_at` in the same transaction. Inapplicable venue/meeting fields are cleared in the effective agreement while originals remain in request history. Applicable existing details are retained. No arrangement confirmation or coordination UI was implemented.

## Concurrency

Read locators before BEGIN, then lock Student, Tutor, relevant slots in ascending ID, booking and request. Revalidate locator values after waiting. Student locking serializes active-overlap checks across Tutors. Conditional uniqueness remains the final pending/occupancy protection. Guard pending transitions and roll back all failures. Duplicate keys, lock timeouts and deadlocks return controlled conflicts. Independent PHP/PDO workers with a shared start barrier test create/create, accept/accept, accept/reject, reject/reject, withdraw/accept and withdraw/reject.

## Security

Prepared statements, escaped output, session-derived actor, role/ownership checks, POST/CSRF, guarded transitions and rollback. Submitted owner/rate/amount/version fields have no authority. GET cannot mutate. Participant detail response forms POST to the role's edit endpoint; the existing detail controllers retain their prior mutation rules. SQL diagnostics are not exposed. Real application accounts, settings, certificates and bookings were not modified.

## Test Results

- Booking changes: 181 assertions passed, including combined schedule/mode acceptance and inactive-counterpart checks.
- Booking management: 100 assertions passed.
- Booking creation: 75 assertions passed, including same-slot/Student overlap races.
- Availability: 66 assertions passed.
- Profiles: 207 assertions passed.
- Authentication: 763 assertions passed, all 91 protected pages.
- JavaScript: 10 tests passed.
- PHP lint: all 8 created/modified PHP files passed. `git diff --check` passed.

Final runs: 1,392 Python integration assertions and 10 JavaScript tests passed; zero failures. Each integration suite imported the unchanged schema into its own randomly named disposable database and cleaned it up. Initial focused-suite fixture errors (an obsolete URL and duplicate past timestamp) were corrected. No recovery or database repair was attempted. MariaDB was initially stopped and started normally using the existing XAMPP configuration for testing. After tests, `mysqladmin shutdown` succeeded and connections were refused, but process 22564 remained listed after a 30-second process wait. Full process exit could not be confirmed; no forced termination was attempted.

## Deferred

Cancellation, coordination, arrangement confirmation UI, completion/no-shows, payments, chat, notifications, feedback, support, reports and Admin corrections remain deferred.

## Known Issues

No schema/business-rule blocker found. No visual browser screenshot comparison was performed; rendered HTTP forms/history, access controls and mutations were exercised. Historical MariaDB/InnoDB warnings remain outside this phase; disposable runtime table checks passed. Environment follow-up: normal MariaDB shutdown stopped connections but its process remained present at the final check, so restoration to a fully exited process is unconfirmed. Do not apply database recovery or delete InnoDB files for this phase.
