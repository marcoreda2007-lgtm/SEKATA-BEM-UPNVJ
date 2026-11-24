<p align="center">
  <img src="docs/assets/bem-upnvj.png" alt="BEM UPNVJ" width="88">
     
  <img src="docs/assets/gelora-juang.png" alt="Kabinet Gelora Juang" height="88">
</p>

<h1 align="center">SEKATA</h1>

<p align="center">
  <strong>Sekretariat Kita Tertata</strong><br>
  <em>Satu jadwal, satu kesepakatan.</em>
</p>

<p align="center">
  A shared secretariat booking system for BEM UPNVJ.<br>
  Web Programming Final Project · Information Systems · UPN “Veteran” Jakarta
</p>

<p align="center">
  <a href="#local-setup">Local setup</a> ·
  <a href="#booking-workflow">Booking workflow</a> ·
  <a href="#team-and-ownership">Team</a>
</p>

SEKATA gives seven fields (*bidang*) and four bureaus (*biro*) one calendar for the BEM secretariat. Units can check availability, submit requests on open slots, and send return evidence. Biro SPPI reviews requests, records decisions with reasons, and verifies the room and key handover.

## Development status

Status as of **25 November 2026**. Features under development are not yet available in `main`.

| Feature | Status |
| --- | --- |
| Authentication and shared foundations | Available in `main`: login/logout, role middleware, account/room/booking schema, initial accounts, and shared Bootstrap layout. |
| SPPI queue | Available in `main`: database-backed pending queue with admin-only access. |
| SPPI decisions | In development: approval/rejection with reasons and direct SPPI bookings. |
| Calendar and requests | In development: weekly calendar, request submission, and slot availability checks. |
| Details and history | Under review: owner/admin detail page. Complete status history remains pending. |
| Return evidence | In development: private two-photo upload, replacement, and SPPI inspection. |
| Notifications | In development: recipient-specific notices, unread badge, list, and status triggers. |

The full booking and return workflow is not yet ready for operational use.

## Tech stack

![SEKATA tech stack: PHP, Laravel 12, Bootstrap 5, JavaScript, MySQL, Composer, and Vite 7](docs/assets/tech-stack.svg)

| Layer | Technology | Role |
| --- | --- | --- |
| Backend | PHP, Laravel 12 | Routing, sessions, validation, and application logic |
| Frontend | Bootstrap 5, JavaScript | Blade views, Bootstrap 5, custom CSS, and vanilla JavaScript |
| Database | MySQL | Local MySQL or MariaDB with InnoDB |
| Build | Composer, Vite 7 | PHP dependencies and frontend assets |

The shared screens use Bootstrap 5 from a CDN and external CSS in `public/css`. Internet access is required for Bootstrap and Google Fonts.

## Local setup

**Requirements.** PHP 8.2 or newer as allowed by `composer.json`, Composer, Git, MySQL or MariaDB, and [Node.js compatible with Vite 7](https://v7.vite.dev/guide/#scaffolding-your-first-vite-project) with npm. Enable the `pdo_mysql` PHP extension.

1. Clone the repository and install dependencies from its root, where `artisan` is located.

   ```powershell
   git clone https://github.com/marcoreda2007-lgtm/SEKATA-BEM-UPNVJ.git
   cd SEKATA-BEM-UPNVJ
   composer install
   npm.cmd install
   ```

2. Create `.env` on the first setup and generate the application key. Keep an existing `.env` when updating a working copy.

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

3. Start MySQL or MariaDB. If you use XAMPP, start **MySQL** from its control panel. Create an empty database in phpMyAdmin and enter its connection details in `.env`.

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sekata
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   `sekata` is an example. **Each member can use a different local database name.** Set `DB_DATABASE` to the database you created and update the port and credentials for your installation. Commit migrations and `.env.example` so the team shares the same schema and setup template. Keep `.env`, local databases, and credentials out of Git.

4. Run migrations and seed the initial room and accounts, then build assets and start the app.

   ```powershell
   php artisan migrate --seed
   npm.cmd run build
   php artisan serve
   ```

   Open [http://127.0.0.1:8000](http://127.0.0.1:8000). Keep the development server running. For Vite-managed assets, run `npm.cmd run dev` in a second terminal. Edits to CSS in `public/css` only need a browser refresh; use Ctrl+F5 if cached styles persist.

### Development accounts

The seeder creates one room and 11 accounts. Examples:

| Account | Email | Role |
| --- | --- | --- |
| Biro SPPI | `sppi@sekata.test` | Admin |
| Ekonomi Kreatif | `ekraf@sekata.test` | Borrower unit |

The default seed password is `password`. To override it, set `SEED_USER_PASSWORD` in your local `.env` before seeding. These are development credentials, not credentials for a BEM pilot. Rerunning the seeder updates the seeded accounts, including their passwords; do not run it casually against operational data.

The commands above use PowerShell on Windows. On macOS or Linux, use `cp .env.example .env` and `npm` in place of `Copy-Item` and `npm.cmd`.

### After pulling changes

Protect local changes first. Fetch and integrate `origin/main` into your own feature branch, resolving conflicts without restoring obsolete routes or shared files. Run `php artisan migrate` when the update includes migrations. Run `composer install` or `npm.cmd install` when the respective dependency files change, then rebuild Vite-managed assets if needed. Your local database name stays in `.env`. Do not use `migrate:fresh` for routine updates: it drops existing tables and their data.

## Booking workflow

This is the agreed target workflow. The development-status table above records which parts are implemented or still pending.

| Step | Who | What happens |
| --- | --- | --- |
| 1. Check and request | Borrower unit | Check the shared weekly calendar, choose an open WIB slot, and submit the activity description and PIC details. The request starts at **Menunggu** and immediately holds the slot against overlapping requests (adjacent time slots are allowed). |
| 2. Record a decision | SPPI | SPPI reviews pending requests and records a decision with a mandatory reason. Requests become **Disetujui** or **Ditolak** (rejection reopens the slot for new requests). SPPI can also record direct usage on empty slots. |
| 3. Submit return evidence | Borrower unit | After using the room, send two private photos (cleaned room and returned key). The booking moves to **Periksa Bukti**. |
| 4. Verify the return | SPPI | Mark the booking **Selesai** or request **Perlu Perbaikan** with a note. The unit can replace the requested photo while keeping the valid one. |

Pending requests do not expire automatically under the current plan. SPPI must process them to release rejected slots. Cancellation policy, operational sanctions, and acceptable photo timestamps still require agreement with SPPI before a pilot.

### Data model and implementation status

| Entity | Purpose |
| --- | --- |
| `users` | In `main`: unit identities (`unit_name`, `unit_type`) and roles (`admin`/`unit`). The seeder creates 10 borrower accounts plus one SPPI admin. |
| `rooms` | In `main`: the shared secretariat room record. |
| `bookings` | In `main`: `tanggal`, `start_time`, `end_time`, activity, PIC, status (`Menunggu`, `Disetujui`, `Ditolak`, `Periksa Bukti`, `Perlu Perbaikan`, `Selesai`), and decision/inspection fields. No complete status-transition history table is present. |
| `booking_proofs` | In development: private `ruangan` and `kunci` uploads. Upload time is distinct from the photo capture timestamp checked by SPPI. |
| `notifications` | In development: recipient-specific notices linked to bookings, with read/unread state. |

## Team and ownership

Each member owns the backend and frontend for their assigned feature. The assignments below follow the revised team plan.

| Member | Feature and responsibilities | Branch |
| --- | --- | --- |
| Hafiz Muhammad Shahzaib (`2510512901`) | **Auth and shared foundations.** Maintain shared `users`/`rooms`/`bookings` migrations and seeders (11 units), session login/logout, role middleware, and the shared Bootstrap 5 Blade layout. Verify installation and coordinate shared schema contracts. | `feature/2510512901-auth-foundations` |
| Ignatius Gavrilla Reda (`2510512020`) | **SPPI decisions and integration review.** Build the filtered admin queue, approve/reject decision form with a mandatory reason, and direct SPPI booking for empty slots. Handle decisions in a transaction with a room-row lock and trigger status notifications. Review member PRs, merge approved work into `main`, maintain progress documentation, and prepare presentation slides. | `feature/2510512020-sppi-decisions` |
| Maulida Nabil Alifah (`2510512138`) | **Calendar and booking requests.** Build the Monday–Sunday grid, week controls, unit highlighting, and request forms. Lock the room row in a transaction to reject overlapping `Menunggu`/`Disetujui` slots (allowing adjacent slots), validate description and PIC, and trigger SPPI notifications. | `feature/2510512138-calendar-booking` |
| Adis Fawwaz Guspandi (`2510512055`) | **Status notifications.** Build the notification service contract, navbar unread badge, notification list, authorized detail links, and mark-as-read state (`PATCH /notifikasi/{id}`). Persist database notifications consistently with successful status transitions, so rollbacks leave no false notices. | `feature/2510512055-status-notifications` |
| Muhammad Raehansyah (`2510512127`) | **Return evidence.** Build two-photo upload, private storage and authorized viewing (`GET /peminjaman/{id}/bukti/{kategori}`), SPPI inspection, and single-photo replacement. Validate file types and sizes, record server timestamps, and trigger inspection notifications. | `feature/2510512127-proof-verification` |
| Naila Izzatul Kamilah (`2510512101`) | **Booking details, status history, and QA.** Coordinate status history tracking, build the owner/admin detail and history page (`GET /peminjaman/{id}`) showing decision and revision notes, and compile T01–T08 test evidence on desktop and at 390 px. | `feature/2510512101-detail-history-qa` |

### Pull requests

1. Work on `feature/<NIM>-<feature-name>`. Protect local work and integrate the latest `origin/main` before requesting review.
2. Test the submitted scope, inspect desktop/mobile UI where relevant, and remove replaced or duplicate code. Preserve other members' routes and shared components.
3. Push your feature branch and open a PR targeting `main`. Describe the change, actual test results, screenshots for visible changes, and remaining dependencies. Use a Draft PR while required checks or dependencies are unresolved.
4. **Reda reviews and merges approved PRs.** Members address review feedback on their own branches; new pushes update the same PR. Do not push directly to `main` or force-push shared branches.

<p align="center">
  <sub>BEM UPNVJ · Kabinet Gelora Juang</sub>
</p>
