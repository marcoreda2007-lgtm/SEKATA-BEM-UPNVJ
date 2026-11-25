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
  Secretariat booking and item deposits for BEM UPNVJ.<br>
  Web Programming Final Project - Information Systems - UPN “Veteran” Jakarta
</p>

<p align="center">
  <a href="#local-setup">Local setup</a> -
  <a href="#booking-workflow">Booking workflow</a> -
  <a href="#item-deposits">Item deposits</a> -
  <a href="#team-and-ownership">Team</a>
</p>

SEKATA supports secretariat bookings and item deposits for seven fields (*bidang*) and four bureaus (*biro*) of BEM UPNVJ. Biro SPPI reviews booking requests and return evidence, while units record and collect their own deposited items. The shared calendar and request-submission flow remain under development.

## Development status

Status in `main` as of 25 November 2026:

| Feature | Status |
| --- | --- |
| Authentication and role-based access | Available for 10 borrower accounts and one SPPI admin. |
| SPPI queue and decisions | Pending queue, approval/rejection with a required reason, admin identity, and decision time. |
| Booking list and details | SPPI sees all bookings; borrowers see their own. All statuses remain accessible through the booking list. This is not a complete status-transition log. |
| Return evidence | Two private photos, SPPI inspection, replacement of the requested photo, and completion. |
| Notifications | Recipient-only list, unread badge, mark as read, and notices from booking decisions and proof inspections. The new-request service awaits the submission flow. |
| Item deposits | Independent of room bookings; private photos, owner-reported collection, and removal from the owner's list while SPPI retains access. Deposit notifications are not implemented. |
| Shared layout | Bootstrap layout, green notification-bell button, collapsible desktop sidebar, and mobile drawer. |
| Calendar and request submission | Pending integration. `/jadwal` currently shows the shared layout placeholder. Direct SPPI booking and concurrent-submission verification remain pending. |

### Development accounts

The seeder creates one room and 11 development accounts. Each seeded account uses `<email-prefix>123` as its default password:

| Account | Email | Password | Role |
| --- | --- | --- | --- |
| Biro SPPI | `sppi@sekata.test` | `sppi123` | Admin |
| Bidang Minat dan Bakat | `minatbakat@sekata.test` | `minatbakat123` | Borrower unit |
| Bidang Sosial dan Politik | `sospol@sekata.test` | `sospol123` | Borrower unit |
| Bidang Kemahasiswaan | `kemahasiswaan@sekata.test` | `kemahasiswaan123` | Borrower unit |
| Bidang PPPM | `pppm@sekata.test` | `pppm123` | Borrower unit |
| Bidang Lingkungan Masyarakat | `lingmas@sekata.test` | `lingmas123` | Borrower unit |
| Bidang Pengkeima | `pengkeima@sekata.test` | `pengkeima123` | Borrower unit |
| Bidang Ekraf | `ekraf@sekata.test` | `ekraf123` | Borrower unit |
| Biro Kesekretariatan | `kesekretariatan@sekata.test` | `kesekretariatan123` | Borrower unit |
| Biro Kebendaharaan | `kebendaharaan@sekata.test` | `kebendaharaan123` | Borrower unit |
| Biro Medkominfo | `medkominfo@sekata.test` | `medkominfo123` | Borrower unit |

To override all seeded passwords at once, set `SEED_USER_PASSWORD` in your local `.env` before seeding. These are development credentials, not credentials for a BEM pilot. Rerunning the seeder updates the seeded accounts, including their passwords; do not run it casually against operational data.

Pulling a seeder change does not update existing database passwords. To apply the account-specific passwords to a local development database, remove any `SEED_USER_PASSWORD` override, run `php artisan config:clear`, then `php artisan db:seed --class=DatabaseSeeder`. This resets the seeded accounts' passwords without deleting bookings or item deposits.

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

The commands above use PowerShell on Windows. On macOS or Linux, use `cp .env.example .env` and `npm` in place of `Copy-Item` and `npm.cmd`.

### After pulling changes

Protect local changes first. Fetch and integrate `origin/main` into your own feature branch, resolving conflicts without restoring obsolete routes or shared files. Run `php artisan migrate` when the update includes migrations. Run `composer install` or `npm.cmd install` when the respective dependency files change, then rebuild Vite-managed assets if needed. Your local database name stays in `.env`. Do not use `migrate:fresh` for routine updates: it drops existing tables and their data.

## Booking workflow

This is the agreed target workflow. The development-status table above distinguishes available features from pending work.

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
| `booking_proofs` | In `main`: private `ruangan` and `kunci` uploads. The server records upload time; this does not establish when a photo was taken. |
| `notifications` | In `main`: recipient-specific booking notices with read/unread state. |
| `item_deposits` | In `main`: owner account, depositor name, item description, deposit date, collection time, and owner archive time. No room booking is required. |
| `item_deposit_photos` | In `main`: private photos associated with each deposit. |

## Item deposits

Item deposits are separate from room bookings and do not reserve calendar slots. SPPI monitors deposits without approving them.

| Action | Borrower account | SPPI |
| --- | --- | --- |
| Record a deposit with 1–5 photos | Own unit | No |
| View records and private photos | Own unit | All units |
| Confirm collection | Own unit, while Dititipkan | No |
| Remove a collected record from the owner's list | Own unit, after Diambil | No; retains access to the archived record |

Open **Penitipan Barang** at `/penitipan` using a borrower account. Enter the depositor name, deposit date, item name and description, then choose 1–5 JPG/PNG photos, each up to 5 MB. Preview the photos and remove unwanted selections before submitting.

After physically collecting the items, the owner selects **Tandai sudah diambil** and confirms. The server records the collection time and changes the status from **Dititipkan** to **Diambil**. This is the borrower's report, not SPPI verification. Because accounts are shared by each unit, the system identifies the account rather than the individual using it.

Collected deposits can be removed from the owner's list using **Hapus dari daftar**. This archives the record for that owner; SPPI retains the record and photos. Active deposits cannot be archived. SPPI can monitor all deposits but cannot create, collect, or archive them.

Photos use private storage and authenticated viewing routes. Check the serving PHP configuration: `upload_max_filesize` must allow 5 MB per photo and `post_max_size` must allow the full request, including up to 25 MB of photos and multipart overhead. These server limits have not yet been verified on the target installation.

## Testing

```powershell
php artisan test
node --test tests/sidebar.test.cjs
git diff --check
```

Laravel feature tests use an in-memory SQLite database configured in `phpunit.xml`, separate from the application's MySQL database. Tests cover booking access and decisions, return evidence, notifications, booking lists, and item deposits. The Node.js tests cover sidebar state, persistence, and blocked browser storage.

Before requesting review, also check the affected screens in a browser: owner/admin access, upload previews, confirmation dialogs, desktop sidebar controls, and the mobile drawer. SQLite tests do not verify MySQL locking under simultaneous requests.

## Team and ownership

Each member owns the backend and frontend for their assigned feature. The assignments below follow the revised team plan.

| Member | Feature and responsibilities | Branch |
| --- | --- | --- |
| Hafiz Muhammad Shahzaib (`2510512901`) | **Auth and shared foundations.** Maintain shared account/room/booking migrations, the 11-account seeder, session login/logout, role middleware, and the shared layout foundation. Verify installation and coordinate schema contracts. | `feature/2510512901-auth-foundations` |
| Ignatius Gavrilla Reda (`2510512020`) | **SPPI decisions, item deposits, and integration review.** Maintain the admin queue and decisions, booking list, item deposits, and collapsible sidebar. Coordinate direct SPPI booking with the shared submission flow. Review and merge member PRs, maintain documentation, and prepare presentation materials. | `feature/2510512020-sppi-decisions`, `feature/2510512020-item-deposits`, `feature/2510512020-collapsible-sidebar` |
| Maulida Nabil Alifah (`2510512138`) | **Calendar and booking requests.** Build the Monday–Sunday grid, week controls, unit highlighting, and request forms. Lock the room row in a transaction to reject overlapping `Menunggu`/`Disetujui` slots (allowing adjacent slots), validate description and PIC, and trigger SPPI notifications. | `feature/2510512138-calendar-booking` |
| Adis Fawwaz Guspandi (`2510512055`) | **Status notifications.** Build the notification service contract, navbar unread badge, notification list, authorized detail links, and mark-as-read state (`PATCH /notifikasi/{id}`). Persist database notifications consistently with successful status transitions, so rollbacks leave no false notices. | `feature/2510512055-status-notifications` |
| Muhammad Raehansyah (`2510512127`) | **Return evidence.** Build two-photo upload, private storage and authorized viewing (`GET /peminjaman/{id}/bukti/{kategori}`), SPPI inspection, and single-photo replacement. Validate file types and sizes, record server timestamps, and trigger inspection notifications. | `feature/2510512127-proof-verification` |
| Naila Izzatul Kamilah (`2510512101`) | **Booking details and QA.** Maintain the owner/admin detail page (`GET /peminjaman/{id}`), decision and revision notes, and integration tests. Coordinate complete status-history tracking if added, and collect desktop/mobile test evidence. | `feature/2510512101-detail-history-qa` |

### Pull requests

1. Work on `feature/<NIM>-<feature-name>`. Protect local work and integrate the latest `origin/main` before requesting review.
2. Test the submitted scope, inspect desktop/mobile UI where relevant, and remove replaced or duplicate code. Preserve other members' routes and shared components.
3. Push your feature branch and open a PR targeting `main`. Describe the change, actual test results, screenshots for visible changes, and remaining dependencies. Use a Draft PR while required checks or dependencies are unresolved.
4. **Reda reviews and merges approved PRs.** Members address review feedback on their own branches; new pushes update the same PR. Do not push directly to `main` or force-push shared branches.

<p align="center">
  <sub>BEM UPNVJ - Kabinet Gelora Juang</sub>
</p>
