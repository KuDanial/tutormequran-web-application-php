# Authentication Handoff

## Completed

Phase 3B implements Student/Tutor account registration, username/password login, sessions, active-account/role guards, POST logout, CSRF, logged-in password changes and CLI Admin provisioning. The approved schema is unchanged. Existing layouts and unrelated demo modules remain; account names in shared headers and the Student greeting now come from the signed-in account.

## Files Created

- `includes/auth.php`: session, CSRF, account validation/creation, login, guards and password helpers using the existing `tmq_db()` PDO connection.
- `includes/auth-message.php`, `includes/password-change.php`: shared notices and password form handling.
- `logout.php`: CSRF-protected POST endpoint.
- `scripts/create_admin.php`: CLI-only Admin provisioning and optional settings initialization.
- `assets/css/auth.css`: preserves existing dropdown-link styling on native logout buttons.
- `tests/authentication.test.py`: isolated HTTP/PDO integration tests using disposable databases.
- `docs/authentication-handoff.md`: this handoff.

## Files Modified

13 existing files: `login.php`, `register.php`; `includes/config.php`, `includes/header.php`, `includes/student-header.php`, `includes/tutor-header.php`, `includes/admin-header.php`; `student/student-profile-edit.php`, `tutor/tutor-account-edit.php`, `admin/admin-profile.php`; `student/student-dashboard.php`, `tutor/tutor-dashboard.php`; `assets/js/main.js`.

No approved design/schema/configuration/connection files were changed. No existing CSS file was rewritten.

## Registration Flow

Both existing registration forms now POST with CSRF. Server validation covers required personal fields, username, email, password/confirmation, IC format (not uniqueness), phone/address, supplied gender/date of birth and the existing adult/Malaysia eligibility declaration. Username is normalized lowercase, 3–80 ASCII letters/digits/dots/underscores/hyphens; email is trimmed/lowercased and validated. Passwords require 8–72 bytes, a letter and a number; the upper bound avoids bcrypt truncation. Names allow up to 100 Unicode characters. Supplied DOB must be a real date and at least 18 years ago in Malaysia time.

The existing forms gained matching confirmation inputs. Duplicate username/email gets a controlled notice. `users` and its matching `students` or `tutors` row are created in one transaction; subtype failure rolls back the user. No public Admin role is accepted. Registration redirects to login with a success message, without automatically authenticating.

Tutor accounts start active with a draft profile and no selected certificate. Certificate controls remain visible but disabled, with an explicit explanation that submission comes later. No file is uploaded or certificate created. The new Tutor's application dashboard shows account creation/incomplete application instead of claiming a sample Ijazah was submitted. This is the staged account-only registration requested for Phase 3B.

## Login Flow

The existing interface specifies **username**, so login accepts username/password, not email. `password_verify()` checks the stored `password_hash()` value. Wrong credentials and inactive accounts receive the same generic error. Session IDs regenerate on success. The role selector remains visually present but has no authority: role always comes from the database.

Student redirects to `student/student-dashboard.php`; Admin to `admin/admin-dashboard.php`; Tutor to `tutor/tutor-dashboard.php`, or the existing verified dashboard if the account already has an approved certificate. No approval is created by login. Demo entry links were removed from login. Remember Me is disabled; Forgot Password still links to Help. Real auth forms are excluded from the old mock handlers in `main.js`; unrelated JavaScript remains unchanged.

## Session Structure

`TMQSESSID` uses strict, cookie-only PHP sessions, HttpOnly, SameSite=Lax, an installation-scoped cookie path and Secure when PHP reports HTTPS. Identity is `auth.user_id`, `auth.role`, `auth.username`, plus the database password-change timestamp as `auth.password_version`. CSRF and short-lived success flags also use the session; passwords/hashes and personal profile data do not.

Protected requests re-read account status, role and password version. Deactivation, role changes or a password change from another session invalidate access. Password changes regenerate the current session ID and retain only its new password version. Session-storage failure returns a controlled 503. Successful logout clears session data, expires the cookie and destroys the session. GET logout returns 405; failed logout CSRF leaves authentication intact.

## Role Guards

All **91 role pages** already load `includes/config.php` before output. Its shared bootstrap determines the role directory from the executed file's resolved filesystem path and calls `tmq_require_role()`: **25 Student**, **42 Tutor**, **23 Admin**, **1 Admin report**. There is no per-page markup rewrite or reliance on submitted roles/URLs for identity.

Unauthenticated access redirects to the installation's login page; a wrong role receives controlled HTTP 403. Protected responses use `Cache-Control: no-store`. Fixed internal redirects work at the web root and under a subdirectory; no user-supplied return URL is accepted. Public pages/assets remain public. Future role pages must keep the same pre-output bootstrap.

## CSRF

One reusable random 32-byte session token and `hash_equals()` protect registration, login, logout and password changes. Tokens rotate with login/session replacement and password changes. Invalid tokens produce HTTP 403 without changing account/session data. These helpers can be reused by later CRUD phases.

## Password Change

The existing separate password sections work on `student/student-profile-edit.php`, `tutor/tutor-account-edit.php` and `admin/admin-profile.php`. Only the signed-in user can change their password; submitted target IDs/profile fields are ignored. Current password, new-password policy/confirmation and CSRF are required. The account row is locked during validation/update. Success uses a redirect and a notice; other sessions require a fresh login.

Student/Tutor changes respect the approved `profile_field_settings` password toggle. **A missing setting is disabled**, so the first real Admin must initialize settings before these roles can change passwords. Admin changes do not depend on those Student/Tutor toggles. Other profile fields remain demos and are not saved.

## Admin Provisioning

No real Admin or default credentials were created. From the project directory, provide JSON through standard input; never pass a password as a command-line argument or commit a password file. Example PowerShell uses a hidden prompt:

```powershell
$tmqAdmin = @{
    username = Read-Host 'Admin username'
    email = Read-Host 'Admin email'
    first_name = Read-Host 'First name'
    last_name = Read-Host 'Last name'
}
$tmqSecret = Read-Host 'Password (8–72 bytes, letter and number)' -AsSecureString
try {
    $tmqAdmin.password = ([System.Net.NetworkCredential]::new('', $tmqSecret)).Password
    $tmqAdmin | ConvertTo-Json -Compress | & C:/xampp/php/php.exe scripts/create_admin.php --init-settings
} finally {
    $tmqAdmin.Remove('password')
    Remove-Variable tmqSecret, tmqAdmin
}
```

The script validates input, hashes the password, and transactionally creates users + admins. Web execution returns 404; duplicate identity fails cleanly. No plaintext password is printed. `--init-settings` runs the approved seed with the newly created real Admin ID only when settings are empty. Existing settings are left unchanged. If settings initialization fails after account creation, the message explicitly reports that the Admin already exists; repair/initialize settings rather than creating that Admin again.

Alternatively initialize settings afterward using the existing seed, selecting `tutormequran` and setting `@profile_settings_admin_id` to a real `admins.admin_id` in the same database session. No fake Admin is needed.

## Database/Test Result

- MariaDB 10.4.32 / PHP 8.2.12 / PDO MySQL. The real `tutormequran` database did not exist; it was imported from the unchanged approved SQL. It contains 23 tables and canonical reference rows, **zero users and zero settings** at handoff. All 23 tables returned CHECK TABLE OK. No existing user database was dropped.
- `python tests/authentication.test.py`: **763 assertions passed**. `python tests/authentication.test.py --subdirectory`: **763 passed**. Coverage includes all requested registration/login/logout/CSRF/password/Admin cases; all 91 protected pages for anonymous and all three roles; forged role/target inputs; deactivation during a session; password-based invalidation of other sessions; and rollback after deliberately failing subtype insertion.
- Each test run created an existence-checked, random-named `tutormequran_auth_validation_...` database, ran test Admin/settings initialization only there, stopped its own HTTP test server and dropped only its owned database. All test accounts were removed with those databases. The real application database contains no test accounts.
- PHP lint: **115 files passed**. Existing Node tests: **10 passed, 0 failed**. Static schema validator: passed.
- Existing frontend audit completed with its unchanged baseline: 3 placeholder links, 2 unmatched closing tags, 1 missing ARIA target. Its generated report was restored; unrelated findings were not edited.
- The legacy Phase 1 conversion audit was also run: lint passed, but its exact HTML-equivalence checks **fail as expected** after functional forms, auth styling and protected rendering replace static HTML behavior. It is not an authentication-aware acceptance test; no guard was weakened to satisfy it. Its initial sandbox session-directory permission errors were eliminated by rerunning outside the sandbox.

## Deferred Features

Full profiles, certificate submission/approval, tutor publication, search, availability, bookings, payments, chat, feedback, notifications, FAQ/support/report data and exports remain unimplemented. Email password reset and persistent Remember Me remain deferred. Existing non-authentication demo data/actions are retained; role protection does not turn those modules into production workflows. No new database tables or schema changes were introduced.

## Known Issues

- **Local MariaDB health:** the previous validation's shutdown left PID 4424 holding InnoDB files without a listener. After explicit permission, that stale process was terminated and XAMPP restarted. MariaDB then logged system-tablespace/redo messages including “log sequence number ... is in the future” and possible-corruption warnings. Account integration tests and CHECK TABLE passed, but these do **not** establish overall server health. No recovery settings, redo-file deletion or repairs of other databases were attempted. Review/back up the local XAMPP data before relying on this installation beyond development. MariaDB was left running for development; temporary HTTP servers were stopped.
- The first real Admin and the approved settings still need the manual provisioning step above. Until then, Student/Tutor password changes correctly fail closed on missing settings.
- Other role pages, including approval/profile views, still contain frontend sample content. Their display is not evidence of an actual database approval or saved profile. Certificate/teaching-tool eligibility beyond account/role authorization belongs to the later verification phase.
- The legacy conversion audit needs a future authenticated/render-aware comparison mode. This phase leaves that audit and its historical HTML references unchanged.
- No unresolved database-design decision was needed for authentication. Production deployment hardening, including deployment-level login throttling, is not covered by these local functional tests.
