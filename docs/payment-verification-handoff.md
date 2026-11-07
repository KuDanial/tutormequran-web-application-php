# Payment Verification Handoff

## Completed

Phase 3F-2: Tutor acknowledgement, Admin evidence decisions, canonical current-evidence totals, explicit final verification and atomic booking closure. Existing Tutor/Admin page shells retained with real data. Student payment pages show current valid funds/outstanding and factual workflow state. No schema changes.

## Files Created

- `includes/payment-verification.php`: exact settlement calculation, review locks, one-way decisions and final verification.
- `includes/payment-review-page.php`, `includes/payment-review-content.php`: shared Tutor/Admin controller and presentation, including verified/closed lists.
- `includes/payment-review-file.php`, `tutor/tutor-payment-file.php`, `admin/manage-payments-file.php`: protected reviewer downloads.
- `tests/payment_verification.test.py`.
- `docs/payment-verification-handoff.md`.

## Files Modified

- Tutor: `tutor-payments.php`, `tutor-payment-view.php`, `tutor-payment-confirm.php`, `tutor-payment-issue.php`, `tutor-payment-received.php`.
- Admin: `manage-payments.php`, `manage-payments-view.php`, `manage-payments-confirmed.php`, `manage-bookings-closed.php`.
- `includes/payment-page.php`, `includes/payment-content.php`: consistent Student read snapshot and settlement display.
- `tests/authentication.test.py`: new protected download routes require file IDs.
- `tests/booking_lifecycle.test.py`: update test descriptions for implemented read-only closure history.

## Tutor Acknowledgement

Authenticated active booked Tutor alone may move the current version from awaiting to received or issue. Received is NOT Admin valid, payment verified or booking closed. Each decision is immutable on that version. Admin status must still be pending. Negative Admin decisions freeze the version for Tutor review; inconsistent valid/awaiting states safely deny.

## Tutor Issues

Issue requires exactly `not_received`, `amount_mismatch` or `reference_mismatch`. Optional private remark and server UTC review time are stored. The existing Student correction workflow creates the next version of the same transfer, restarting awaiting/pending. No transfer or evidence is automatically created/edited by Tutor review.

## Admin Evidence Review

Current pending evidence can become valid only after Tutor received. Rejected/reupload_required are permitted with any approved Tutor status and require a reason. Store authenticated Admin identity and UTC timestamp; decisions cannot be rewritten. Admin valid is NOT payment verified. Negative decisions allow the existing same-transfer correction path.

## Current Submission Rule

`tmq_payment_current` selects only the highest attempt_number of every transfer. Missing evidence on an unexpected empty transfer blocks settlement. Old versions and their decisions/files remain unchanged. The same current selector and settlement calculation serve Student totals, reviewer display, aggregate decisions and final verification.

## Valid Total Calculation

Only current Tutor received + Admin valid evidence contributes amount_paid once. Rejected, pending, reupload_required and incomplete acknowledgement chains contribute zero. Parse DECIMAL strings into 64-bit integer sen with overflow protection; no binary floating point. Expected amount comes from bookings.expected_amount. Outstanding is max(expected minus valid total, zero).

## Aggregate Payment Status

After Admin evidence review: reupload_required first, unresolved chains/pending second, rejected third; otherwise all current evidence is valid and short totals become underpaid. Exact/excess totals remain pending with derived ready/manual-review messages. No invented status. Tutor/new Student evidence returns nonfinal aggregate to pending and clears aggregate reviewer metadata. Verified remains final and read-only.

## Underpayment

Resolved all-valid funds below the expected amount become underpaid. Student sees outstanding funds and may record a genuine new top-up transfer. Valid evidence cannot be edited into a larger payment. Unresolved receipts contribute zero but are displayed as awaiting review, not as an instruction to pay again.

## Rejected Evidence

Rejected contributes zero and stays retained. It may remain excluded while other genuine valid transfers settle the exact fee. Final verification independently evaluates evidence rather than requiring payment.status pending; an aggregate rejected payment can therefore settle when every current claim is resolved and valid_total equals expected_amount.

## Reupload Required

A current reupload_required blocks final verification even when other valid funds equal the fee. It requests corrected evidence for the SAME transfer, not more money. Correction inserts the next immutable version and invalidates old forms.

## RM40 / RM4 / RM36

Current accepted RM4 toward expected RM40 gives valid_total RM4 and outstanding RM36. A genuine RM36 top-up is a second transfer, initially contributing zero. After Tutor received/Admin valid it contributes RM36, giving exactly RM40. Receipt correction versions never add another RM4.

## Final Admin Verification

Canonical Admin POST: `admin/manage-payments-view.php?id=<booking_id>`, `payment_action=verify`, CSRF and displayed evidence_revision. Recheck active Admin, completed booking with both timestamps, nonfinal payment, current revision, all current chains and exact equality under locks. Pending/reupload_required block final verification; valid without Tutor received also blocks. Rejected is resolved and excluded. Underpayment and overpayment cannot verify. Excess remains manual/nonfinal; no refunds, repricing or ignored valid funds.

## Booking Closure

One transaction sets payments.status verified, verified_by_admin_id/verified_at and final aggregate metadata, then bookings.status closed/closed_at using the same UTC instant. Any failure rolls back both. No separate close mutation. Repeat verification returns controlled conflict without another write. Closed bookings, historical slots, generated occupancy, arrangement, completion timestamps and all receipts remain intact. Confirmed-payment/closed-booking routes are real read-only lists.

## Evidence Revision / Stale Protection

Every Tutor/Admin evidence decision increments evidence_revision exactly once. Student transfer/correction retains the existing increment. All reviewer forms require the displayed revision; final verification never silently accepts a stale revision. Review GETs use a consistent database snapshot so displayed evidence and revision belong together. Final settlement preserves the evidence revision and makes all normal evidence mutation paths read-only.

## Protected Receipt Access

Reviewer downloads take only submission_id; Tutor ownership is checked through transfer/payment/booking. Active Admin can inspect current and historical evidence. Retain storage-key validation, actual MIME checks, attachment disposition, private/no-store, nosniff and sandbox CSP. No public files or filesystem paths. Student access remains unchanged; Student pages omit private Tutor/Admin remarks. Tutor pages omit Admin private remarks.

## Concurrency

Order: booking -> payment -> ascending transfer IDs -> highest submission of each transfer. Never acquire participant or slot locks afterward. Lock-time revision, ownership, finality and current-version checks serialize competing Tutor/Admin decisions, Student corrections/top-ups and final verification. Conflicting requests return controlled 409 errors; SQL diagnostics are hidden. Tests use two independent PHP servers and login sessions, plus real DB privilege failure on the booking update to verify rollback of the preceding payment update.

## Security

Session-derived active role/relationship, prepared statements, escaped output, POST/CSRF, immutable review transitions and controlled stale/not-found responses. Submitted actor IDs, states and totals have no authority. Transfer and receipt writes remain in the original Student workflow. Reviewed payment detail routes use booking IDs; download routes use submission IDs.

## Test Results

- Phase 3F-2 payment verification: 331 assertions passed, including review immutability, protected current/historical receipts, all issue/negative-decision combinations, superseded valid evidence, RM4/RM36, exact decimal boundaries, stale revisions, independent HTTP races and actual second-write rollback.
- Phase 3F-1 payment submission: 244 passed.
- Phase 3E-3 lifecycle: 243 passed.
- Phase 3E-2 coordination: 172 passed.
- Phase 3E-1 booking changes: 181 passed.
- Phase 3D-2B booking management: 100 passed.
- Phase 3D-2A booking creation: 75 passed.
- Phase 3D-1 availability: 66 passed.
- Phase 3C profile verification: 207 passed.
- Phase 3B authentication: 784 passed, covering 94 protected pages.
- Total: 2,403 integration assertions and 10 existing JavaScript tests passed; zero final failures. Created/modified PHP files pass lint; `git diff --check` passes. Approved SQL/design files remain unchanged.

All suites removed their disposable databases/uploads. Before normal shutdown, the isolated MariaDB instance contained only bootstrap system/test databases and no temporary test accounts. Its resolved data directory and port were verified before shutdown; no real application database or pre-existing MariaDB process was touched. Normal shutdown logged completion and the process exited. Only its exact task-created runtime directory and marker were removed after verifying shutdown, process identity and the closed listening port.

## Deferred

Payment gateways/bank APIs/OCR, refunds/reversals/chargebacks, excess-payment automation, disputes/no-shows/fees, chat, notifications, feedback, support/FAQ/chatbot, reports and exports. No schema, SQL or approved database-design edits.

## Known Issues

The pre-existing XAMPP/MariaDB process issue is untouched. Tests use installed MariaDB 10.4.32 binaries with a separate temporary data directory, loopback port 33320, disposable databases and temporary uploads. No real application records are changed. HTTP presentation and protected responses are tested; no visual screenshot comparison.
