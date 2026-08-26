# Deploying to Hostinger

The site is plain PHP with no build step and no Composer dependencies, so a
Git pull is the whole deployment.

---

## 1. Create the database

hPanel → **Databases → MySQL Databases**

1. Create a database, e.g. `u123456_sarada`.
2. Create a user and give it **all privileges** on that database.
3. Write down the database name, username and password.

Hostinger prefixes both the database and the username with your account ID.
Use the full prefixed names.

---

## 2. Import the tables

hPanel → **Databases → phpMyAdmin** → select your database → **Import**

Import these two files, in order:

1. `sql/schema.sql` — creates the tables
2. `sql/seed.sql` — adds the two doctors, their weekly sessions and default settings

`seed.sql` deliberately creates **no login account**. No password hash is ever
committed to this repository. You create the first account in step 5.

---

## 3. Point Git at the branch

hPanel → **Advanced → Git**

| Field | Value |
|---|---|
| Repository | `https://github.com/azeem0718/saradahospitals` |
| Branch | `claude/hospital-website-git-access-kynqj7` |
| Install path | `public_html` |

The repository root **is** the web root. `index.php` sits at the top level of
the repo and lands directly in `public_html`. Do not nest the site inside
another `public_html` folder — that produces `public_html/public_html` and a 404.

Use **Auto Deployment** so a push to the branch updates the site.

---

## 4. Add your credentials

`includes/config.php` is git-ignored, so it never reaches GitHub and is never
overwritten by a deployment. Create it once, on the server.

File Manager → `public_html/includes/` → copy `config.example.php` to
`config.php`, then edit:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'u123456_sarada');
define('DB_USER', 'u123456_sarada');
define('DB_PASS', 'the password you set');
define('SITE_URL', 'https://saradahospitals.highflyers.io');
define('DEBUG_MODE', false);   // keep false on the live site
```

`DEBUG_MODE` must stay `false` in production. When true, database errors are
printed to the page.

Until this file exists the site serves a holding page carrying the hospital's
phone numbers, so anyone who visits mid-setup still sees how to reach you. The
setup steps sit behind a collapsed **"Setting up this site?"** toggle on that
page. The same page appears if the credentials are wrong or the tables have not
been imported, and it never prints a credential.

---

## 5. Create the first login

Visit **`https://saradahospitals.highflyers.io/setup.php`** once.

Enter your name, a username and a password of at least 10 characters. The page
creates the administrator account and then **permanently disables itself** —
it refuses to run again once any account exists.

Delete `setup.php` from the server afterwards. It is inert, but there is no
reason to leave it there.

---

## 6. Check it works

| Check | Where |
|---|---|
| Site loads | `/` |
| Booking shows live token counts | `/book.php` |
| Staff login works | `/admin/login.php` |
| Today's queue loads | `/admin/index.php` |
| Sitemap responds | `/sitemap.xml` |

---

## Day-to-day use

**Reception panel:** `https://saradahospitals.highflyers.io/admin/`

| Page | What it does |
|---|---|
| Today | The day's queue by session. Mark patients arrived, completed or no-show. |
| Bookings | Search every booking by phone, name or reference. Filter by status, doctor and date. |
| New Token | Issue a token for a walk-in or phone patient. Ignores the online cutoff. |
| Schedule | Session times and token caps, per doctor, per weekday. |
| Leave | Close a session when a doctor is away, or the whole day for a holiday. |
| Staff | Add or deactivate staff accounts. Admin only. |
| Settings | Booking window, cutoff, free-OP day, and the site-wide announcement. Admin only. |

**Changing OP timings** — Schedule, pick the doctor, edit the times, Save.
Defaults are Morning 9:00 AM–1:00 PM and Evening 5:00 PM–9:00 PM.

**Closing online booking temporarily** — Settings, untick "Online booking is
open". Patients are then asked to call. Reception can still issue tokens.

**Putting a notice on the site** — Settings, fill in "Website announcement".
It shows as a yellow banner on every page. Clear the field to remove it.

---

## Adding photographs

Drop JPG, PNG or WebP files into `assets/img/gallery/`. They appear on the
gallery page automatically — no code change needed. Name each file after what
it shows (`reception.jpg`, `icu.jpg`), because the filename becomes the alt
text for screen readers.

Doctor photographs: add the file to `assets/img/`, then set the `photo` column
for that doctor in the `doctors` table to the filename. Until then a neutral
illustrated avatar is shown.

---

## Changing hospital details

Address, phone numbers, fees, room charges and the service lists all live in
one file: **`includes/site.php`**. Edit it there and every page updates.

Everything in that file is taken from the hospital's own signage and brochure.
If you add a claim, make sure it is one the hospital can stand behind.

---

## If something breaks

**"Site not configured"** — `includes/config.php` is missing. See step 4.

**"The site is temporarily unavailable"** — the database credentials are wrong,
or the database is down. Recheck them in hPanel.

**Blank page** — set `DEBUG_MODE` to `true` briefly, reload to read the error,
then set it back to `false`.

**"Your session expired" on every form** — the server is not keeping PHP
sessions. Check that the session save path is writable in hPanel.

**Booking says "Too many booking attempts"** — the per-IP rate limit
(15 bookings per hour). Clear it with:
`DELETE FROM booking_attempts;`

---

## Backups

The bookings table holds patient names and phone numbers. Take regular backups
and keep them somewhere private.

hPanel → **Files → Backups** covers the files. For the database, export it from
phpMyAdmin periodically, or enable Hostinger's automatic database backups.
