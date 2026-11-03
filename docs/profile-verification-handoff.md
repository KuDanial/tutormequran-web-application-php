# Profile and Verification Handoff

## Completed

Phase 3C implements database-backed Student/Tutor profiles, relational teaching information, certificate submission/history/Admin review, displayed qualifications, visibility, public Tutor pages, protected uploads and the approved editable-field settings. Existing layouts and CSS classes are retained. Authentication, database schema and approved design documents are unchanged. No later application module was implemented.

## Files Created

11 files, including this handoff:

- `config/uploads.php`
- `includes/uploads.php`, `includes/profiles.php`, `includes/certificates.php`
- `includes/profile-page.php`, `includes/profile-view-helpers.php`, `includes/certificate-panel.php`, `includes/public-tutors.php`
- `profile-file.php`
- `tests/profile_verification.test.py`
- `docs/profile-verification-handoff.md`

## Files Modified

31 files:

- Root: `register.php`, `tutors.php`, `tutor-detail.php`.
- Shared: `includes/student-header.php`, `includes/tutor-header.php`.
- Student: `student-profile-view.php`, `student-profile-edit.php`.
- Tutor: `tutor-account-edit.php`, `tutor-account-verified.php`, `tutor-application-approved.php`, `tutor-application-rejected.php`, `tutor-certificate-upload.php`, `tutor-certificate-view.php`, `tutor-certificate-replace.php`, `tutor-certificate-verified.php`, `tutor-dashboard.php`, `tutor-payment-qr.php`, `tutor-profile-draft.php`, `tutor-profile-setup.php`, `tutor-profile-edit.php`, `tutor-profile-view.php`, `tutor-profile-private.php`, `tutor-profile-ready.php`, `tutor-profile-visibility.php`.
- Admin: `admin-settings.php`, `manage-certificates.php`, `manage-certificates-view.php`, `manage-tutors.php`, `manage-tutors-view.php`, `manage-tutors-edit.php`.
- `tests/authentication.test.py`: public Tutor detail without an ID now correctly expects 404; authentication assertions remain intact.

No existing CSS/JavaScript files were changed. Registration/dashboard wording now directs users to the implemented certificate/profile steps. Shared headers use actual profile photos.

## Student Profile

View/edit uses the authenticated account, never a submitted owner ID. Personal fields, email uniqueness and photo uploads are validated server-side. Disabled settings reject attempted changes, even if HTML controls are bypassed. Existing password-change handling is reused unchanged.

## Tutor Profile

Account/personal details, teaching information, social links and QR setup persist to the approved columns. Existing separate setup forms save their own sections. Multi-table saves use transactions and roll back on failure. Admin Tutor pages show actual accounts and support the existing account/profile fields, including deactivation; they do not physically delete users.

## Languages and Lesson Focuses

Canonical records populate checkbox controls. Selections persist through `tutor_languages` and `tutor_lesson_focuses`; composite keys prevent duplicates. There is no comma-separated storage or separate lesson catalogue. Disabled controls do not accidentally clear relationships.

## Profile Completeness

`tmq_profile_missing()` is the single rule used by profile screens, visibility updates and public eligibility. It requires an active account, owned approved displayed certificate, photo, teaching state/location, supported mode, positive hourly rate, bio, at least one language and at least one lesson focus. Future availability is deliberately not required. Removing required information from a Public profile is rejected with instructions to switch Private first.

## Certificate Upload

Tutor identity comes from authentication. Submissions use the approved purpose/status values and store file metadata, qualification information and timestamps. Initial submissions, corrections and higher qualifications create new records. Corrections must reference an owned pending/rejected submission; a replaced pending submission becomes superseded. Approved history is never overwritten.

## Certificate Review

Admin list/detail pages show real submissions and authorized downloads. Approve/reject actions require the existing CSRF token and authenticated Admin identity. Rejection requires a reason. Transactions record reviewer/time and reject repeated review of a non-pending submission. An explicit review checkbox can select an approved certificate as the displayed qualification.

## Displayed Qualification

Tutor selection accepts only an owned approved certificate. Multiple approved certificates remain stored, with one selected pointer and no automatic ranking. A pending or rejected later submission leaves the previous approved selection intact.

## Public Visibility

Public updates recheck completeness inside a transaction. Private/inactive/incomplete Tutors and Tutors without an approved displayed certificate are excluded regardless of a submitted visibility value. An eligible Public Tutor with zero availability remains visible.

## Public Tutor Pages

`tutors.php` uses actual eligible Tutors, with name/bio, state, mode and rate filters plus an empty state. `tutor-detail.php?id=<tutor_id>` uses prepared queries and returns a controlled 404 for missing/private/ineligible Tutors. Templates expose only intended public teaching details, photo, qualification and social links; they omit private contact/address/IC, certificate paths/files and review remarks. Demo slots/reviews/booking actions were replaced by truthful deferred-feature messages; no availability filtering or booking operation was added.

## Upload Security

Certificates accept PDF/JPG/JPEG/PNG; photos and QR accept JPG/JPEG/PNG, all up to 5 MiB. Validation checks upload provenance, extension, MIME, file signatures, image dimensions/content markers and obvious executable content. Storage names are random; original names are metadata only. File paths are controlled, with traversal/symlink checks. Failed database writes remove only the newly uploaded orphan file; older committed files are retained.

`TMQ_UPLOAD_ROOT` configures an absolute directory outside the HTTP document root. The default is the project sibling `tutormequran-private-storage`; configure a different location when deploying beneath a broader document root. The PHP process needs write access there. No upload directory was created inside the repository.

`profile-file.php` streams files without exposing disk paths. Certificates require their owning Tutor or Admin and download as attachments. QR access is owner/Admin only. Photos allow owner/Admin access or an eligible Public Tutor's photo. Delivery uses MIME/nosniff, restrictive CSP and no-store headers. Upload limits also need compatible PHP `upload_max_filesize`/`post_max_size` values.

## Admin Settings

Only the approved 7 Student and 15 Tutor keys are accepted. Saves require Admin/CSRF and record the authenticated Admin updater. Missing settings default to locked. There is no generic permissions system. One real Admin already exists locally; credentials and existing production settings were not changed. If initializing a new installation, provision a real Admin using `scripts/create_admin.php --init-settings`; never use fake Admin records.

## Test Results

Environment: PHP 8.2.12, MariaDB 10.4.32, PDO MySQL and fileinfo available.

- `python tests/profile_verification.test.py`: **207 assertions passed**, covering authenticated ownership, settings enforcement, relational persistence, validation/rollback, output escaping, duplicate email, certificates/history/review, displayed qualification, public eligibility/privacy, protected files and QR uploads.
- Phase 3B authentication suite: **763 assertions passed** at the root URL and **763 passed** with `--subdirectory`; all 91 protected pages and authentication/password/logout flows pass.
- `node --test tests/booking-availability.test.cjs`: **10 passed, 0 failed**. These existing frontend tests do not implement database availability/booking.
- PHP lint: **124 files, 0 failures**.
- `python scripts/validate_database_schema.py`: PASS; approved 23-table/42-FK schema unchanged.
- Runtime suites imported the schema into uniquely named temporary databases and cleaned up only their own databases/accounts/uploads. Real accounts were not used as fixtures. The existing real Admin count remained one.
- Read-only `CHECK TABLE` on real users, students, tutors, certificates, profile settings and both teaching relationship tables: all OK.
- Baseline hash comparison confirms the approved SQL/design, authentication core, PDO configuration and existing CSS/JS were not modified.

No pixel-by-pixel browser comparison was performed. Legacy Phase 1 static HTML-equivalence checks are not an acceptance criterion for database-backed, authorized pages.

## Deferred Features

Availability, booking/change/cancellation/completion, chat, payment receipt/acknowledgement/verification, feedback, notifications, support, FAQ management, reports and exports remain deferred. QR image storage alone is implemented. No Phase 3D work was started.

## Known Issues

- MariaDB's error log still contains the previously reported InnoDB “log sequence number ... is in the future”/possible corruption warnings. Runtime tests and the relevant real-table checks succeeded. No recovery, repair, configuration change or deletion of MariaDB files was attempted.
- GD is absent locally. Upload checks are not antivirus scanning or a complete PDF parser/image re-encoder. Protected storage and forced certificate download reduce exposure; do not describe validation as comprehensive malware detection.
- Replaced committed photos/QR files are retained to avoid unsafe deletion and possible historical references; automated orphan/retention cleanup is deferred.
- Configure protected storage permissions/location for the actual deployment document root. There are no unresolved schema/business-rule blockers for this phase.
