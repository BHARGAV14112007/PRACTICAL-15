# Student Hub — College Portal (Practicals 1–15, Integrated)

StudentHub is a campus portal built across B.Tech Semester 3 practicals: static
pages and client-side validation (1–6), PHP form processing with file storage
(7), MySQL schema + PDO (8), MySQLi registration (9), sessions/roles (10),
student CRUD (11), event CRUD with poster uploads (12), JSON API + Fetch UI
(13), admin dashboard with audit log (14), and final integration (15).

## Prerequisites

- XAMPP with **Apache** and **MySQL** (PHP 8.x, MariaDB/MySQL 10.4+)
- A browser (Chrome/Edge/Firefox)
- Optional: Git for version control

## Folder Structure

```
student hub 15/
├── config/
│   └── db.php                # Single DB config (PDO + MySQLi, env-overridable)
├── css/style.css             # Design system Morris
├── data/                     # JSON datasets (events, students, faqs, contacts)
├── database/
│   ├── studenthub.sql        # Full setup: DB, tables, constraints, sample data
│   ├── migration_p10_roles.sql
│   ├── migration_p12_posters.sql
│   ├── migration_p14_audit.sql
│   └── seed_students_p11.sql # 16 extra students (idempotent INSERT IGNORE)
├── image/                    # Static images / wireframes
├── js/                       # header, validation (script.js), auth gate,
│                             # dashboard/event/faq renderers, events-api.js
├── pages/                    # Static HTML pages (home, login, dashboards, …)
├── php/                      # Server pages: forms, processors, guards,
│                             # api_events.php, admin_dashboard.php, …
├── uploads/
│   ├── .htaccess             # Blocks script execution in uploads
│   └── posters/              # Event poster images (auto-created, writable)
├── style.css                 # Dashboard styles (Practical 6)
└── README.md / .gitignore
```

## Installation (XAMPP)

1. Copy this folder to `C:\xampp\htdocs\studenthub`.
2. Start **Apache** and **MySQL** in XAMPP Control Panel.
3. Import the database (see below).
4. Open `http://localhost/studenthub/php/login.php`.

No virtual host is required. For a vhost instead, point a
`<VirtualHost *:80>` `DocumentRoot` at the project folder, add
`127.0.0.1 studenthub.local` to `C:\Windows\System32\drivers\etc\hosts`,
and open `http://studenthub.local/php/login.php`.

## Database Import (phpMyAdmin)

1. Open `http://localhost/phpmyadmin` → **Import** → choose file.
2. For a **fresh** database import only `database/studenthub.sql`
   (creates `studenthub` with all tables, constraints, sample data, admin).
3. For an **existing Practical 8/9** database, import in order:
   `migration_p10_roles.sql` → `seed_students_p11.sql` →
   `migration_p12_posters.sql` → `migration_p14_audit.sql`.
4. `uploads/posters/` must be writable by Apache (default on XAMPP/Windows).

## Configuration

`config/db.php` defaults to XAMPP MySQL (`127.0.0.1:3306`, `studenthub`,
`root`, empty password). Override without editing code via environment
variables: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
Credentials are never printed; failures return generic messages and log
server-side only.

## Test Accounts (passwords are bcrypt-hashed in the DB)

| Role    | Email                      | Password  | Lands on              |
|---------|----------------------------|-----------|-----------------------|
| Admin   | admin@studenthub.edu       | Admin123  | Admin Dashboard       |
| Student | aarav.sharma@college.edu   | Secret123 | Student Dashboard     |

Key URLs (under `http://localhost/studenthub/`): `php/login.php`,
`php/register_mysql.php`, `php/students.php` (login; admin edits),
`php/events.php` (admin), `php/events_live.php` (Fetch demo),
`php/admin_dashboard.php` (admin), `php/api_events.php?action=list`,
`php/db_test.php`.

## Testing Procedure (verified for this release)

- Registration: valid insert; invalid email/mobile rejected; duplicate
  email rejected; stored value is a bcrypt hash (`password_verify` passes).
- Login: correct credentials per role with correct redirects; wrong
  password and unknown email give the identical generic message; session ID
  regenerates; logout kills the session; expired sessions redirect to login.
- Dashboards: unauthenticated → login; student → admin pages denied;
  role menus hide admin options from students.
- Students module: search/filter/pagination; add/edit/delete as admin;
  duplicate/invalid blocked; delete with registrations blocked; student-role
  writes denied.
- Events module: CRUD as admin; JPEG/PNG/WebP ≤ 2 MB accepted; `.php`,
  text, and oversized files rejected; poster replaced/removed cleanly.
- API: list/get public; writes require admin + CSRF; 201/400/401/403/404/
  405/409/422/503 verified; malformed JSON handled.
- Dashboard: live counts, latest registrations, audit rows after successful
  changes only (failed operations log nothing).

## Known Limitations

- `pages/dashboard.html` sidebar links to `resources.html`, `messages.html`,
  and `settings.html`, which do not exist (legacy static links).
- `php/api_events.php` manages event data only; poster uploads stay on
  `php/event_form.php`.
- No Remember-Me: intentionally omitted (would require token storage).
- Session timeout is 15 minutes of inactivity, not configurable via UI.
- `data/*.json` demo datasets and `localStorage` state from early practicals
  coexist with MySQL; the database is authoritative for accounts/events.
