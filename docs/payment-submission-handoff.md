# Payment Submission Handoff

## Completed

Phase 3F-1 Student payment opening, genuine transfers, protected receipt upload/download, immutable submission versions, authorized correction, top-ups and duplicate-evidence warnings. Existing Student payment routes use shared PHP controller/content; demo receipt/progression scripts are removed from these pages. No schema changes, Tutor acknowledgement, Admin review, payment verification or booking closure implemented.

## Files Created

- `includes/payments.php`: scoped reads, money validation, correction rules and transactional writes.
- `includes/payment-page.php`, `includes/payment-content.php`, `includes/payment-receipt-form.php`: shared Student controller and presentation.
- `student/student-payment-file.php`: owned receipt downloads and saved QR access.
- `tests/payment_submission.test.py`.
- `docs/payment-submission-handoff.md` (replaces its initial blocker content).

## Files Modified

- `includes/uploads.php`: receipts storage category with the existing PDF/JPEG/PNG validator.
- `includes/booking-lifecycle-content.php`: completed Student bookings link to payment history/submission.
- `student/student-payment-upload.php`, `student/student-payment-view.php`, `student/student-payment-review.php`, `student/student-payment-rejected.php`, `student/student-payment-verified.php`, `student/student-invoices.php`: real Student records; former outcome pages display actual read-only state. Invoices becomes a lesson-charge summary, not a finalized paid invoice.
- `tests/authentication.test.py`: the new protected receipt stream returns 404 without a file ID for its authorized role; other role checks remain unchanged.

## Payment Eligibility

Authenticated active Student owns the booking; mutations require status completed and both completion timestamps. Requested/accepted/rejected/cancelled cannot submit. Closed history is readable but immutable. Routes use `?id=<booking_id>`; file routes use their explicitly scoped submission/payment IDs. GET performs no mutations, including payment creation.

## One Payment Per Booking

POST `payment_action=open` on the upload page creates or obtains the single payment under booking/payment locks and existing UNIQUE(booking_id). Repeated opens reuse it. Payments start pending, evidence_revision 1, with recipient-name and immutable QR-key snapshots. Expected amount is read only from bookings.expected_amount; no duplicate amount or recalculation using the Tutor's profile rate. Opening records no transfer.

## Genuine Transfers

POST `payment_action=transfer` requires an opened owned payment and actual receipt. One payment_transfers row plus its attempt 1 submission commit together. The schema places amount_paid on each submission, not on payment_transfers. Validate positive decimal strings without floating point, at most two decimals and maximum 99999999.99; normalize presentation to two decimals without rounding. Optional bank reference is retained on the transfer. Optional transfer_date is entered from bank evidence and is never inferred from upload time; uploaded_at is UTC server time.

## Receipt Upload

Reuse protected storage outside the web document root via TMQ_UPLOAD_ROOT. Accept PDF/JPG/JPEG/PNG up to 5,242,880 bytes, checking actual size, detected MIME, extension relationship, image structure/dimensions, PDF signature/end marker and unsafe active/script content. Random 40-hex filenames; DB stores metadata/key and SHA-256, never BLOB bytes. Storage succeeds before DB evidence insertion; rollback discards only the newly created uncommitted file. Existing evidence files are retained. PHP request/upload limits must accommodate 5 MB plus multipart overhead (installed defaults are 40M; tests explicitly configure sufficient limits).

## Receipt Versioning

Current means highest attempt_number within a transfer. Correction inserts the next SMALLINT attempt under the same transfer, protected by payment locking and UNIQUE(transfer_id, attempt_number). Old evidence, amounts, files and reviews are never overwritten/deleted. UI labels Current versus Historical. Submission versions do not count as additional money. Claimed current amounts are displayed individually; this phase calculates no final verified total.

## Reupload / Correction

The user's final rule resolves the earlier blocker. Only the targeted current submission can be corrected:

1. Deny payment verified, booking closed or otherwise ineligible booking.
2. Deny historical/noncurrent submission.
3. Deny current admin_status valid.
4. Allow current admin_status rejected or reupload_required.
5. Deny tutor_status received with Admin pending.
6. Allow tutor_status issue with Admin pending.
7. Allow tutor_status awaiting with Admin pending.
8. Deny unexpected/unknown combinations safely.

Aggregate nonfinal payment status never independently authorizes correction. A rejected/reupload-required current receipt may be corrected even if Tutor previously received it; Admin valid takes precedence over Tutor issue. The explicit original submission_id is checked after locks, so two forms targeting the same old attempt cannot both replace it.

A permitted correction may update the claimed amount/date/note/evidence in the new version. Transfer identity and its original bank reference remain unchanged; use the correction note for evidence clarification. New reviews start awaiting/pending with no reviewers/remarks. Preserve all old reviews/files. Return the payment to pending, increment evidence_revision once and clear aggregate reviewed_by_admin_id/reviewed_at/admin_remark.

## Top-Up Transfer

The UI separates "I made another payment" from "Correct / reupload the receipt for this transfer." A genuine top-up creates a NEW transfer with attempt 1; receipt correction creates a NEW submission under the SAME transfer. Valid evidence cannot be edited into a top-up. Other transfers' evidence/reviews remain unchanged.

## RM40 / RM4 / RM36 Behavior

Booking expected RM40 remains RM40. One genuine RM4 transfer stays one transfer across corrected receipt versions. Once its current evidence is Tutor received/Admin valid, Student cannot replace it with RM36. An actual RM36 top-up is a second transfer: current declared evidence represents RM4 + RM36, never RM4 + RM4 + RM36. This phase neither recognizes a valid total nor verifies settlement.

## Duplicate Evidence

Store SHA-256 for every upload using the existing non-UNIQUE indexed file_sha256 column. History derives warnings for identical evidence (including same-transfer versions) and matching nonempty references on other transfers. Matching files remain stored for review; no automatic rejection or invented flag/status/uniqueness. Only warning booleans are exposed, never another owner's IDs, notes or files. Hash/reference matches do not prove banking uniqueness or verify money; future Admin review determines duplicate claims.

## Protected Receipt Access

`student/student-payment-file.php?id=<submission_id>` streams owned historical/current receipts as attachments. `?kind=qr&id=<payment_id>` serves that owned payment's QR snapshot. Authenticated Student relationship is checked through the full booking ownership chain. No user-supplied filesystem key; protected category validation, MIME check, nosniff, sandbox CSP and private/no-store headers. Foreign files return 404, other roles are denied, POST returns 405. Stored paths are absent from Student pages. QR files retained by the existing profile workflow remain usable after Tutor QR replacement.

## Payment / Booking State

New/corrected evidence starts awaiting/pending and resets the nonfinal aggregate to pending with a revision increment and cleared aggregate review metadata. Receipt upload does NOT mean verified payment. Booking remains completed; price, schedule, arrangement, completion timestamps and agreement_version are untouched. Read-only Student history excludes private Tutor/Admin review notes. Tutor acknowledgement deferred; Admin validation/final verification deferred; completed -> closed deferred.

## Concurrency

Approved order: booking -> payment -> relevant transfer/current submission. Never acquire participant/slot locks afterward. Recheck actual active Student identity, ownership, booking eligibility, payment finality and current attempt after locks. Booking locking serializes first creation; existing UNIQUE(booking_id) remains final protection. Distinct genuine transfers may both commit; same-current correction has one winner and a controlled stale loser. Top-up/correction races preserve separate identities and revisions. Integer revision/attempt exhaustion returns a controlled conflict. DB lock/duplicate conflicts roll back without exposing SQL diagnostics.

## Security

Existing auth/RBAC/account guards, session actors, prepared statements, POST/CSRF, scoped IDs and escaped output. Submitted user/Student/owner/review/status/expected-amount fields have no authority. Files are validated and stored inside the mutation transaction; exceptions roll back records and remove only the newly stored file. Tests use disposable databases, temporary upload directories and a temporary restricted DB account for post-storage INSERT failure; no real application data or real DB accounts changed.

## Test Results

- Payment submissions: 244 assertions passed, including all correction-state combinations, current-version guards, RM4/RM36, PDF/JPEG/PNG and exact 5 MB, ownership, SHA-256 warnings, independent HTTP-process races and real file/DB failure rollback.
- Phase 3E-3 lifecycle: 243 passed.
- Phase 3E-2 coordination: 172 passed.
- Phase 3E-1 changes: 181 passed.
- Phase 3D-2B management: 100 passed.
- Phase 3D-2A creation: 75 passed.
- Phase 3D-1 availability: 66 passed.
- Phase 3C profiles: 207 passed.
- Phase 3B authentication: 770 passed, including 92 protected pages.
- Total: 2,058 integration assertions, zero final failures. Existing JavaScript: 10 passed. All 13 created/modified PHP files pass lint; `git diff --check` passes. Approved SQL/design/implementation-note files remain unchanged.

Successful runs used disposable databases on MariaDB 10.4.32 at 127.0.0.1:33319 (`TMQ_DB_PORT=33319`). Every suite removed its own databases/uploads; the fault-injection DB user was removed. Before shutdown, only bootstrap system/test databases remained and the temporary payment DB-user count was zero. Initial connection/stalled-schema attempts were environment failures before application assertions; all required suites were rerun successfully on the fresh instance. No mutation touched the real application database.

The successful instance shut down normally, logged Shutdown complete and exited. Its exact task-created directory and marker were removed only after verifying process exit, log completion and resolved paths. The earlier task-created stalled directory was likewise removed after its process exit. No temporary MariaDB instance or receipt fixture from this task remains.

## Deferred

Tutor received/issue acknowledgement, Admin evidence decisions, final verified-total calculation and payment verification, booking closure, refunds/reversals/fees, banking integration, disputes, chat, notifications, feedback, support/FAQ/chatbot, reports and exports. Tutor/Admin payment demo pages remain unchanged and have no backend review mutations.

## Known Issues

No remaining business-rule/schema blocker. HTTP-rendered forms, states and protected files are tested; no visual screenshot comparison. The pre-existing XAMPP process issue remains untouched. Initial isolated runtime attempts failed/stalled during schema setup; the task-owned stalled instance exited after a normal shutdown request and was removed after process-exit verification. The successful test instance uses a fresh temporary data directory, loopback port 33319 and redirected stdout/stderr. No force-kill, recovery setting, database repair or interaction with the pre-existing MariaDB process occurred.
