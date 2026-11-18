<p align="center">
  <img src="docs/assets/bem-upnvj.png" alt="BEM UPNVJ" width="88">
  &nbsp;&nbsp;&nbsp;
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

SEKATA gives seven fields (*bidang*) and four bureaus (*biro*) one calendar for the BEM secretariat. Units can check availability, submit requests, and send return evidence. Biro SPPI manages scheduling conflicts, records decisions, and verifies the room and key handover.

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
| 1. Check and request | Borrower unit | Check the shared weekly calendar, choose a WIB slot, and submit the activity description and PIC details. The request starts at **Menunggu**. |
| 2. Discuss a conflict | SPPI and affected units | SPPI opens a private forum for overlapping requests, notifies the participants, and sets a response deadline in WIB. |
| 3. Record a decision | SPPI | After all participants respond or the deadline passes, SPPI records the decision and reason. Requests can become **Disetujui**, **Ditolak**, or **Perlu Jadwal Baru**. A revised slot returns to **Menunggu**. |
| 4. Submit return evidence | Borrower unit | After using the room, send a photo of the cleaned room and a photo of the returned key. The booking moves to **Periksa Bukti**. |
| 5. Verify the return | SPPI | Mark the booking **Selesai** or request **Perlu Perbaikan** with a note. The unit can replace the requested photo while keeping the valid one. |

Biro SPPI also uses the room. The planned SPPI booking form checks that the selected slot is empty before recording its use.

### Planned data model

| Entity | Purpose |
| --- | --- |
| `users` | Unit identities and roles. The planned seeder covers 11 units, including one SPPI admin. |
| `rooms` | Secretariat room record, locked during approval to avoid double booking. |
| `bookings` | Requested slots, activity description, PIC, status, and decision or inspection notes. |
| `forums`, `forum_bookings`, `forum_messages` | Conflict discussion, linked requests, deadlines, and messages. |
| `booking_proofs` | Private uploads for the `ruangan` and `kunci` categories, with upload timestamps. |
| `notifications` | In-app notices for forum invitations and status updates. |

## Team and ownership

Each member owns the backend and frontend for their assigned feature. The assignments below follow the current team plan.

| Member | Feature and responsibilities | Branch |
| --- | --- | --- |
| Hafiz Muhammad Shahzaib | **Auth, notifications, and coordination.** Maintain the project scaffold, shared migrations and demo seeders. Build login/logout, role middleware, the shared Blade layout, and notification feed and badge. Maintain the README and milestones, and review and merge PRs as team lead. | `feature/2510512901-auth-notifications-docs` |
| Ignatius Gavrilla Reda | **SPPI decisions.** Build the filtered admin queue, decision form with a reason, and direct booking form for empty slots. Handle approval in a transaction with a room-row lock, then flag overlapping requests for a new slot. Prepare presentation slides. | `feature/2510512020-sppi-decisions` |
| Maulida Nabil Alifah | **Calendar and booking requests.** Build the Monday–Sunday grid, week controls, unit highlighting, and request forms. Validate the slot, description, PIC, and proposed cleanliness agreement. Guard edits and deletions once a request is in an active forum or approved. | `feature/2510512138-calendar-booking` |
| Adis Fawwaz Guspandi | **Conflict forum and deadlines.** Build the private forum, deadline display, and reply form. Notify participants when SPPI opens a forum and enforce the response or deadline requirement for a decision. | `feature/2510512055-clash-forum` |
| Muhammad Raehansyah | **Return evidence.** Build two-photo upload, private storage and authorized viewing, SPPI inspection, and single-photo replacement. Validate file types and sizes and record upload timestamps. | `feature/2510512127-proof-verification` |
| Naila Izzatul Kamilah | **Booking details, rescheduling, and QA.** Build the authorized detail and status history page, show decision and revision notes, and validate replacement slots. Collect evidence for T01–T08 on desktop and at 390 px. | `feature/2510512101-detail-reschedule-qa` |

### Pull requests

1. Create your feature branch using `feature/<NIM>-<feature-name>`.
2. Open a pull request into `main` with a summary and steps to test. Include screenshots for visible changes.
3. Have another team member review it. The team lead merges approved changes into `main`.

<p align="center">
  <sub>BEM UPNVJ · Kabinet Gelora Juang</sub>
</p>
