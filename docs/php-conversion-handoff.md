# PHP Conversion Handoff

Phase 1 completed on 2 November 2026. This is a PHP-rendered interface prototype, with no backend processing or database dependency.

## Completed

- Converted **99 pages**: 8 public/root, 25 student, 42 tutor, 23 admin and 1 report.
- Updated internal page links and JavaScript navigation/filename checks to `.php`, preserving query strings, fragments and relative asset paths.
- Preserved the approved markup, content, forms, role menus, sample identities, active admin navigation, responsive styles and frontend demonstrations.
- Kept the single `report/report-dashboard.php` with Comparison Report, Composition Report and existing inert export buttons.
- Updated README setup instructions and the interface guide; marked the earlier `docs/codex-handoff.md` as historical context.

## New Structure

- PHP entry pages sit alongside their original HTML references in the root, `student/`, `tutor/`, `admin/` and `report/`.
- `includes/` contains nine presentation components/helpers listed below.
- `.htaccess` sets `DirectoryIndex index.php` for Apache while `index.html` remains present.
- `scripts/audit_php_conversion.py` provides dependency-free conversion validation using PHP CLI and the existing frontend audit parser.
- No database connection file or schema was created. Phase 1 needs neither MySQL nor credentials; `includes/config.php` is only a presentation helper.

Run from the project directory:

```powershell
& C:\xampp\php\php.exe -S 127.0.0.1:8000 -t .
```

Open `http://localhost:8000/index.php`. XAMPP Apache can also serve the project from its document root. Python HTTP servers and editor static servers cannot execute PHP.

## Reusable Components

- `config.php`: UTF-8 HTML escaping helper, loaded with `require_once`.
- `header.php`: document head, title, description, shared CSS and contact script; retains page-specific dashboard CSS and homepage styles.
- `public-navbar.php`: public navigation and mobile menu.
- `student-header.php`: student navigation and profile dropdown.
- `tutor-header.php`: tutor navigation, retaining applicant/verified sample names and destinations via `$tutorApplicant`.
- `admin-header.php`: admin/report topbar and profile dropdown.
- `admin-sidebar.php`: active navigation through `$currentPage`; preserves the existing pages that omit the Settings item.
- `footer.php`: shared public/student/tutor footer, role-specific support links and homepage-specific social links/copyright.
- `admin-footer.php`: existing admin/report footer.

Pages declare metadata and `$assetPrefix` (`''` or `'../'`). Includes use `__DIR__` so filesystem resolution is independent of the server working directory. Page-specific content, body classes and scripts remain in their pages. These presentation variables provide no authentication or authorization.

## Files Not Converted

- All 99 original HTML files remain unchanged in place as references. Their old `.html` links are intentional; active PHP pages and JavaScript contain no `.html` references. Because both versions share assets, the originals are not an isolated runnable backup.
- CSS, SCSS and images are unchanged. Only four JavaScript files needed route-extension changes: `availability.js`, `lesson-selection.js`, `main.js` and `tutor-workflow.js`.
- Existing frontend audit scripts, tests and historical workflow/audit documents remain available.
- Forms retain their existing attributes and demo handlers. No processing endpoints, uploads, sessions, authentication, CRUD, payment handling or export implementation was introduced.
- Older tutor setup/publishing preview pages were also converted and remain reference flows; profile view/edit stay the main workflow.

## Validation

- PHP 8.2.12: **108 PHP files passed syntax checks** (99 pages and 9 includes).
- All 99 pages rendered without PHP errors/warnings. Their parsed markup matches the originals after route-extension conversion, ignoring formatting, comments and one original UTF-8 BOM.
- All 99 pages and 22 referenced local assets returned HTTP 200 under a project subdirectory using PHP's local server. HTTP output also matched CLI output. Apache itself was not started for validation.
- No missing PHP equivalents, broken local links/assets/anchors, obsolete active `.html` routes or new frontend audit findings. Markup parity checks also cover forms, navigation, duplicated components and script loading order.
- All 10 existing booking/availability JavaScript tests passed; every JavaScript file passed `node --check`. `git diff --check` passed.
- Original HTML, CSS, SCSS and image files are unchanged. No browser screenshot comparison was performed; markup parity and unchanged styles provide the interface-preservation check.
- Existing baseline findings were preserved: three homepage social `#` placeholders, an unmatched closing `section` in each student/tutor booking-coordinate page, and missing `community-title` ARIA target on the student dashboard. These are pre-existing frontend cleanup items, not conversion regressions.

Re-run:

```powershell
python -X utf8 scripts/audit_php_conversion.py --php C:/xampp/php/php.exe
node --test tests/booking-availability.test.cjs
```

The PHP audit optionally accepts `--base-url http://localhost:8000/` for a running server. Include any deployment subdirectory in that URL. The original `scripts/audit_frontend.py` continues to audit the retained HTML references.

## Questions / Decisions Needed

None blocking Phase 1; no `php-conversion-questions.md` was needed. Database schema and backend policy decisions remain future-phase work. Browser storage and direct role-page access still behave as interface demonstrations.

## Recommended Next Phase

Confirm the final ERD/schema and role/account rules first. Then implement a small PHP/MySQL foundation with environment-specific connection settings, authentication, sessions, password hashing, CSRF protection and role/ownership checks before adding individual persisted workflows. Keep the approved PHP templates and move through booking, availability, payments and other modules in separate scoped phases.
