# Booking Chat Handoff

## Completed

Phase 3G-1: private database-backed booking chat with text, multiple protected attachments, manual locations and retained terminal-state history. Plain PHP POST/redirect/GET; no WebSocket requirement or added infrastructure. No schema changes or approved design/SQL edits.

## Files Created

- `includes/booking-chat.php`: participant scope, validation, transactional sends and bounded history.
- `includes/chat-uploads.php`: finite attachment content/extension validation and staged storage.
- `includes/chat-page.php`, `includes/chat-content.php`: shared controller and presentation.
- `includes/chat-file.php`, `student/student-chat-file.php`, `tutor/tutor-chat-file.php`: scoped downloads.
- `tests/booking_chat.test.py`.
- `docs/booking-chat-handoff.md`.

## Files Modified

- `student/student-chat.php`, `tutor/tutor-chat.php`: replace sample conversations with shared real chat, retaining portal shells; remove demo chat JavaScript.
- `includes/booking-detail-content.php`: participant booking-context chat links, including terminal history and arrangement pages.
- `includes/uploads.php`: permit randomized protected chat keys with the finite supported extensions; existing upload kinds retain their original limits/formats.
- `tests/authentication.test.py`: new protected file endpoints require an attachment ID.

## Booking Relationship

Use `student/student-chat.php?id=<booking_id>` and the corresponding Tutor route. Booking Student/Tutor identities come through the existing subtype relationships. Missing context points to the booking list. No general direct messaging, public conversation tokens or new relationship tables. A Tutor ID, username or public booking reference cannot authorize chat.

## Chat Access

Only the authenticated active booking Student or Tutor may read/send/download. Foreign and missing booking IDs return controlled 404 responses. Admin has no automatic private-chat access, viewer, download route or booking-detail chat link. A role mismatch returns 403; guests/inactive sessions go to login.

## Writable / Read-Only Booking States

Requested, accepted and completed permit sending. Rejected, cancelled and closed retain read-only history and protected downloads. Payment/evidence changes cannot reopen a terminal conversation.

## Text Messages

Trim input, require nonempty valid UTF-8, reject null characters and more than 2,000 Unicode characters without truncation. Persist ordinary line breaks. Escape all content and preserve line breaks with existing chat CSS; message text is never HTML. Optional attachment/location captions use the same limit.

## Message Immutability

Only the `chat_action=send` POST mutation exists. No edit, delete, unsend or Admin deletion operation. GET does not mutate messages or timestamps.

## Attachments

One to five files per attachment message; each is 1–10,485,760 bytes (10 MB). Allowlist: PDF, UTF-8 TXT, DOCX, XLSX, PPTX, JPEG/JPG, PNG, MP4 and WebM. Server content/MIME and extension checks ignore browser MIME. Reject scripts, HTML/SVG, executables, standalone archives and macro-enabled Office formats. Office ZIP containers are inspected using bundled Phar/DOM without extraction; required document parts, bounded expanded size/entry count, XML and active/embedded content exclusions apply. Images/PDF/video containers receive format checks. This is format validation, not malware scanning or media transcoding.

Uploads reuse the outside-document-root storage configuration and randomized `chat/` keys. Database rows store metadata only. File staging precedes the mutation transaction; message and all metadata rows commit together. Validation/DB failures discard only files staged by that request, preserving historical attachments. PHP deployment limits still apply: configure `upload_max_filesize` at least `10M`, `post_max_size` above the combined request (for five maximum-size files, e.g. `56M`), and `max_file_uploads` at least five. Oversized PHP requests/uploads fail without creating messages.

## Protected Attachment Access

Role-specific `*-chat-file.php?id=<attachment_id>` endpoints authorize through attachment → message → booking → authenticated participant. They validate the storage key, file size and type, stream an attachment disposition with safe generated download name, and send private/no-store, nosniff and sandbox CSP headers. Raw storage keys/paths never appear in chat output. Terminal booking downloads retain the same participant restrictions.

## Location Sharing

Persist the actual `location_label`, `latitude`, `longitude` fields with `message_type=location`. Label-only is valid; otherwise supply both coordinates. Labels are valid UTF-8 up to 500 characters and escaped on output. Exact decimal input supports at most six decimal places, latitude ±90 and longitude ±180; reject partial pairs, exponent notation, nonnumbers, out-of-range values and silent rounding. No geolocation, external Maps API or reverse geocoding.

## Arrangement Separation

Location messages are communication only. No booking venue, `class_mode`, agreement version or arrangement confirmation is changed. The UI links back to the booking and explains that agreed venue changes use the existing arrangement/change-request workflow.

## History

Initially show the latest 100 messages, rendered in `sent_at ASC, message_id ASC` order. A scoped tuple cursor provides older pages; latest/refresh navigation returns to the newest page. Attachment metadata is fetched only for the bounded page. Sender name and Malaysia-local timestamp use escaped output and the existing time helper. Inserts use server UTC; historical timestamps are untouched.

## Concurrency

Stage files before locking. Inside the transaction lock only the booking row, recheck participant relationship, active actor and current writable status, then insert message/attachments and commit. Booking terminal transitions already lock that row, so competing sends and transitions serialize without participant/slot/payment locks. A send waiting behind a terminal update rejects with 409 after that update commits. Independent Student/Student and Student/Tutor sends preserve both messages; timestamps plus message IDs establish deterministic order. Lock conflicts return controlled 409 responses; other storage/DB failures return generic 503 responses.

## Security

Existing session/RBAC/account guards, session-derived sender, prepared statements, CSRF and POST-only sending. Submitted sender/participant/role fields have no authority. Stored-XSS strings, filenames and location labels are escaped. Chat performs no booking, availability, arrangement, completion, payment, evidence-revision or notification writes. SQL diagnostics are not exposed.

## Test Results

- Phase 3G-1 booking chat: 218 assertions passed, covering both participants, foreign/Admin/guest/inactive denial, all six statuses, immutable text and stored XSS, every allowed attachment format, exact 10 MB and oversized files, spoofed/forbidden formats, multiple attachments, scoped downloads, locations, chronological pagination, independent HTTP sends and terminal-state lock contention. A real metadata INSERT privilege failure verifies message rollback and cleanup of only newly staged files.
- Phase 3F-2 payment verification: 331 passed.
- Phase 3F-1 payment submission: 244 passed.
- Phase 3E-3 lifecycle: 243 passed.
- Phase 3E-2 coordination: 172 passed.
- Phase 3E-1 booking changes: 181 passed.
- Phase 3D-2B booking management: 100 passed.
- Phase 3D-2A booking creation: 75 passed.
- Phase 3D-1 availability: 66 passed.
- Phase 3C profiles: 207 passed.
- Phase 3B authentication: 798 passed, covering 96 protected pages.
- Total: 2,635 integration assertions plus 10 existing JavaScript tests passed; zero final failures. All 11 created/modified PHP files pass lint; `git diff --check` passes. Approved schema/design files are unchanged.

Each suite removed its disposable database and temporary uploads. The initial task-created database instance developed a privilege-table error; it was replaced without repairing tables or changing recovery settings. After the interruption, both task-created runtimes were verified to contain only bootstrap databases and no test accounts. Cleanup uses normal shutdown, logged shutdown completion and process-exit verification before deleting only the exact task-created runtime directories and marker. No real application records or pre-existing database process were changed.

## Deferred

Notifications are Phase 3G-2. `read_at` remains unused; no unsafe GET mutation or real-time read-receipt system. No message editing/deletion, general messaging, Admin private chat, support chat, polling daemon, WebSocket, calling, feedback, reports or exports.

## Known Issues

The pre-existing XAMPP/MariaDB process issue is untouched. Runtime validation uses installed MariaDB 10.4.32 binaries, a separate temporary data directory, loopback port 33321, disposable databases and temporary protected uploads. No real application records are changed. HTTP presentation/security are tested; no browser screenshot comparison. PHP request limits can be lower than the application's per-file maximum until deployment configuration is adjusted as above.
