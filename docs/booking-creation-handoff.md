# Booking Creation Handoff

## Completed

Phase 3D-2A only: authenticated Students select an eligible Public Tutor and real future slot, then create a requested booking that immediately reserves that slot. The existing creation page retains its layout/classes. A successful POST redirects back with a session-backed success message and the actual booking primary key. No booking list/detail or later lifecycle was connected.

## Files Created

- `includes/booking-creation.php`
- `assets/js/booking-creation.js`
- `tests/booking_creation.test.py`
- `docs/booking-creation-handoff.md`

## Files Modified

- `student/student-booking-addNew.php`: real Tutor/slot selection, summary, POST and controlled errors; removed demo booking scripts/data.
- `tutor-detail.php`: existing disabled booking CTA now links to the Student creation page with the Tutor ID; adjacent wording updated.

Existing authentication/profile/availability helpers, schema, design documents and CSS remain unchanged.

## Booking Creation Rules

Submission revalidates active Student identity, the existing `tmq_public_tutor()` eligibility rule, slot ownership, available status, future time, occupancy and mode. Only online/physical modes supported by both Tutor and slot are accepted. Selection lists exclude occupied/unavailable/past slots and incompatible modes. Slot times display through the existing UTC-to-Malaysia helper.

Learning notes are optional and limited to 4,000 characters. No separate lesson-focus selection or payment record is required by the schema; those nullable/default fields remain untouched. Forms work without JavaScript; the new script only updates the summary and mode options.

## Slot Occupation

Every insert explicitly uses `requested`. The existing generated `occupied_slot_id` UNIQUE constraint enforces reservation immediately; the manual slot status remains `available`. Locked occupancy checks and duplicate-key handling return a controlled slot-no-longer-available message. No schema change or new reservation table was introduced.

## Student Overlap Protection

A locked Student row serializes concurrent requests even across different Tutors. The query checks requested/accepted bookings using `starts_at < proposed_end AND ends_at > proposed_start`; adjacency is allowed. Different Students may book different overlapping slots.

## Price Snapshot

The current Tutor DECIMAL hourly rate is read under the transaction and stored in `hourly_rate_snapshot`. Expected amount uses integer sen multiplied by the whole-hour slot duration, then a decimal string. No floating-point money multiplication is used. Submitted prices are ignored; later rate changes do not affect bookings. Amounts beyond DECIMAL(10,2) capacity are rejected and those slots are not offered.

## Transaction Locking

Lock order is Student row, Tutor row, selected slot, then booking records. Consistent profile/account reads start after acquiring participant/slot locks, including after any lock wait. All validation and insertion occur before commit; failures roll back. This reuses the availability parent-lock protocol and approved generated uniqueness. No later lifecycle transactions were implemented.

## Security

Existing role/account guards, CSRF, PDO, public eligibility and timezone helpers are reused. Student identity is mapped from the authenticated user; submitted student/user IDs cannot change ownership. Identifiers/modes/notes are validated, queries prepared and output escaped. All creation uses POST/CSRF. Errors omit database diagnostics. UI success is session-backed, not trusted from a URL parameter.

## Test Results

- Booking creation: **75 assertions passed**, including exact snapshot pricing, forged identity/price, public eligibility, real-slot filtering, modes, inactive accounts, rollback, overlap and CSRF/role access.
- Independent concurrent PHP/PDO processes: two Students competing for one slot produce one booking and one controlled failure; concurrent overlapping requests by one Student across Tutors also produce one success.
- Availability regression: **66 assertions passed**.
- Phase 3B authentication: **763 assertions passed**.
- Phase 3C profiles: **207 assertions passed**.
- Existing JavaScript suite: **10 passed**; new JS syntax check passed.
- PHP lint: **all 3 created/modified PHP files passed**.
- Read-only real bookings/availability table checks: **OK**.

Tests imported the approved schema into uniquely named temporary databases, used only temporary fixtures, and cleaned up their own databases/accounts. No real bookings/accounts/settings/certificates were created or changed. No visual screenshot audit was performed.

## Deferred

Tutor acceptance/rejection, cancellation, changes/rescheduling, arrangements, completion, payments, chat and all other later modules remain deferred. Requested bookings stay reserved until those later authorized workflows exist. Existing booking list/detail screens remain demo-only; creation does not redirect to them.

## Known Issues

No blocking schema/business-rule mismatch found. The previously recorded MariaDB/InnoDB warning remains an environment concern; no recovery was attempted. Temporary booking operations and relevant real-table checks passed. Anonymous visitors following the booking CTA must log in; automatic return to the selected Tutor after login remains deferred.
