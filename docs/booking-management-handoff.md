# Booking Management Handoff

## Completed

Phase 3D-2B: real Student/Tutor booking lists and details, Tutor requested-to-accepted/rejected decisions, rejected history and Admin read-only booking list/detail. Existing page shells, navigation, cards, tables and CSS are retained. Status filters use the six approved booking statuses. Demo booking scripts/data and later-action links were removed from these pages; shared dropdown behavior remains.

## Files Created

- `includes/bookings.php`: scoped reads, status/time presentation and transactional decisions.
- `includes/booking-page.php`: shared page controller.
- `includes/booking-list-content.php`, `includes/booking-detail-content.php`: shared presentation.
- `tests/booking_management.test.py`.
- `docs/booking-management-handoff.md`.

## Files Modified

- `student/student-bookings.php`, `student/student-booking-view.php`.
- `tutor/tutor-bookings.php`, `tutor/tutor-booking-view.php`, `tutor/tutor-booking-rejected.php`.
- `admin/manage-bookings.php`, `admin/manage-bookings-view.php`.

No schema/design, existing auth/profile/availability/creation helpers, CSS or JavaScript source changes.

## Student Booking Access

Lists and `?id=<booking_id>` details are restricted through the authenticated user's Student relationship. Submitted owner IDs have no authority. Displays use actual Tutor name, agreed interval/mode, requested/accepted/rejected or other stored status, hourly-rate snapshot, expected amount, creation/decision timestamps and rejection reason. UTC timestamps reuse the Malaysia display helper. No private counterpart contact/address/IC fields are selected.

## Tutor Booking Access

The same ownership-scoped read helper restricts Tutor pages. Requested bookings appear first; filters include rejected history. Tutor details show Student name and learning notes. The existing rejected page now reads an owned record and displays its actual status rather than an assumed rejection. Missing-ID GETs show a selection prompt; explicit invalid/foreign/nonexistent IDs return the same controlled 404 message.

## Accept Flow

POST `decision=accept` on `tutor/tutor-booking-view.php?id=...`, with existing CSRF protection. Requires an active owning Tutor, current requested state, matching booking/slot interval and a valid future available slot supporting the agreed mode. Sets only status, decision timestamp and rejection-reason metadata; stored rate/amount, interval and later workflow fields remain unchanged. No requirement to republish a previously eligible Tutor profile is introduced.

## Reject Flow

POST `decision=reject` to the same endpoint. Requires the owning Tutor, current requested state and a nonempty reason of at most 600 characters, matching the existing UI. Records `rejection_reason` and `decision_at`; retains the booking permanently. Success redirects to the canonical detail page. Both decisions are unavailable on non-requested records and never run through GET.

## Slot Occupancy

Accepted remains on the same slot with generated occupancy intact. Rejected automatically gets NULL occupancy through the approved generated column. The slot row is not altered or recreated. Existing booking creation can reuse a rejected slot if it is still future/available and all normal eligibility/overlap rules pass.

## Admin Read-Only Access

Admin can read all bookings and counterparts. Payment placeholders were replaced with the actual expected lesson amount; no payment queries/actions were added. Admin list/detail POSTs return 405. Edit/cancel/complete links are absent on these connected pages; the separate deferred pages are unchanged.

## Security and Ownership

Existing auth/account guards, CSRF, PDO and timezone helpers are reused. Queries are prepared and displayed values escaped, including notes and reasons. Only Tutor detail supports decision POSTs; Student detail, lists, Admin pages and the rejection-history page are read-only. Errors expose no SQL diagnostics. No real database users/settings/certificates/bookings were changed.

## Concurrency

An ownership-scoped locator read precedes BEGIN. The transaction locks Student, Tutor, slot, then booking, rechecks identity/active account/ownership and locator values, and verifies requested status. The guarded UPDATE also requires requested. Duplicate or competing decisions produce one success and a controlled HTTP 409 stale-state response. Failures roll back. Accepted/rejected decisions do not acquire later participant locks or create payment/change records.

## Test Results

- Booking management: **100 assertions passed** (scoped lists/details, pricing/time display, privacy/escaping, both decisions, CSRF/roles, stale/past acceptance, rejection history, slot reuse and Admin read-only behavior).
- Independent concurrent PHP/PDO processes verified Accept/Accept and Accept/Reject: exactly one transition succeeds, with coherent metadata/occupancy and a controlled losing response.
- Booking creation regression: **75 assertions passed**, including same-slot and Student overlap races.
- Availability regression: **66 assertions passed**.
- Phase 3C profile regression: **207 assertions passed**.
- Phase 3B authentication regression: **763 assertions passed**, including all 91 protected pages.
- Existing JavaScript tests: **10 passed**. PHP lint: **all 11 new/modified PHP files passed**.
- Read-only real `bookings`/`availability_slots` checks: **OK**.

All runtime fixtures used uniquely named disposable databases; each test removed only its own database/accounts/uploads. Initial setup was blocked by stopped MariaDB and sandbox write restrictions. The existing MariaDB 10.4.32 installation was started outside the sandbox with its unchanged configuration, tested, then shut down normally to restore its initial stopped state. No manual recovery or repair occurred. An initial test assertion was corrected to recognize the pages' existing relative links; final runs have zero failures.

## Deferred

Rescheduling/change requests, mode changes, arrangement confirmation, coordination, cancellation, completion, payment, chat, notifications and feedback remain deferred. No Phase 3E work was started. Booking creation retains its existing success redirect; the real lists/details are accessible from booking navigation.

## Known Issues

No blocking schema/business-rule issue found. Historical MariaDB/InnoDB warnings remain an environment concern, but this startup and the runtime/table checks succeeded. Start MariaDB normally in XAMPP before using the application again. No browser screenshot comparison was performed.
