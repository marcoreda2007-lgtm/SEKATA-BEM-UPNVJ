**SEKATA — Sekretariat Kita Tertata**

SEKATA is a web-based secretariat room booking, conflict resolution, and post-usage verification system built with Laravel and MySQL for BEM UPNVJ (Universitas Pembangunan Nasional "Veteran" Jakarta).

Developed as the Final Group Project for the Web Programming course (Semester III, Undergraduate Program in Information System, Faculty of Computer Science, UPNVJ).

**1. Project Overview & Booking Lifecycle**

SEKATA centralizes room scheduling for the BEM UPNVJ secretariat across 7 departments (Bidang) and 4 bureaus (Biro).

**Calendar Check & Request (Menunggu):** Borrower units check the shared weekly calendar (Senin–Minggu) and submit a booking request with date, WIB time slot, activity description, and Person-in-Charge (PIC) details.

**Clash Detection & Forum (Dalam forum):** If two or more requests overlap, SPPI opens a dedicated discussion forum with a WIB deadline and triggers in-app notifications for involved units.

**SPPI Decision (Disetujui / Ditolak / Perlu Jadwal Baru):** SPPI approves one request (using database row-level locking to prevent double bookings) with a traceable reason. Overlapping requests automatically move to Perlu Jadwal Baru so those units can submit an alternative schedule.

**Post-Usage Proof Upload (Periksa Bukti):** After using the room, the borrower uploads 2 mandatory private photos: (1) cleaned room condition and (2) key return.

**Verification & Completion (Perlu Perbaikan / Selesai):** SPPI inspects both photos. If an image is unclear or incomplete, SPPI requests a revision with notes; once verified, the booking is marked Selesai.

**2. Database Schema (8 Tables)**

users — Unit accounts (ID, account name, unique login email, hashed password, role admin/unit, unit name, and unit type bidang/biro).

rooms — Secretariat room record (locked via lockForUpdate() during SPPI approval to prevent race conditions).

bookings — Booking requests (owner user_id, room_id, date, start/end time WIB, activity description, PIC name & division, status, decision reason, inspection notes).

forums — Clash resolution forums created by SPPI (creator user_id, response deadline WIB, closed timestamp).

forum_bookings — Pivot table linking conflicting bookings and participant units to a forum.

forum_messages — Discussion responses posted inside a forum (forum_id, user_id, message body, timestamp).

booking_proofs — Post-usage photo proofs (booking_id, category ruangan or kunci, private storage file path, upload timestamp).

notifications — In-app alerts for forum invitations and status updates (user_id, forum_id, message, read_at).

**3. System Requirements**

PHP: 8.3 or newer

Composer: Latest version

Database: MySQL / MariaDB (InnoDB engine via XAMPP)

Frontend: Blade Templates, Bootstrap 5, Custom CSS, Vanilla JavaScript

Version Control: Git & GitHub

**4. Installation & Running From Scratch**

Follow these steps to install and run the project locally:

Clone the repository

git clone https://github.com/Team-Lead-Username/SEKATA-BEM-UPNVJ.git
cd SEKATA-BEM-UPNVJ/laravel


Install PHP dependencies

composer install


Create the local .env configuration file

Windows (PowerShell / CMD):

copy .env.example .env


macOS / Linux:

cp .env.example .env


Generate the application key

php artisan key:generate


Create the database and configure .env

Start MySQL in XAMPP.

Open phpMyAdmin (http://localhost/phpmyadmin) and create an empty database named sekata_db.

Open .env inside the laravel directory and update your local MySQL credentials:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sekata_db
DB_USERNAME=root
DB_PASSWORD=


 *(Adjust `DB_PORT` or `DB_PASSWORD` if your local MySQL uses a custom port such as `3307` or a password).*


Run database migrations and seeders

php artisan migrate --seed


(Creates all 8 tables and seeds the 11 demo accounts and secretariat room record).

Start the local development server

php artisan serve


Open http://127.0.0.1:8000 in your browser.

**5. Seeded Demo Accounts (11 Units)**

Running php artisan migrate --seed populates 11 unit accounts:

Admin Account (1): Biro SPPI (Satuan Pengawasan dan Pengembangan Internal) — Full administrative access to approve/reject bookings, manage clash forums, record SPPI usage, and verify proof photos.

Borrower Accounts (10):

7 Bidang: Minat dan Bakat, Sosial dan Politik, Kemahasiswaan, Pemberdayaan Perempuan dan Perlindungan Mahasiswa, Lingkungan Masyarakat, Pengkeima, Ekraf.

3 Biro: Kesekretariatan, Kebendaharaan, Medkominfo.

(Refer to database/seeders for the default login credentials).

**6. GitHub Collaboration Rules**

Branch Naming: Every member works on their own branch formatted as feature/<NIM>-<feature-name>.

Pull Requests: Direct pushes to main are prohibited. Every feature must be submitted via Pull Request with a summary and test steps, reviewed by another member, and merged by the Team Lead.

**7. Detailed Division of Work (All 6 Members — Frontend & Backend)**

Each member builds their assigned module end-to-end (Backend Controller/Routes/Database + Frontend Blade/CSS/JS) to ensure balanced contributions and minimal merge conflicts:

**Member 1:** Hafiz Muhammad Shahzaib (2510512901) — Team Lead, Auth, Notifications & Coordination

Branch: feature/2510512901-auth-notifications-docs

Backend Tasks:

Initialize Laravel project skeleton, .env.example, and .gitignore rules.

Create database migrations for all 8 tables and seeders for the 11 demo unit accounts and secretariat room.

Build session-based authentication (GET /login, POST /login, POST /logout) and role-based access middleware (SPPI admin vs Unit borrower).

Build in-app notification endpoints (GET /notifikasi, PATCH /notifikasi/{id} to mark notifications as read).

Review and merge Pull Requests from all teammates into main.

Frontend & Coordination Tasks:

Build master Blade layout (resources/views/layouts/app.blade.php), responsive navbar, role-based navigation links, and notification badge/dropdown.

Build Login page UI (resources/views/auth/login.blade.php) with validation error messages.

Maintain README.md, track milestone progress (M1–M4).

**Member 2:** Ignatius Gavrilla Reda (2510512020) — SPPI Decisions & Clash Locking

Branch: feature/2510512020-sppi-decisions

Backend Tasks:

Build SPPI booking queue (GET /admin/peminjaman) using eager loading (with('user')) and status filters.

Implement SPPI decision logic (POST /admin/peminjaman/{id}/keputusan) using DB::transaction and pessimistic row locking (lockForUpdate()) on the rooms table to prevent double-booking race conditions.

Automatically update conflicting pending requests to Perlu Jadwal Baru when an overlapping slot is approved.

Implement direct SPPI room booking validation on empty slots (auto-approved if free).

Frontend Tasks:

Build SPPI Admin Dashboard and incoming booking queue table (resources/views/admin/bookings/index.blade.php).

Build Approve/Reject decision modal/form requiring a traceable decision reason (alasan keputusan).

Build SPPI direct usage booking form UI.

Prepare final presentation slides.

**Member 3:** Maulida Nabil Alifah (2510512138) — Shared Weekly Calendar & Booking Request CRUD

Branch: feature/2510512138-calendar-booking

Backend Tasks:

Implement weekly schedule retrieval (GET /jadwal) grouped by unit and Monday–Sunday (Senin–Minggu) date ranges in WIB.

Implement booking request CRUD (GET /peminjaman, POST /peminjaman, PATCH /peminjaman/{id}, DELETE /peminjaman/{id}).

Enforce server-side validation for activity description, PIC name/division, cleanliness agreement checkbox, and overlap checks against approved slots or active forums.

Lock editing/deletion once a booking enters an active forum (Dalam forum) or is approved.

Frontend Tasks:

Build interactive Weekly Calendar Grid (resources/views/schedule/index.blade.php) showing unit rows, date columns, time blocks, and status badges.

Implement JavaScript controls for week navigation (Previous/Next week, Today button) and unit highlight filtering without hiding other units' schedules.

Build Booking Request create and edit forms (resources/views/bookings/create.blade.php, edit.blade.php) with duration calculation and validation feedback.

**Member 4:** Adis Fawwaz Guspandi (2510512055) — Clash Resolution Forum & Deadlines

Branch: feature/2510512055-clash-forum

Backend Tasks:

Implement forum creation by SPPI (POST /admin/forum) linking conflicting bookings via forum_bookings and setting a WIB response deadline.

Automatically trigger in-app notifications (notifications table) for all participating units when a forum opens.

Implement forum view (GET /forum/{id}) and message posting (POST /forum/{id}/tanggapan) with strict participant-only authorization.

Enforce forum decision readiness rules (SPPI can finalize a decision only after all participants respond or the WIB deadline passes).

Frontend Tasks:

Build Clash Forum discussion page (resources/views/forums/show.blade.php) displaying conflicting booking summaries, WIB deadline countdown/status, and chronological messages.

Build SPPI "Open Forum" modal/form (selecting overlapping requests and setting the WIB deadline) and participant reply form.

**Member 5:** Muhammad Raehansyah (2510512127) — Post-Usage Proof Upload & Verification

Branch: feature/2510512127-proof-verification

Backend Tasks:

Implement two-photo upload endpoint (POST /peminjaman/{id}/bukti) requiring both ruangan (cleaned room) and kunci (key return) photos on initial submission.

Store uploaded files in Laravel's private storage disk (storage/app/private/proofs) with file type/size validation and server-recorded WIB upload timestamps.

Build an authorized file-serving route (GET /peminjaman/{id}/bukti/{kategori}) that verifies ownership or SPPI admin role before streaming the image (blocking unauthorized direct URL access).

Implement SPPI proof inspection (POST /admin/peminjaman/{id}/pemeriksaan) supporting Selesai approval or Perlu Perbaikan with notes (retaining the valid photo if only one photo needs replacement).

Frontend Tasks:

Build two-photo upload and single-photo revision replacement UI on the borrower booking view.

Build SPPI Proof Inspection interface displaying both photos side-by-side, upload timestamps, and the Approve (Selesai) / Request Revision (Perlu Perbaikan) form.

**Member 6:** Naila Izzatul Kamilah (2510512101) — Booking Details, Rescheduling & End-to-End QA

Branch: feature/2510512101-detail-reschedule-qa

Backend Tasks:

Implement booking detail retrieval (GET /peminjaman/{id}) using eager loading (with(['user', 'proofs', 'forums'])) and strict ownership/admin authorization.

Implement replacement schedule submission for bookings in Perlu Jadwal Baru status, validating the new slot and transitioning status back to Menunggu.

Frontend & QA Tasks:

Build Booking Detail & Status History page (resources/views/bookings/show.blade.php) showing full status progression, SPPI decision reasons, revision notes, and the reschedule form.

Design, execute, and document all 8 end-to-end test scenarios (T01–T08) across desktop and mobile (390px) viewports, compiling test evidence and screenshots for the final report and demo.
