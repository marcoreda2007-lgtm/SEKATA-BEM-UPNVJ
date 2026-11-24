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
  <a href="#team-and-ownership">Team</a> ·
  <a href="https://www.figma.com/design/P16PbuVMeKvhVbPXJ4Aqs9">Figma prototype</a>
</p>

SEKATA gives seven fields (*bidang*) and four bureaus (*biro*) one calendar for the BEM secretariat. Units can check availability, submit requests on open slots, and send return evidence. Biro SPPI reviews requests, records decisions with reasons, and verifies the room and key handover.

**Development status.** The Laravel scaffold, starter migrations, and sample user seeder are in the repository. The booking features, SPPI access, and 11 demo accounts described below are implementation targets. The Figma prototype illustrates the workflow with sample data.

## Tech stack

![SEKATA tech stack: PHP, Laravel 12, Bootstrap 5, JavaScript, MySQL, Composer, and Vite 7](docs/assets/tech-stack.svg)

| Layer | Technology | Role |
| --- | --- | --- |
| Backend | PHP, Laravel 12 | Routing, sessions, validation, and application logic |
| Frontend | Bootstrap 5, JavaScript | Blade views, Bootstrap 5, custom CSS, and vanilla JavaScript |
| Database | MySQL | Local MySQL or MariaDB with InnoDB |
| Build | Composer, Vite 7 | PHP dependencies and frontend assets |

Bootstrap is the target for SEKATA’s screens. The current scaffold still bundles Tailwind CSS through Vite.

## Local setup

**Requirements.** PHP 8.2 or newer as allowed by `composer.json`, Composer, Git, MySQL or MariaDB, and [Node.js compatible with Vite 7](https://v7.vite.dev/guide/#scaffolding-your-first-vite-project) with npm. Enable the `pdo_mysql` PHP extension.

1. Clone the repository and install dependencies from its root, where `artisan` is located.

   ```powershell
   git clone [https://github.com/marcoreda2007-lgtm/SEKATA-BEM-UPNVJ.git](https://github.com/marcoreda2007-lgtm/SEKATA-BEM-UPNVJ.git)
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

4. Build the assets, run migrations, and start the app.

   ```powershell
   php artisan migrate
   npm.cmd run build
   php artisan serve
   ```

   Open [http://127.0.0.1:8000](http://127.0.0.1:8000). For live frontend updates, run `npm.cmd run dev` in a second terminal instead of rebuilding after every edit.

The commands above use PowerShell on Windows. On macOS or Linux, use `cp .env.example .env` and `npm` in place of `Copy-Item` and `npm.cmd`.

### After pulling changes

Pull the merged changes from `main` and rerun `php artisan migrate` when the pull includes migrations. Run `composer install` or `npm.cmd install` when the respective dependency files change, then rebuild the frontend assets if needed. Your local database name stays in `.env`.

## Booking workflow

| Step | Who | What happens |
| --- | --- | --- |
| 1. Check and request | Borrower unit | Check the shared weekly calendar, choose an open WIB slot, and submit the activity description and PIC details. The request starts at **Menunggu** and immediately holds the slot against overlapping requests (adjacent time slots are allowed). |
| 2. Record a decision | SPPI | SPPI reviews pending requests and records a decision with a mandatory reason. Requests become **Disetujui** or **Ditolak** (rejection reopens the slot for new requests). SPPI can also record direct usage on empty slots. |
| 3. Submit return evidence | Borrower unit | After using the room, send two private photos (cleaned room and returned key). The booking moves to **Periksa Bukti**. |
| 4. Verify the return | SPPI | Mark the booking **Selesai** or request **Perlu Perbaikan** with a note. The unit can replace the requested photo while keeping the valid one. |

### Planned data model

| Entity | Purpose |
| --- | --- |
| `users` | Unit identities (`unit_name`, `unit_type`) and roles (`admin`/`unit`). The planned seeder covers 11 units, including one SPPI admin. |
| `rooms` | Secretariat room record, locked in database transactions during submission and approval to prevent overlapping bookings. |
| `bookings` | Requested slots, activity description, PIC, status (`Menunggu`, `Disetujui`, `Ditolak`, `Periksa Bukti`, `Perlu Perbaikan`, `Selesai`), and decision or inspection notes. |
| `booking_proofs` | Private uploads for the `ruangan` and `kunci` categories, with server-recorded upload timestamps. |
| `notifications` | In-app notices for new requests (to SPPI) and status decisions/inspections (to request owners). |

## Team and ownership

Each member owns the backend and frontend for their assigned feature. The assignments below follow the revised team plan.

| Member | Feature and responsibilities | Branch |
| --- | --- | --- |
| Hafiz Muhammad Shahzaib (`2510512901`) | **Auth, shared foundations, and coordination.** Agree on shared `users`/`rooms`/`bookings` migrations and seeders (11 units). Build session login/logout, role middleware, and the shared Bootstrap 5 Blade layout. Maintain the README, coordinate milestones, and review and merge PRs as team lead. | `feature/2510512901-auth-foundations` |
| Ignatius Gavrilla Reda (`2510512020`) | **SPPI decisions.** Build the filtered admin queue, approve/reject decision form with a mandatory reason, and direct SPPI booking for empty slots. Handle decisions in a transaction with a room-row lock and trigger status notifications. Prepare presentation slides. | `feature/2510512020-sppi-decisions` |
| Maulida Nabil Alifah (`2510512138`) | **Calendar and booking requests.** Build the Monday–Sunday grid, week controls, unit highlighting, and request forms. Lock the room row in a transaction to reject overlapping `Menunggu`/`Disetujui` slots (allowing adjacent slots), validate description and PIC, and trigger SPPI notifications. | `feature/2510512138-calendar-booking` |
| Adis Fawwaz Guspandi (`2510512055`) | **Status notifications.** Build the notification service contract, navbar unread badge, notification list, authorized detail links, and mark-as-read state (`PATCH /notifikasi/{id}`). Ensure notifications only fire after successful transaction commits. | `feature/2510512055-status-notifications` |
| Muhammad Raehansyah (`2510512127`) | **Return evidence.** Build two-photo upload, private storage and authorized viewing (`GET /peminjaman/{id}/bukti/{kategori}`), SPPI inspection, and single-photo replacement. Validate file types and sizes, record server timestamps, and trigger inspection notifications. | `feature/2510512127-proof-verification` |
| Naila Izzatul Kamilah (`2510512101`) | **Booking details, status history, and QA.** Coordinate status history tracking, build the owner/admin detail and history page (`GET /peminjaman/{id}`) showing decision and revision notes, and compile T01–T08 test evidence on desktop and at 390 px. | `feature/2510512101-detail-history-qa` |

### Pull requests

1. Create your feature branch using `feature/<NIM>-<feature-name>`.
2. Open a pull request into `main` with a summary and steps to test. Include screenshots for visible changes.
3. Have another team member review it. The team lead merges approved changes into `main`.

<p align="center">
  <sub>BEM UPNVJ · Kabinet Gelora Juang</sub>
</p>
