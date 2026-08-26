# Sarada Nursing Home

Website and OP token-booking system for **Sarada Nursing Home**, Kandukur,
Prakasam District, Andhra Pradesh.

Live at **https://saradahospitals.com**

---

## What it does

**For patients** — a public website covering the hospital, its two consultants,
services, facilities and published tariff, plus online booking of an outpatient
token.

**For reception** — a password-protected panel for running the day: the queue
by session, marking patients arrived or completed, issuing walk-in tokens,
editing consultation timings, and recording doctor leave.

Booking uses a **token model** rather than clock appointments: a patient picks a
doctor, a date and a session (morning or evening) and receives the next token
number in that session's queue — which is how the nursing home already works at
the desk.

---

## Stack

PHP 8 and MySQL, with vanilla CSS and JavaScript. **No build step and no
dependencies** — Hostinger pulls the branch and the site runs.

```
├── index.php  about.php  doctors.php  services.php  …   public pages
├── book.php   availability.php  booking.php             booking flow
├── setup.php                                            one-time first-run setup
├── admin/                                               reception panel
├── includes/
│   ├── config.php          credentials — git-ignored, created on the server
│   ├── config.example.php  template for the above
│   ├── site.php            hospital details, fees, service lists
│   ├── db.php  functions.php  auth.php  booking.php     application code
│   ├── header.php  footer.php  components.php  icons.php
├── assets/  css, js, fonts, images
├── sql/     schema.sql, seed.sql
└── docs/    DEPLOY.md
```

---

## Setting it up

Full instructions, including Hostinger specifics, are in
**[docs/DEPLOY.md](docs/DEPLOY.md)**. In short:

1. Create a MySQL database and user in hPanel.
2. Point hPanel → Git at this branch, install path `public_html`.
   The repository root is the web root.
3. Open `/setup.php` and follow it. Three steps — database credentials, tables,
   your login — and it writes `includes/config.php` for you. Delete `setup.php`
   when it says so.

`setup.php` switches itself off permanently the moment an account exists, so
complete it promptly after deploying. No credential ever needs to be committed:
`config.php` is git-ignored, and nothing in this repository contains a password
or a password hash.

---

## Where things live

| To change | Edit |
|---|---|
| Address, phone numbers, fees, service lists | `includes/site.php` |
| Consultation timings and token caps | Admin → Schedule |
| Booking window, cutoff, free-OP day, site notice | Admin → Settings |
| Doctor leave and hospital closures | Admin → Leave |
| Photographs | drop files into `assets/img/gallery/` |
| Colours, type, spacing | the token block at the top of `assets/css/style.css` |

Everything on the site is drawn from the hospital's own signage, letterhead and
brochure. No claim about the hospital was invented — if a fact is not on those
materials, it is not on the site.

---

## Security

Patient names and phone numbers pass through this application, so:

- All SQL uses prepared statements via PDO, with emulation off.
- Every state-changing form carries a CSRF token, checked with `hash_equals`.
- Passwords are stored with `password_hash`, and rehashed on login when PHP's
  default cost changes.
- Admin sessions are cookie-hardened, user-agent pinned and idle-timed.
- Public booking is rate-limited per IP and carries a honeypot field.
- Credentials live only in `includes/config.php`, which is git-ignored. No
  password or hash is committed to this repository — `setup.php` creates the
  first account interactively and then disables itself.
- `.htaccess` denies direct access to `includes/`, `sql/` and `docs/`, and sets
  the usual security headers.

Set `DEBUG_MODE` to `false` in production. Take regular database backups and
keep them private.

---

## Local development

Requires PHP 8.1+ and MySQL or MariaDB.

```bash
mysql -e "CREATE DATABASE sarada CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql sarada < sql/schema.sql
mysql sarada < sql/seed.sql

cp includes/config.example.php includes/config.php   # then fill in credentials
php -S localhost:8080 -t .
```

Visit `/setup.php` to create an admin account.
