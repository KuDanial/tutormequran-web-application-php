# Notifications Handoff

## Completed

Phase 3G-2: persisted in-app notifications only, scoped to the recipient account. Student/Tutor centers use real records, explicit read actions and authorized links. No schema changes; approved SQL/design documents remain unchanged. No email, SMS or push delivery.

## Files Created

- `includes/notifications.php`: fixed event catalogue, transactional insert, recipient selection, count, read-state, bounded history and safe links.
- `includes/notification-page.php`, `includes/notification-content.php`: shared controller and presentation.
- `tests/notifications.test.py`.
- `docs/notifications-handoff.md`.

## Files Modified

- `includes/booking-creation.php`, `includes/bookings.php`, `includes/booking-changes.php`, `includes/booking-cancellations.php`, `includes/booking-completion.php`.
- `includes/booking-chat.php`, `includes/payments.php`, `includes/payment-verification.php`, `includes/certificates.php`.
- `student/student-notifications.php`, `tutor/tutor-notifications.php`.

## Notification Model

Use the existing `notifications` table. Each record has one recipient, an allowlisted type, server-generated plain-text title/message, server UTC `created_at` and nullable `read_at`. Booking/payment/chat notices have only `booking_id`; qualification notices have only `certificate_id`. The helper rejects absent/incompatible relations and requires the caller's existing PDO transaction. There are no account-only events in this phase.

## Allowlisted Types

`booking_requested`, `booking_accepted`, `booking_rejected`, `booking_change_requested`, `booking_change_accepted`, `booking_change_rejected`, `booking_change_withdrawn`, `booking_cancelled`, `booking_completion_needed`, `booking_completed`, `chat_message`, `payment_submitted`, `payment_tutor_received`, `payment_tutor_issue`, `payment_admin_valid`, `payment_admin_rejected`, `payment_reupload_required`, `payment_verified`, `certificate_approved`, `certificate_rejected`.

The application catalogue is authoritative for creation and link generation. Unknown historical types can display escaped text but have no action link. No new database enum or CHECK constraint.

## Event Matrix

| Successful event | Type | Recipient |
| --- | --- | --- |
| Student requests lesson | booking_requested | Tutor |
| Tutor accepts / rejects | booking_accepted / booking_rejected | Student |
| Participant proposes change, including arrangement | booking_change_requested | Counterpart |
| Counterpart accepts / rejects change | booking_change_accepted / booking_change_rejected | Original requester |
| Requester withdraws change | booking_change_withdrawn | Counterpart |
| Participant cancels | booking_cancelled | Counterpart |
| Admin cancels exceptionally | booking_cancelled | Both participants |
| First completion confirmation | booking_completion_needed | Counterpart |
| Second completion confirmation | booking_completed | Earlier confirmer |
| Committed chat message | chat_message | Counterpart |
| First/top-up/corrected evidence | payment_submitted | Tutor |
| Tutor receives / reports issue | payment_tutor_received / payment_tutor_issue | Student |
| Admin accepts / rejects evidence | payment_admin_valid / payment_admin_rejected | Student |
| Admin requests receipt correction | payment_reupload_required | Student |
| Explicit final verification and closure | payment_verified | Both participants |
| Admin approves / rejects certificate | certificate_approved / certificate_rejected | Certificate Tutor |

## Booking Notifications

Insert only after the successful validated transition and before commit. No self-notification by default. Idempotent completion and stale decisions return before notification insertion. Cancellation's automatic withdrawal of a pending proposal produces only the cancellation notification. Arrangement confirmation alone produces none.

## Chat Notifications

One generic notification for each committed text, attachment or location message. No private body, attachment filename, location label or coordinates are copied. Message, attachment metadata and notification share the existing transaction; rollback retains the existing staged-file cleanup behavior. No read receipts.

## Payment Notifications

Evidence submission/correction/top-up notifies only the Tutor. Tutor acknowledgement and Admin evidence acceptance explicitly retain the need for final verification. Reupload wording requests corrected evidence for the same transfer. No receipt path/hash, bank reference or private reviewer remark appears. Final verification inserts both participant notices in the payment-verification/booking-closure transaction. Opening payment alone creates none.

## Qualification Notifications

Approved/rejected pending certificates notify the owning Tutor through `certificate_id`. Existing review locks and one-way decisions prevent stale/concurrent reviews from producing duplicate notices. Private certificate paths and Admin remarks are omitted.

## Notification Center

Existing Student/Tutor portal shells and styling remain. Replace demo records with title, message, Malaysia-local time, explicit read/unread state and authorized action. Fetch at most 51 rows to display 50 and detect the next page; order by `created_at DESC, notification_id DESC`. No new Admin center; existing operational queues continue unchanged.

## Read / Unread

`read_at IS NULL` means unread. GET does not mark read. Mark-one and mark-all require authenticated active recipient, POST and CSRF. Recipient is session-derived, never submitted. The conditional UPDATE preserves the first read timestamp and is idempotent. A foreign/missing ID is a scoped no-op without disclosing its existence. No mark-unread, delete or edit action.

## Unread Count

One recipient-scoped COUNT uses the existing recipient/read index. Display the count in the center. Shared Student/Tutor headers contain ordinary Notifications navigation links, with no existing notification badge/icon; those headers remain unchanged. No polling or N+1 count queries.

## Safe Links

No arbitrary URLs are stored or accepted. A bounded joined query checks the recipient's actual booking/certificate relationship before generating links. Booking events use `*-booking-view.php?id=...`; chat uses `*-chat.php?id=...`; payment uses `*-payment-view.php?id=...`; Tutor qualifications use `tutor-certificate-view.php?certificate_id=...`. All are existing role-local routes. Foreign/missing relations have no action link; destination authorization remains authoritative. Mark-read redirects only to the role's fixed notification center.

## Transaction Integration

The insert helper accepts the existing PDO and refuses calls outside a transaction. No nested transaction, separate commit, background dispatch or best-effort insert. Errors propagate to existing domain rollback handlers. Capture booking/change/submission IDs before notification INSERT changes PDO's last-insert ID. Notification reads never invoke domain mutations.

## Concurrency

Existing booking/slot/participant and payment/transfer/submission lock orders remain unchanged. Recipient lookup uses ordinary reads; no additional explicit domain row locks. Existing FK enforcement still applies. Notification insertion follows successful state/revision checks immediately before commit. Competing decisions produce only the winning transition's notice; distinct committed chat messages and genuine transfers each produce their own notice. No deduplication schema or keys.

## Privacy

Server catalogue strings contain no private chat/payment/certificate fields, personal identifiers or SQL diagnostics. The centers expose only recipient-owned records and escaped text. A notification cannot establish ownership of its related domain object.

## Security

Reuse active-account/session/RBAC guards, prepared statements, CSRF and existing controlled errors. Creation fields are derived inside authorized domain helpers; submitted recipient/type/content/relation/timestamp/redirect fields have no authority. Output is escaped, links are allowlisted and GET is read-only.

## Test Results

| Suite | Passed assertions |
| --- | ---: |
| Phase 3G-2 notifications | 305 |
| Phase 3G-1 chat | 218 |
| Phase 3F-2 payment verification | 331 |
| Phase 3F-1 payment submission | 244 |
| Phase 3E-3 lifecycle | 243 |
| Phase 3E-2 coordination | 172 |
| Phase 3E-1 booking changes | 181 |
| Phase 3D-2B booking management | 100 |
| Phase 3D-2A booking creation | 75 |
| Phase 3D-1 availability | 66 |
| Phase 3C profile verification | 207 |
| Phase 3B authentication (96 protected pages) | 798 |

Total: 2,940 integration assertions and 10 existing JavaScript tests passed; zero final failures. All 14 created/modified PHP files pass lint; `git diff --check` passes. Approved SQL/design files are unchanged.

Notification tests cover all event recipients, no-self behavior, immutable decisions, server text, relations/UTC, privacy, pagination/order, GET purity, active-account/role denial, CSRF, read-state ownership/idempotence, unread totals, escaped stored output and foreign-relation link denial. Independent HTTP workers exercise booking/change/lifecycle/payment/certificate decisions, receipt-correction races, genuine transfers and chat sends. A disposable restricted DB account with notification INSERT denied proves rollback of booking acceptance, chat insertion, Tutor/Admin evidence review, final verification/closure and certificate review; errors remain controlled.

All suites remove their disposable databases/uploads and test DB accounts. The isolated runtime is normally shut down and its process exit verified before removing only its exact task-created temporary directory and marker. No real application records or pre-existing database process are touched.

## Deferred

No email/SMS/push, browser notifications, WebSockets, polling daemon, reminders/scheduler, preferences, broadcasts, editing/deletion, chat read receipts, feedback, support/FAQ/chatbot, reports/exports or refunds/disputes.

## Known Issues

The pre-existing XAMPP/MariaDB process issue is untouched. Validation uses installed MariaDB 10.4.32 binaries, a separate temporary data directory and loopback port 33322. No real application records are changed. HTTP presentation/security are tested; no browser screenshot comparison. OFFSET pagination can shift between requests when new events arrive; ordering within each response is deterministic.
