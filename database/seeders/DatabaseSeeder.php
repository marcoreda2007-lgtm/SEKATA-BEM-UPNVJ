<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roomDefinitions = [
            [
                'name' => 'Sekretariat BEM UPNVJ',
                'location' => 'Gedung Kegiatan Mahasiswa Lt. 1',
                'description' => 'Ruang sekretariat utama bersama untuk kegiatan seluruh bidang dan biro BEM UPNVJ.',
            ],
            [
                'name' => 'Ruang Rapat Kabinet Gelora Juang',
                'location' => 'Gedung Kegiatan Mahasiswa Lt. 1',
                'description' => 'Ruang rapat koordinasi pimpinan, rapat pleno, dan konsolidasi program kerja.',
            ],
            [
                'name' => 'Ruang Kerja Biro & Bidang',
                'location' => 'Gedung Kegiatan Mahasiswa Lt. 2',
                'description' => 'Ruang kerja harian penyusunan proposal, LPJ, dan administrasi kegiatan.',
            ],
            [
                'name' => 'Ruang Studio Medkominfo',
                'location' => 'Gedung Kegiatan Mahasiswa Lt. 2',
                'description' => 'Ruang produksi konten publikasi, dokumentasi, dan siaran media BEM UPNVJ.',
            ],
            [
                'name' => 'Ruang Logistik & Inventaris SPPI',
                'location' => 'Gedung Kegiatan Mahasiswa Lt. 1',
                'description' => 'Ruang penyimpanan perlengkapan sekretariat, kunci, dan barang titipan kegiatan.',
            ],
            [
                'name' => 'Aula Mini Sekretariat',
                'location' => 'Gedung Kegiatan Mahasiswa Lt. 3',
                'description' => 'Ruang diskusi publik, pelatihan internal, dan forum mahasiswa skala menengah.',
            ],
        ];

        $rooms = [];
        foreach ($roomDefinitions as $roomData) {
            $rooms[] = Room::updateOrCreate(
                ['name' => $roomData['name']],
                [
                    'location' => $roomData['location'],
                    'description' => $roomData['description'],
                ]
            );
        }

        // Development-only credentials (each account uses <prefix>123 unless overridden via SEED_USER_PASSWORD).
        // Must be rotated before any BEM pilot.
        $accounts = [
            [
                'name' => 'Admin Biro SPPI',
                'email' => 'sppi@sekata.test',
                'unit_name' => 'Biro SPPI',
                'unit_type' => 'biro',
                'role' => 'admin',
                'password' => 'sppi123',
            ],
            [
                'name' => 'Bidang Minat dan Bakat',
                'email' => 'minatbakat@sekata.test',
                'unit_name' => 'Bidang Minat dan Bakat',
                'unit_type' => 'bidang',
                'role' => 'unit',
                'password' => 'minatbakat123',
            ],
            [
                'name' => 'Bidang Sosial dan Politik',
                'email' => 'sospol@sekata.test',
                'unit_name' => 'Bidang Sosial dan Politik',
                'unit_type' => 'bidang',
                'role' => 'unit',
                'password' => 'sospol123',
            ],
            [
                'name' => 'Bidang Kemahasiswaan',
                'email' => 'kemahasiswaan@sekata.test',
                'unit_name' => 'Bidang Kemahasiswaan',
                'unit_type' => 'bidang',
                'role' => 'unit',
                'password' => 'kemahasiswaan123',
            ],
            [
                'name' => 'Bidang Pemberdayaan Perempuan dan Perlindungan Mahasiswa',
                'email' => 'pppm@sekata.test',
                'unit_name' => 'Bidang Pemberdayaan Perempuan dan Perlindungan Mahasiswa',
                'unit_type' => 'bidang',
                'role' => 'unit',
                'password' => 'pppm123',
            ],
            [
                'name' => 'Bidang Lingkungan Masyarakat',
                'email' => 'lingmas@sekata.test',
                'unit_name' => 'Bidang Lingkungan Masyarakat',
                'unit_type' => 'bidang',
                'role' => 'unit',
                'password' => 'lingmas123',
            ],
            [
                'name' => 'Bidang Pengkeima',
                'email' => 'pengkeima@sekata.test',
                'unit_name' => 'Bidang Pengkeima',
                'unit_type' => 'bidang',
                'role' => 'unit',
                'password' => 'pengkeima123',
            ],
            [
                'name' => 'Bidang Ekraf',
                'email' => 'ekraf@sekata.test',
                'unit_name' => 'Bidang Ekraf',
                'unit_type' => 'bidang',
                'role' => 'unit',
                'password' => 'ekraf123',
            ],
            [
                'name' => 'Biro Kesekretariatan',
                'email' => 'kesekretariatan@sekata.test',
                'unit_name' => 'Biro Kesekretariatan',
                'unit_type' => 'biro',
                'role' => 'unit',
                'password' => 'kesekretariatan123',
            ],
            [
                'name' => 'Biro Kebendaharaan',
                'email' => 'kebendaharaan@sekata.test',
                'unit_name' => 'Biro Kebendaharaan',
                'unit_type' => 'biro',
                'role' => 'unit',
                'password' => 'kebendaharaan123',
            ],
            [
                'name' => 'Biro Medkominfo',
                'email' => 'medkominfo@sekata.test',
                'unit_name' => 'Biro Medkominfo',
                'unit_type' => 'biro',
                'role' => 'unit',
                'password' => 'medkominfo123',
            ],
        ];

        $overridePassword = env('SEED_USER_PASSWORD');
        $adminUser = null;
        $borrowerUsers = [];

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'unit_name' => $account['unit_name'],
                    'unit_type' => $account['unit_type'],
                    'role' => $account['role'],
                    'password' => Hash::make($overridePassword ?: $account['password']),
                ]
            );

            if ($account['role'] === 'admin') {
                $adminUser = $user;
            } else {
                $borrowerUsers[] = $user;
            }
        }

        // Seed 55 deterministic, non-overlapping bookings across 6 rooms, 10 units, and all 6 statuses.
        $statuses = [
            'Selesai',
            'Disetujui',
            'Menunggu',
            'Periksa Bukti',
            'Perlu Perbaikan',
            'Ditolak',
        ];

        $timeSlots = [
            ['08:00:00', '10:00:00'],
            ['10:00:00', '12:00:00'],
            ['13:00:00', '15:00:00'],
            ['15:00:00', '17:00:00'],
            ['18:30:00', '20:30:00'],
        ];

        $activities = [
            'Rapat Koordinasi Program Kerja Bulanan',
            'Briefing Panitia Pelaksana Kegiatan',
            'Penyusunan Proposal dan RAB Kegiatan',
            'Diskusi Kajian Isu Strategis Mahasiswa',
            'Workshop Pelatihan Internal Pengurus',
            'Evaluasi Pelaksanaan Program Kerja',
            'Rapat Teknis Publikasi dan Dokumentasi',
            'Konsolidasi Lintas Bidang dan Biro',
            'Persiapan Logistik dan Perlengkapan Acara',
            'Forum Aspirasi dan Advokasi Mahasiswa',
            'Rapat Pleno Tengah Semester Kabinet',
        ];

        $picNames = [
            'Raka Pratama',
            'Nadia Putri',
            'Dimas Saputra',
            'Alya Rahmawati',
            'Fajar Nugroho',
            'Salsabila Azzahra',
            'Bagas Wijaya',
            'Intan Permata',
            'Rizky Maulana',
            'Zahra Khairunnisa',
        ];

        $baseDate = Carbon::create(2026, 11, 2);

        for ($i = 0; $i < 55; $i++) {
            $room = $rooms[$i % count($rooms)];
            $unit = $borrowerUsers[$i % count($borrowerUsers)];
            $slot = $timeSlots[$i % count($timeSlots)];
            $status = $statuses[$i % count($statuses)];
            $dayOffset = intdiv($i, count($timeSlots)) * 2 + ($i % 2);
            $bookingDate = $baseDate->copy()->addDays($dayOffset)->toDateString();

            $decidedBy = null;
            $decidedAt = null;
            $decisionReason = null;
            $inspectionNote = null;

            if ($status === 'Ditolak') {
                $decidedBy = $adminUser?->id;
                $decidedAt = Carbon::parse($bookingDate)->subDay()->setTime(14, 0);
                $decisionReason = 'Jadwal bentrok dengan agenda perawatan inventaris sekretariat.';
            } elseif (in_array($status, ['Disetujui', 'Periksa Bukti', 'Perlu Perbaikan', 'Selesai'], true)) {
                $decidedBy = $adminUser?->id;
                $decidedAt = Carbon::parse($bookingDate)->subDay()->setTime(11, 30);
                $decisionReason = 'Disetujui sesuai jadwal penggunaan ruangan.';
            }

            if ($status === 'Perlu Perbaikan') {
                $inspectionNote = 'Foto pengembalian kunci kurang jelas, mohon unggah ulang foto kunci di kotak SPPI.';
            } elseif ($status === 'Selesai') {
                $inspectionNote = 'Ruangan bersih dan kunci telah dikembalikan sesuai prosedur.';
            }

            Booking::updateOrCreate(
                [
                    'room_id' => $room->id,
                    'tanggal' => $bookingDate,
                    'start_time' => $slot[0],
                ],
                [
                    'user_id' => $unit->id,
                    'end_time' => $slot[1],
                    'activity_description' => sprintf('[SEED-%02d] %s - %s', $i + 1, $activities[$i % count($activities)], $unit->unit_name),
                    'pic_name' => $picNames[$i % count($picNames)],
                    'pic_division' => $unit->unit_name,
                    'status' => $status,
                    'decision_reason' => $decisionReason,
                    'inspection_note' => $inspectionNote,
                    'decided_by' => $decidedBy,
                    'decided_at' => $decidedAt,
                ]
            );
        }
    }
}