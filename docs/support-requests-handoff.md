# Support Requests Handoff

## Completed

Phase 3H-2A implements authenticated support intake, scoped history and protected screenshots in the existing Student/Tutor pages. Public Help directs users to sign in. Existing styling and local image preview remain; the PHP forms now submit without requiring JavaScript. No schema or approved database-design changes.

## Files Created

- `includes/support-requests.php`: validation, transactional creation and scoped history.
- `includes/support-page.php`: shared authenticated controller and form replay protection.
- `includes/support-history.php`: escaped history and pagination.
- `support-file.php`: authenticated owner/Admin screenshot download.
- `tests/support_requests.test.py`: disposable integration tests.
- `docs/support-requests-handoff.md`.

## Files Modified

- `student/student-support-request.php`, `tutor/tutor-support-request.php`: connect existing forms, screenshot upload and real history.
- `help.php`: replace unsupported guest form with sign-in guidance.
- `includes/uploads.php`: allow protected support JPG/PNG keys; support-only checks reject active web content and client directory prefixes.
- `assets/js/student-workspace.js`: allow the persisted support form to submit normally while retaining image preview and the static HTML prototype behavior.

## Support Model

Actual `support_requests` fields: `request_id`, `requester_user_id`, `category`, nullable `booking_id`, required `description`, nullable `photo_path`, `status`, nullable `handled_by_admin_id`, nullable `resolution_note`, `created_at`, nullable `resolved_at`.

The schema CHECKs allow categories `billing`, `attendance`, `other` and statuses `open`, `resolved`. There is no subject, guest contact identity, conversation/thread, assignment or priority field. Requester references `users`; optional booking references `bookings`; optional handler references `admins`, all with RESTRICT foreign keys. Existing indexes cover status/created time, requester/created time and the optional foreign keys. No constraints or indexes changed.

Approved design section 3.23 explicitly calls this authenticated Student/Tutor intake and defers Admin handling until its management interface is implemented. Repository searches found no Admin support management page or transition UI. This phase therefore adds no Admin queue or state mutation. This is a documented deferral, not an inferred open/resolved state machine.

## Submitter Authorization

Existing pre-output authentication/RBAC and active-account checks protect both role pages. Active Students and Tutors, including Tutors without an approved certificate, may submit. Guests and Admins cannot submit through these pages. Role, requester and ownership always derive from the authenticated session. Submitted identity, role, status, handler, resolution and timestamp fields have no authority.

## Request Creation

POST with existing CSRF and a session form token creates one `open` record with server UTC creation time. Category and description are required; an optional numeric booking ID must belong to the current Student or Tutor. One prepared INSERT/SELECT rechecks the active role/subtype and optional booking ownership as part of insertion. There is no separate authorization SELECT followed by an unscoped INSERT. Successful requests redirect to a fixed role-local detail/history URL.

## Validation

Text inputs must be scalar strings containing valid UTF-8 and are trimmed. Description must be nonempty and within the actual TEXT limit of 65,535 UTF-8 bytes, without truncation. The former prototype's 3,000-character HTML limit is removed. Categories use the exact CHECK allowlist. Optional booking IDs must be canonical positive integers within the unsigned INT range; foreign and missing bookings receive a controlled not-found response. Plain text is preserved, including markup, and every displayed description/resolution is escaped. Unknown actions are rejected; submitted statuses cannot mutate records.

## Attachment

One optional JPG/JPEG or PNG, at most 5,242,880 bytes. Reuse `includes/uploads.php`: actual uploaded-file validation, server fileinfo MIME, extension/content relationship, image dimensions/format boundaries, executable-content rejection and randomized 40-hex filenames. Support additionally rejects active HTML/script/SVG/embed markers and directory prefixes retained in PHP's `full_path`. Browser Content-Type has no authority.

Only a protected `support/<random>.jpg|png` key is stored in `photo_path`, never a BLOB, original filename or public raw URL. Storage must be outside the document root, with existing traversal/symlink checks. Rejected ownership or DB failure removes the staged file; a failed upload creates no record. Exact 5 MB acceptance and oversize rejection are tested. Deployment PHP request/upload limits must allow the application's 5 MB file plus multipart overhead.

## Protected Attachment Access

`support-file.php?id=<request_id>` accepts authenticated GET only. The query scopes non-Admin access to `requester_user_id`; Admin access requires the existing Admin role guard and an actual Admin subtype. Active-account enforcement applies to downloads. Foreign users, including the Tutor/Student counterpart of an attached booking, receive 404. No private booking-chat access follows from support access.

Downloads use a fixed safe filename, image MIME, attachment disposition, `nosniff`, no-store and restrictive CSP. Storage keys and filesystem paths never appear in rendered history or download URLs. Invalid/missing attachments receive controlled responses.

## User History

The existing support page now includes the user's own persisted requests and an optional scoped detail lookup. Display category, description, numeric booking reference, actual status, Malaysia-local creation time, optional escaped resolution note/time and authorized screenshot link. Fetch at most 51 records to show 50 and detect continuation; order `created_at DESC, request_id DESC`. Pagination cannot hide a detail record or grant foreign access.

## Admin Management

Deferred as specified by approved design section 3.23: there is no existing Admin support interface to connect. No resolution, reopen, assignment, deletion or editing endpoint was invented. Authorized Admin screenshot retrieval is implemented. Existing resolved records and resolution notes can be displayed to their owners; tests seed these only in disposable databases. No Admin chat or access to booking conversations is added.

## Status Handling

Creation always writes `open`. History displays actual `open`/`resolved` records. Users cannot submit status mutations, and arbitrary action/status combinations are rejected. Handler/resolution fields remain NULL at creation. Admin transition/concurrent decision testing is not applicable because Admin handling remains deferred; schema status values alone do not establish transition semantics.

## Concurrency

The existing PHP session lock serializes submissions sharing a session; a per-session form token is consumed only after successful commit. Simultaneous duplicate POSTs yield one committed request and one controlled 409. Stale replay is rejected. Independently opened authenticated sessions may submit separate requests, as there is no approved content-deduplication key. A new form allows a new request.

Atomic insertion checks current active ownership. A concurrent booking-owner change winning before the authorization read denies creation and removes the staged screenshot. Tests use independent HTTP workers and a competing database transaction. No explicit booking/payment/availability locks or domain updates are introduced. Normal InnoDB DML/FK locking still applies. Application exceptions roll back the request and discard staged files; no cross-system crash-recovery subsystem is introduced.

## Security

Prepared statements, active-account/RBAC guards, session-derived ownership, POST/CSRF mutation, UTF-8/type/length validation, escaped output, protected uploads/downloads, fixed redirects and controlled errors. No SQL diagnostics or filesystem paths appear in errors. GET has no domain mutation. Session form tokens supplement, rather than replace, existing CSRF.

## Side Effects

Only `support_requests` receives application database writes. Full snapshots of every other table remain unchanged by support operations. No booking, availability, arrangement, completion, payment, evidence, certificate, feedback, chat or notification side effects. No support notification types: the existing 20-event catalogue and notification schema remain unchanged. No booking-chat integration, live chat, email, SMS, push, FAQ backend or chatbot work. No schema changes.

## Test Results

Focused support suite: 117 assertions passed. Includes authenticated submitters, validation and TEXT boundary, UTF-8/XSS, GET/POST/CSRF, forged identities, owned/foreign bookings and requests, deterministic bounded history, read-only statuses, JPEG/PNG and exact 5 MB boundary, content/path spoofing, owner/Admin/foreign downloads, upload/DB failure cleanup, duplicate submissions, concurrent ownership change and all other-table snapshots.

| Suite | Passed assertions |
| --- | ---: |
| Phase 3H-2A support requests | 117 |
| Phase 3H-1 feedback | 144 |
| Phase 3G-2 notifications | 305 |
| Phase 3G-1 booking chat | 218 |
| Phase 3F-2 payment verification | 331 |
| Phase 3F-1 payment submission | 244 |
| Phase 3E-3 booking lifecycle | 238 |
| Phase 3E-2 booking coordination | 172 |
| Phase 3E-1 booking changes | 181 |
| Phase 3D-2B booking management | 100 |
| Phase 3D-2A booking creation | 75 |
| Phase 3D-1 availability | 66 |
| Phase 3C profiles | 207 |
| Phase 3B authentication (96 protected pages) | 798 |

Total: 3,196 integration assertions and 10 existing JavaScript tests passed; zero failures in the final suite runs. Counts include one final successful run per suite, not accumulated reruns. The existing lifecycle suite skipped five same-date participant terminal-race checks because the run occurred before 01:00 Malaysia time, when no eligible whole-hour lesson existed that day. Its Admin terminal-race checks still ran. PHP lint passed for all eight created/modified PHP files. `git diff --check` passed. Approved schema/design files remain unchanged.

The final support run included the finalized protected-download implementation. No application code changed afterward. Successful results were preserved during the cleanup/handoff continuation; expensive suites were not rerun. Earlier support runs exposed a test-helper argument collision and PHP's filename-directory normalization; the helper and support filename validation were corrected before the final successful runs. An initial notification run failed in the disposable MariaDB privilege tables and was rerun successfully on the fresh instance described below.

Each suite dropped its own randomly named disposable database and cleaned its HTTP servers/uploads. Before shutdown, the healthy instance on port 33325 contained only bootstrap system/test databases and bootstrap accounts; no test accounts remained. Its log recorded normal shutdown and `Shutdown complete` on 2026-11-09. The continuation independently verified PID 12440 was absent, checked the exact resolved temporary path and matching marker, then removed only `tmq-support-ed11579ae870422e9e2ae1a85f750f6a` and `tmq-support-fresh.txt`. The prior combined command's nonzero exit did not override the shutdown-log/process-exit evidence. The earlier stalled instance is retained as documented under Known Issues.

## Deferred

Admin support management UI/actions per approved design; guest intake; FAQ backend; chatbot; reports; exports; email/SMS/push; notification redesign; booking/payment redesign; support threading/live chat. No new schema fields or transition rules.

## Known Issues

OFFSET pagination can shift between requests when new records arrive. Duplicate-form protection is scoped to the authenticated PHP session, not a durable cross-session deduplication key. Unexpected process termination between file staging and DB commit may leave an inaccessible orphan, as in the existing protected-upload architecture. HTTP output/security were tested; no visual redesign or screenshot comparison was performed.

The known pre-existing XAMPP/MariaDB issue remains untouched. All fixtures use randomly named disposable databases and temporary upload directories on task-created loopback MariaDB instances. An initial isolated instance developed an Aria privilege-table file-descriptor error during notification fault-injection setup; no database repair or recovery settings were attempted. Validation continued on a fresh temporary instance with redirected stdout/stderr.

Outstanding temporary-resource cleanup: the initial task-created MariaDB process, PID 21932 (started 2026-11-08 at 23:44:37 Malaysia time), remains present after its earlier normal shutdown request. A continuation check found port 33324 refused connections, so another normal shutdown could not be issued. It was not force-killed. Its exact directory `C:/Users/aster/AppData/Local/Temp/tmq-support-7d6b4cf3dfcc4df890c4a66813ef14c5` and matching `C:/Users/aster/AppData/Local/Temp/tmq-support-current.txt` marker were retained. Do not delete that directory until process exit is verified. No unrelated MariaDB/XAMPP process or real application database was changed.
