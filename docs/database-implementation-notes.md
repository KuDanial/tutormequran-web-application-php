# Database Implementation Notes — Phase 3A

## Environment and scope

Checked on 2026-11-03. The installed XAMPP server binary at `C:/xampp/mysql/bin/mysqld.exe` reports **10.4.32-MariaDB, Win64 AMD64**. The client reports MariaDB 10.4.32. `C:/xampp/php/php.exe` reports **PHP 8.2.12 CLI**, with **PDO MySQL enabled**.

During the initial foundation pass, MariaDB was not running and validation was static only. In the authorized runtime follow-up on 2026-11-03, the existing XAMPP process was started temporarily without configuration changes. The live server returned **10.4.32-MariaDB**. Schema installation and constraint/PDO tests passed in isolated temporary databases; see the runtime validation record below. Existing user databases were not modified.

The approved design documents are unchanged. This phase adds foundation files only; existing pages do not load the connection helper and retain their current behavior.

## Files and configuration

| File | Responsibility |
| --- | --- |
| `database/tutormequran.sql` | Fresh database `tutormequran`, 23 tables, constraints, indexes and base reference rows |
| `database/seed-profile-field-settings.sql` | Deferred, one-time seed of the 22 approved settings after a real Admin exists |
| `config/database.php` | Central connection settings; no real password committed |
| `includes/db.php` | Lazy `tmq_db(): PDO` connection, exceptions, native prepared statements, associative results, utf8mb4 |
| `scripts/validate_database_schema.py` | Repeatable static comparison with the approved Markdown/JSON specification |
| `docs/database-implementation-notes.md` | This implementation and validation record |

Environment overrides are `TMQ_DB_HOST`, `TMQ_DB_PORT`, `TMQ_DB_NAME`, `TMQ_DB_USER`, and `TMQ_DB_PASSWORD`. Local XAMPP defaults are `127.0.0.1`, `3306`, `tutormequran`, `root`, and an empty password. Configure actual credentials in the server environment; a deployed application should use its own restricted database account. No .env package is required. Connection failures expose a generic exception; only the driver error code is logged. UTC and strict SQL mode are set on each connection. No application queries or session/authentication handling were added.

The schema is a **fresh-install file**, not a migration or repeatable reset. `CREATE DATABASE` deliberately has no `IF NOT EXISTS`: an existing database must cause an abort. Use an importer that stops on the first error; never use `--force` or continue after a database-creation error. There are no DROP/TRUNCATE statements or disabled integrity checks. DDL is not an all-or-nothing transaction; any failed installation needs inspection before retrying. Imports were performed only in temporary validation databases, never in `tutormequran`.

## Compatibility and SQL integrity

- All 23 tables use InnoDB, `utf8mb4_unicode_ci` and DYNAMIC rows. IDs/FK types match the approved unsigned types; money is `DECIMAL(10,2)`. Status columns use a binary collation with CHECK allowlists. Username/email uniqueness follows the database's case-insensitive collation; later PHP must normalize them consistently. IC is not UNIQUE.
- Explicit named foreign keys use RESTRICT for update/delete. Composite parent UNIQUE keys support slot/tutor ownership and displayed-certificate/tutor ownership. Create tutors with a nullable certificate pointer, create certificates, then add the displayed-certificate FK through ALTER TABLE. FK checks remain enabled.
- MariaDB PERSISTENT generated columns implement UNIQUE occupied slots for requested/accepted/completed/closed bookings and UNIQUE pending change requests per booking. Other rows generate NULL, permitting historical rejected/cancelled bookings and resolved requests. These generated columns are not themselves FK columns; ordinary IDs have separate supporting FK indexes.
- One payment and one feedback per booking, and UNIQUE(transfer_id, attempt_number), are enforced. SQL also checks positive prices, whole-hour 1–24-hour intervals, snapshot fee calculation, rating 1–5, supplied file metadata limits, and other row-local invariants. Cross-row workflow rules below remain required.

Compatibility was reviewed against MariaDB documentation for [generated columns](https://mariadb.com/docs/server/reference/sql-statements/data-definition/create/generated-columns), [constraints](https://mariadb.com/docs/server/reference/sql-statements/data-definition/constraint), and [foreign keys](https://mariadb.com/docs/server/ha-and-performance/optimization-and-tuning/optimization-and-indexes/foreign-keys). No MySQL 8-only syntax, triggers, routines, or new tables were introduced. Runtime execution on MariaDB 10.4.32 subsequently passed without any schema compatibility fix.

## Reference data

The base SQL supplies **3 languages** (Bahasa Melayu, English, Arabic) and **5 lesson focuses** (Tajweed, Quran Reading, Iqra, Hafazan, Talaqqi). It contains no sample users, tutors, certificates, bookings, payments or feedback. These inserts were executed and verified only in temporary validation databases.

The approved settings updater FK is NOT NULL and is preserved. Therefore the base schema leaves settings empty instead of creating a fake Admin or weakening the FK. After a real Admin is manually provisioned in a later phase, select the intended database and set the session variable `@profile_settings_admin_id` to that existing `admins.admin_id`; then run `database/seed-profile-field-settings.sql` in the same session. Its single INSERT supplies the exact **7 Student + 15 Tutor** keys with `is_editable=1`. A missing/invalid Admin ID fails NOT NULL/FK validation; existing settings fail uniqueness instead of being overwritten. Missing settings must be treated as disabled until this initialization is completed, as specified in the approved design.

## Rules requiring later PHP transactions/validation

| Area | Required enforcement beyond ordinary SQL constraints |
| --- | --- |
| Accounts and qualification | Create exactly one role-matching subtype atomically. Authorize Admin reviews. Display only an owned approved certificate; preserve approved history, validate same-tutor noncyclic replacement chains and certificate year against the current year. Enforce publication completeness and active account without requiring future availability. Deactivate historical users instead of deleting them. |
| Availability and reservation | Validate future instants in Malaysia time and persist UTC. Reject overlapping tutor intervals, including blocked intervals, and overlapping requested/accepted student bookings across all tutors. Lock student first, tutor next, slots in ascending ID, then booking/change rows; multiple participants also lock in ascending ID. Slot-only edits lock tutor/slots and never then acquire student locks. Check overlaps under these locks, not with an unlocked check-then-insert. Generated uniqueness enforces one slot occupant but cannot enforce time-range overlap. Referenced slot identity/times remain immutable. |
| Agreement and cancellation | Validate slot/tutor capability and trusted time snapshots. Keep original hourly rate; recompute expected amount only for changed duration. Other participant must consent to a current-version change before applying it; recheck target availability without temporary holds. Save before/after history, increment agreement_version and reset both arrangement timestamps for material changes. Validate venue/meeting details before both confirmations. Store authorized cancellation actor/reason/time/Malaysia timing and withdraw pending requests atomically. No expiry, penalty or automatic refund. |
| Completion and feedback | Validate participant ownership and lesson timing for separate completion timestamps. Require both before completed/feedback; payment verification is not a feedback prerequisite. UNIQUE(booking_id) prevents duplicate feedback, but cannot itself establish eligibility. Normal closed/verified records are read-only. |
| Payments | Lock booking then payment; do not later acquire participant/slot locks. Allocate receipt attempts, increment evidence_revision and reset relevant reviews atomically. Tutor acknowledgement is distinct from Admin evidence validity and final verification. Recompute totals from only the highest attempt per transfer with tutor received AND admin valid. Check revision, both completions and all current evidence resolved before Admin verifies exact expected total and closes booking atomically. Preserve earlier versions; handle excess/disputes manually. |
| Access, files and settings | Validate chat/support/notification ownership, role-specific settings and trusted upload content/paths. Admin support access does not authorize private chat. SQL file metadata checks do not inspect file contents. Store protected files outside the public root where practical; enforce 5 MB certificate/receipt/profile/QR/support limits and 10 MB safe chat limits in PHP. Create related notifications in domain transactions. Use prepared statements, CSRF protection and authorization in later features. |

The approved partial-payment model is preserved: an RM4 genuine transfer toward RM40 leaves RM36 outstanding after review; a later RM36 payment is a second transfer. Correcting the RM4 receipt adds another attempt under the first transfer, not another transfer. Only each transfer's latest accepted evidence contributes, so replacing a receipt cannot count the RM4 twice. This requires the transactional query rules above; the schema intentionally does not implement a payment workflow or ledger.

Comparison reports can count bookings by tutor using bookings.created_at; composition reports count payments by status using payments.created_at. Receipt versions do not inflate either report. No report-storage tables were added.

## Validation and deferred work

- `python scripts/validate_database_schema.py`: **passed** static checks for 23 tables, 226 columns, 42 FKs, exact approved column/type/nullability/PK/UNIQUE parity, FK types/targets/indexes, circular creation order, 23 stored-status mappings, generated uniqueness, money types, InnoDB/utf8mb4, canonical seeds and no destructive statements/fake records. Lexical checks cover balanced syntax; this script is not a SQL compiler.
- PHP lint: **passed** for both new PHP files. Loading the helper performs no connection; configuration environment overrides worked, and an unavailable server produced the intended generic exception without underlying driver details.
- Runtime SQL parsing, installation, SHOW CREATE TABLE/information_schema inspection, constraint rejection tests and the live PDO helper test subsequently **passed**, as detailed below. PHP transaction-level interval overlap/concurrency logic was not implemented or tested.
- Existing application files and approved design documents were preserved. Production database import, real Admin provisioning/settings initialization and all authentication/CRUD/workflow implementations remain deferred. The runtime follow-up modifies only this notes file.

## Runtime validation follow-up — 2026-11-03

**Result: PASS on live MariaDB 10.4.32.** No schema or connection/configuration compatibility changes were required.

- **Isolation and installation:** Read the actual `database/tutormequran.sql`; replace only its CREATE DATABASE and USE names in memory. Every attempt used a fresh random-suffixed `tutormequran_schema_validation_...` name after an information_schema existence check. All schema statements executed successfully. The final complete run used `tutormequran_schema_validation_20261102_204126_4059982b` (the name uses UTC).
- **Installed metadata:** SHOW CREATE TABLE and information_schema confirmed exactly **23 application tables**, all InnoDB with `utf8mb4_unicode_ci`, **42 foreign keys** matching the approved summary, both generated columns, and all **55 named CHECK constraints**. Money columns are DECIMAL(10,2). The circular tutor/certificate relationship and both composite FKs installed successfully; attempts to select another tutor's certificate or pair a booking with another tutor's slot were rejected.
- **Conditional uniqueness:** Each occupying booking status (requested, accepted, completed, closed) prevented a second booking for its slot. Changing an existing booking to rejected or cancelled allowed another occupant. Reactivating a historical booking into an occupied slot failed. A second pending change request failed; rejected and withdrawn requests released the key for a new pending request. Reactivating a resolved request while another was pending also failed.
- **Other constraints:** Duplicate username, email, feedback.booking_id, payments.booking_id and (transfer_id, attempt_number) failed with duplicate-key errors. Four temporary accounts successfully shared one IC value. An orphan FK insert failed. Each of the **55 CHECK constraints** was individually exercised with invalid data and rejected by MariaDB error 4025. In total, **70 expected SQL failures** verified the targeted CHECK/UNIQUE/FK constraints.
- **Payment structure:** Two genuine transfers of RM4 and RM36, with an additional correction attempt for the RM4 receipt, produced RM40 when selecting only each transfer's latest tutor-received/admin-valid attempt. This was a read query over disposable fixtures, not application payment workflow implementation.
- **Reference seeds:** The base install contained exactly **3 languages and 5 lesson focuses**, with no users or profile settings. The separate settings seed also executed successfully with a temporary test Admin and produced **22 keys**; no real Admin or production settings were created.
- **PDO:** Process-local environment overrides selected only the temporary database; `includes/db.php` connected successfully. DATABASE(), SELECT 1, utf8mb4 client/connection/results, UTC, native prepares and exception mode were verified. Querying an intentionally nonexistent table raised PDOException as expected. Configuration files and page wiring were unchanged.
- **Cleanup:** Domain fixtures were transactional and rolled back. All three task-created temporary databases, including the earlier test-harness runs, were dropped by an ownership-guarded cleanup; no pre-existing database was dropped. SHOW DATABASES before/after matched. The original schema was unchanged. No PHP transaction-level overlap logic was tested.
- **Server lifecycle:** This task started the existing standalone XAMPP MariaDB process (PID 4424). With no other clients connected, mysqladmin accepted a graceful shutdown request. Port 3306 subsequently closed and connection attempts were refused, but PID 4424 remained present at the final process check. It was not force-terminated; full process exit is therefore not confirmed. This does not affect the completed schema tests or confirmed temporary-database removal. No MariaDB configuration was changed.
- **Source preservation:** Content hashes confirmed that all 260 other project files, including the schema, both connection files, seed, approved design documents and application pages, remained unchanged. Only this implementation-notes document was modified in the repository.

The temporary validation harness was outside the repository. Earlier harness-only adjustments normalized metadata sorting and distinguished numeric from string CHECK probes; MariaDB accepted the schema on every run. These required no production-file or approved-design changes.
