# Reports Handoff

## Completed
Phase 3I-1 implements one Admin report dashboard with Total Bookings by Tutor, payment-status composition, optional record-month filtering, and CSV export. Existing report cards, tables, bar chart and donut styles are reused. Unrelated sample KPIs, revenue columns and trend/distribution placeholders were removed from the live PHP report; no additional analytics were implemented.

## Files Created
- `includes/reports.php`
- `tests/reports.test.py`
- `docs/reports-handoff.md`

## Files Modified
- `report/report-dashboard.php`

Pre-existing chatbot work is untouched. The static HTML prototype is unchanged; the existing PHP route is the single live report dashboard.

## Report Model
Current results are derived directly from transactional records using fixed prepared SELECT statements. Aggregate data only: Tutor identifier/name and booking count; payment status, count and percentage. No report storage tables, materialized reports, schema changes or changes to `database/tutormequran.sql` or approved design documents.

## Comparison Report
Counts actual bookings grouped by Tutor, including all statuses (requested, accepted, rejected, completed, cancelled, closed). Names use the Tutor display name with user first/last name fallback. Historical private/inactive Tutors are included. Ordering is booking count descending, then Tutor ID ascending. Tutors without bookings in the selected period have no group. The bar chart and table share these counts.

## Composition Report
Groups actual `payments.status` values, displaying each represented status and count in status order. No receipt/submission joins or inferred statuses. Percentages use the same filtered payment total, rounded to two decimals; rounding can prevent displayed percentages summing to exactly 100%. The donut uses the unrounded count proportions. Empty results show zero payments and an empty-state message without division by zero. No money calculations.

## Month Filter
The approved report mapping explicitly permits optional filtering by `bookings.created_at` and `payments.created_at`. GET `month=YYYY-MM` uses strict validation (four-digit years 1000–9999 and months 01–12); blank/omitted means all time. Malaysia month boundaries are converted to UTC and applied as inclusive start/exclusive next-month start. Each report uses its own record creation date, so booking and payment counts may differ. Invalid scalar/array input receives controlled HTTP 422. The export link preserves the applied month.

## Export
GET `report/report-dashboard.php?export=csv` optionally accepts the same month. CSV replaces the unimplemented PDF/Excel/print preview controls; no export library or service. Both outputs call the same aggregate loader. Downloads contain a six-column header and rows for the two report definitions, with period metadata. Empty periods produce a header-only CSV. Filenames are deterministic (`tutormequran-reports-all-time.csv` or `tutormequran-reports-YYYY-MM.csv`). Export recalculates current records, so intervening transactional updates can change results between requests.

## Admin Authorization
The existing report-directory guard requires an authenticated, active Admin before any report query or output. Guests/inactive accounts redirect to login; Student/Tutor accounts receive HTTP 403, including for export. Shared authentication/protected-page code was not modified.

## Security
Fixed SQL and bound date parameters; fixed ordering; escaped HTML; controlled HTTP 503 for database errors without SQL diagnostics or partial CSV. GET-only reporting; unsupported methods receive HTTP 405. CSV uses `fputcsv` with explicit quoting, no proprietary backslash escaping and CRLF records. Exported text starting with `=`, `+`, `-`, `@`, whitespace followed by those characters, or a leading tab/newline is prefixed with an apostrophe to prevent spreadsheet formula injection. Download headers use validated filenames, `nosniff` and `no-store`. No emails, IC, receipt/QR paths, payment hashes, remarks, certificate paths or chat content are selected/exported.

## Side Effects
Viewing/exporting performs only SELECTs. No domain mutations or notifications. Existing session/CSRF handling remains in the shared Admin shell. Focused tests compare every domain table and table definition before/after report views, exports and query-error paths.

## Test Results
- `python -X utf8 tests/reports.test.py`: PASS, 90 focused assertions covering authorization, grouping/counts/order, every booking/payment status, percentages, empty periods, Malaysia/UTC boundaries, independent record dates, validation, CSV equivalence/escaping/formula protection, private-data exclusion, XSS, controlled query failures and no domain/schema mutations.
- PHP lint: PASS for `includes/reports.php` and `report/report-dashboard.php`.
- `git diff --check`: PASS.

Tests created a fresh, isolated MariaDB process, data directory and database, then shut it down normally and removed the task-created runtime. They never connected to the real database or PID 21932's retained runtime. Only HTTP/SQL helpers were imported from the authentication test file; its suite was not executed.

## Deferred
- Full historical regression and final integration deferred to Phase 3I-2.
- PDF/Excel/print-specific implementation and unrelated prototype analytics are outside this phase.
- Authentication regression is N/A because shared authentication/protected-page code is unchanged.
- Report JS tests are N/A because no JavaScript changed; existing CSS charts are rendered from aggregates on the server.

## Known Issues
None identified in focused verification. Full integration remains untested by design until Phase 3I-2.
