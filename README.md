<h1 align="center">TutorMeQuran</h1>

<p align="center">
  <img src="assets/img/TutorMeQuran_LOGOnTITLE_WhiteStroke.png" alt="TutorMeQuran logo" width="240">
</p>

<p align="center">
  A Quran tutor booking platform for learners in Malaysia.<br>
  Discover qualified tutors, arrange online or physical lessons, and follow bookings through class completion and payment verification.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/HTML5-E34F26?style=flat-square&amp;logo=html5&amp;logoColor=white" alt="HTML5">
  <img src="https://img.shields.io/badge/CSS3-1572B6?style=flat-square&amp;logo=css&amp;logoColor=white" alt="CSS3">
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=flat-square&amp;logo=javascript&amp;logoColor=black" alt="JavaScript">
  <img src="https://img.shields.io/badge/Status-PHP%20Application-0D9488?style=flat-square" alt="Status: PHP application">
  <img src="https://img.shields.io/badge/UiTM-CSP600/CSP650-6D28D9?style=flat-square" alt="UiTM CSP600 project">
</p>

----

![landing page](assets/img/readmebanner.png)

## About the project

TutorMeQuran is a CSP600 academic project at Universiti Teknologi MARA (UiTM). It brings tutor discovery, certificate review, lesson booking and payment tracking into one interface for students, tutors and administrators.

The application is a PHP and MySQL Quran tutor booking platform. It includes account registration and authentication, tutor applications and verification, booking coordination, chat, support requests, payment receipt submission and review, FAQs, feedback and reporting. The database schema and setup scripts are in `database/`; connection and upload helpers are in `config/`.

## Features

| Area | Included screens and interactions |
| --- | --- |
| Students | Tutor search, registration, profile editing, bookings, invoices, receipt upload, chat and feedback |
| Tutors | Applications and certificate status, profile and availability, booking decisions, DuitNow QR management and payment acknowledgement |
| Administrators | Tutor verification, student and tutor management, bookings, payment review and FAQ management |
| Reports and support | Comparison and composition reports, Help Centre, support requests and FAQs |
| Usability | Shared progress indicators, grouped account menus, destructive-action confirmations, keyboard navigation and reduced-motion support |

## Booking journey

1. **Request a lesson** — the student chooses a tutor and an available slot.
2. **Accept and arrange** — the tutor accepts or rejects the request; participants agree on meeting details.
3. **Attend the class** — lessons take place online through an external service or at an agreed physical venue.
4. **Confirm completion** — the student and tutor each confirm that the lesson took place.
5. **Pay with DuitNow** — the student uses the tutor's QR and uploads the transfer receipt.
6. **Confirm and verify** — the tutor acknowledges the funds received, then admin reviews the payment to close the booking.

Tutor acknowledgement and admin verification are separate stages. DuitNow transfers take place outside the website; the prototype does not process money or verify bank transactions automatically.

## Setup and run locally

Requirements: PHP with MySQLi and the MySQLi, file upload and session features enabled, plus MySQL or MariaDB. Create a database and import `database/tutormequran.sql`. Configure the database connection in `config/database.php`; review `config/uploads.php` and ensure the configured upload directory is writable by PHP. Then serve the project with PHP (XAMPP PHP is suitable):

```powershell
cd C:\Users\aster\Documents\GitHub\tutormequran-web-application-php
& C:\xampp\php\php.exe -S 127.0.0.1:8000 -t .
```

Open [localhost:8000/index.php](http://localhost:8000/index.php). A static Python/Live Server cannot execute PHP. Alternatively serve the project with XAMPP Apache under its document root; `.htaccess` selects `index.php` as the directory entry point. For admin account setup, see `scripts/create_admin.php` and its usage instructions before running it.

Use the same browser tab when checking related student and tutor booking screens: selected prototype actions share `sessionStorage` in that tab. They do not sync between users, tabs or devices. A local HTTP server provides more consistent browser storage behaviour than opening files directly.

## Useful starting pages

| Page | File |
| --- | --- |
| Home | [index.php](index.php) |
| Browse tutors | [tutors.php](tutors.php) |
| Registration | [register.php](register.php) |
| Student dashboard | [student/student-dashboard.php](student/student-dashboard.php) |
| Tutor application status | [tutor/tutor-dashboard.php](tutor/tutor-dashboard.php) |
| Verified tutor dashboard | [tutor/tutor-dashboard-verified.php](tutor/tutor-dashboard-verified.php) |
| Admin dashboard | [admin/admin-dashboard.php](admin/admin-dashboard.php) |
| Reports | [report/report-dashboard.php](report/report-dashboard.php) |

## Repository structure

```text
tutormequran-web-application-php/
├── includes/        Shared PHP head, role navigation, footers and helpers
├── assets/
│   ├── css/          Shared styles
│   ├── img/          Logos, screenshots and other images
│   └── js/           Interface interactions and local workflow state
├── student/          Student pages
├── tutor/            Tutor pages
├── admin/            Administration pages
├── report/           Reporting dashboard
├── docs/             Workflow and implementation notes
├── index.php        Public landing page
├── register.php     Student registration and tutor application
└── INTERFACE-GUIDE.md
```

## Implementation status

The interface includes local booking decisions, class-completion confirmations, receipt handling and tutor payment acknowledgement. Chat and support requests do not send messages to another user or a server. Profile saves and other static controls still require backend implementation.

The PHP phase will need authentication, role and record ownership checks, database persistence, booking-slot protection, server-side validation, file storage, messaging and admin verification. Client-side confirmations and browser storage are interface behaviour, not access controls.

Extra pages preserve the earlier tutor setup and publishing flow for reference. The main registration and application dashboard use **Apply → Admin verification**, with teaching details and visibility managed through Edit Profile.

## Phase 1 conversion checks

```powershell
python -X utf8 scripts/audit_php_conversion.py --php C:/xampp/php/php.exe
node --test tests/booking-availability.test.cjs
```

All 99 original HTML pages remain in place as frontend references. Active links and JavaScript use PHP routes; the HTML copies share those updated scripts and are not an isolated runnable backup. The existing `scripts/audit_frontend.py` still audits the HTML references. The PHP audit renders active pages, compares their markup with the references, and checks links, assets and frontend regressions.

## Project documentation

- [PHP conversion handoff](docs/php-conversion-handoff.md)
- [Interface guide and page map](INTERFACE-GUIDE.md)
- [Tutor booking and payment workflow](docs/tutor-workflow.md)
- [Booking progress, action confirmations and PHP migration](docs/booking-progress-and-actions.md)
- [Student navigation and proposal scope](docs/student-navigation-scope.md)

## Author

[@KuDanial](https://github.com/KuDanial)  
CSP600 academic project
