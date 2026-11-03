# Availability Handoff

## Completed

Phase 3D-1 only: database-backed Tutor availability list, create, view and edit. Existing layouts/forms remain; sample slots and browser-only persistence are bypassed on these PHP pages. Private/incomplete Tutors can manage availability. Phase 3C public eligibility is unchanged.

## Files Created

- `includes/availability.php`: interval validation, timezone conversion, overlap check and transactional writes.
- `includes/availability-page.php`: authenticated page controller and shared presentation helpers.
- `tests/availability.test.py`: disposable-database integration tests.
- `docs/availability-handoff.md`.

## Files Modified

- `tutor/tutor-availability.php`
- `tutor/tutor-availability-addNew.php`
- `tutor/tutor-availability-edit.php`
- `tutor/tutor-availability-view.php`
- `assets/js/availability.js`: server-backed pages retain the preview only; no localStorage reads/writes or intercepted submissions. Legacy demo behavior is retained elsewhere.

Approved schema/design, authentication, profiles, public pages and CSS were not changed.

## Availability Rules

Future start, valid calendar date, whole-hour start and 1–24 whole-hour duration are required, matching the approved SQL constraints. End is derived server-side; an inconsistent submitted preview is rejected. Overnight and 24-hour intervals are supported. Only `online`, `physical`, `both` modes and `available`, `unavailable` statuses are accepted.

The existing interface has no delete control. Editing status to Unavailable retains the record and interval; it still prevents overlap and can be restored through Edit while future and unreferenced. No physical deletion or new archival system was added. Slots referenced by a booking/change request are read-only here to preserve history; these are defensive reference checks, not booking functionality.

Lists show the current Tutor's upcoming slots first, followed by past slots, and provide an empty state. View/edit use `?slot=<slot_id>`; absent IDs show a selection prompt, invalid/foreign IDs return controlled 404 responses.

## Timezone Handling

PHP `DateTimeImmutable` parses the local date/start with `Asia/Kuala_Lumpur`, derives the end, and converts both to UTC before storage. Display explicitly converts UTC back to Malaysia time, including next-day wording. The existing PDO connection uses UTC. Forms work without JavaScript; client preview is optional.

## Overlap Protection

One helper uses `existing.starts_at < proposed.end AND existing.ends_at > proposed.start`, excluding the edited slot. Both available and unavailable intervals participate; adjacency is permitted.

Every save begins a transaction and locks the authenticated Tutor's parent row before checking/locking slots and writing. This serializes even first-slot creation. Later booking operations must follow the approved participant-lock protocol; interval uniqueness is not a database range constraint. No student/booking locks or operations were implemented in this phase.

## Security

Existing role/account guards, PDO and CSRF helpers are reused. Ownership comes from the authenticated user-to-Tutor relationship, never submitted tutor IDs. All database queries use prepared statements and output is escaped. State changes require POST/CSRF; invalid values return controlled errors without database diagnostics. Failed saves roll back.

## Test Results

- Focused availability suite: **66 assertions passed**, including UTC persistence/Malaysia display, cross-midnight/24-hour intervals, ownership, invalid dates/durations/status/mode, CSRF, retained Unavailable records, adjacency and edit conflicts. Independent concurrent PHP/PDO processes with different overlapping start times produce one success and one conflict.
- Phase 3B authentication regression: **763 assertions passed**, all 91 protected pages.
- Phase 3C regression: **207 assertions passed**.
- Existing JavaScript tests: **10 passed**; modified JS syntax check passed.
- PHP lint: **all 6 created/modified PHP files passed**.
- Read-only real `availability_slots` table check: **OK**.
- Integration tests used unique temporary databases and removed only databases/uploads/accounts created by their own runs. Real accounts/Admin/settings were untouched.

## Deferred

Student booking, reservation, acceptance, rescheduling, cancellation, payment, chat and every other later module remain unimplemented. No public Tutor booking controls were connected.

## Known Issues

No availability blocker was found. The previously documented MariaDB/InnoDB warning remains an environment concern; no recovery or repair was attempted. Availability runtime operations and table checks succeeded. No visual screenshot comparison was performed.
