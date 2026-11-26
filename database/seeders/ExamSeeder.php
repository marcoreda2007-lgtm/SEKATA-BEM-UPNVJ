<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingProof;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Dedicated exam and benchmark seeder (6 rooms, 55 bookings, and consistent proof records).
 *
 * Intended for a dedicated test database (e.g., sekata_test). Uses explicit lookups to avoid
 * duplicating exact slots or overwriting existing records/evidence, and does not check partial
 * time overlaps against arbitrary pre-existing operational bookings.
 */
class ExamSeeder extends Seeder
{
    private const PROOF_STATUSES = [
        'Periksa Bukti',
        'Perlu Perbaikan',
        'Selesai',
    ];

    private const PROOF_CATEGORIES = [
        'ruangan',
        'kunci',
    ];

    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

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
            $rooms[] = Room::firstOrCreate(
                ['name' => $roomData['name']],
                [
                    'location' => $roomData['location'],
                    'description' => $roomData['description'],
                ]
            );
        }

        $adminUser = User::where('role', 'admin')->orderBy('id')->first();
        $borrowerUsers = User::where('role', 'unit')->orderBy('id')->get()->all();

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

            $booking = Booking::where('room_id', $room->id)
                ->whereDate('tanggal', $bookingDate)
                ->where('start_time', $slot[0])
                ->first();

            if ($booking === null) {
                $booking = Booking::create([
                    'room_id' => $room->id,
                    'user_id' => $unit->id,
                    'tanggal' => $bookingDate,
                    'start_time' => $slot[0],
                    'end_time' => $slot[1],
                    'activity_description' => sprintf('[SEED-%02d] %s - %s', $i + 1, $activities[$i % count($activities)], $unit->unit_name),
                    'pic_name' => $picNames[$i % count($picNames)],
                    'pic_division' => $unit->unit_name,
                    'status' => $status,
                    'decision_reason' => $decisionReason,
                    'inspection_note' => $inspectionNote,
                    'decided_by' => $decidedBy,
                    'decided_at' => $decidedAt,
                ]);
            }

            if (in_array($booking->status, self::PROOF_STATUSES, true)) {
                $this->seedProofsForBooking($booking, $bookingDate);
            }
        }
    }

    private function seedProofsForBooking(Booking $booking, string $bookingDate): void
    {
        foreach (self::PROOF_CATEGORIES as $category) {
            $existingProof = BookingProof::where('booking_id', $booking->id)
                ->where('category', $category)
                ->first();

            if ($existingProof !== null) {
                continue;
            }

            $path = sprintf('booking-proofs/%d/seed-%s.png', $booking->id, $category);
            $binary = $this->buildTestProofPng($booking->id, $category);

            if (! Storage::disk('local')->exists($path)) {
                $written = Storage::disk('local')->put($path, $binary);

                if ($written === false) {
                    throw new RuntimeException(sprintf(
                        'Failed to store seed proof file [%s] for booking #%d.',
                        $path,
                        $booking->id
                    ));
                }
            }

            $requiresReplacement = $booking->status === 'Perlu Perbaikan' && $category === 'kunci';

            BookingProof::firstOrCreate(
                [
                    'booking_id' => $booking->id,
                    'category' => $category,
                ],
                [
                    'path' => $path,
                    'original_name' => sprintf('seed-test-proof-%s-%d.png', $category, $booking->id),
                    'mime_type' => 'image/png',
                    'size' => strlen($binary),
                    'uploaded_at' => Carbon::parse($bookingDate)->setTime(21, 0),
                    'requires_replacement' => $requiresReplacement,
                ]
            );
        }
    }

    private function buildTestProofPng(int $bookingId, string $category): string
    {
        if (function_exists('imagecreatetruecolor')) {
            $image = imagecreatetruecolor(360, 160);
            $background = imagecolorallocate($image, 236, 245, 240);
            $border = imagecolorallocate($image, 25, 135, 84);
            $textColor = imagecolorallocate($image, 20, 60, 40);

            imagefill($image, 0, 0, $background);
            imagerectangle($image, 4, 4, 355, 155, $border);
            imagestring($image, 4, 18, 45, 'SEKATA SEED TEST EVIDENCE', $textColor);
            imagestring($image, 3, 18, 80, sprintf('Booking #%d | Category: %s', $bookingId, $category), $textColor);
            imagestring($image, 2, 18, 112, 'Synthetic private fixture for exam/test dataset', $textColor);

            ob_start();
            imagepng($image);
            $png = (string) ob_get_clean();
            imagedestroy($image);

            if ($png !== '') {
                return $png;
            }
        }

        // Valid 1x1 PNG fallback when GD is unavailable.
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
        );
    }
}