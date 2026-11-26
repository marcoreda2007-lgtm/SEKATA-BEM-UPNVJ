<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingProof;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ExamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExamSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_database_seeder_keeps_one_operational_room_and_eleven_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(11, User::count());
        $this->assertSame(1, Room::count());
        $this->assertSame('Sekretariat BEM UPNVJ', Room::first()->name);
        $this->assertSame(0, Booking::count());
        $this->assertSame(0, BookingProof::count());
    }

    public function test_exam_seeder_does_not_duplicate_records_when_run_twice(): void
    {
        Storage::fake('local');

        $this->seed(ExamSeeder::class);

        $firstBookings = Booking::count();
        $firstProofs = BookingProof::count();

        $this->assertSame(11, User::count());
        $this->assertSame(6, Room::count());
        $this->assertSame(55, $firstBookings);
        $this->assertGreaterThan(0, $firstProofs);

        $this->seed(ExamSeeder::class);

        $this->assertSame(11, User::count());
        $this->assertSame(6, Room::count());
        $this->assertSame($firstBookings, Booking::count());
        $this->assertSame($firstProofs, BookingProof::count());
    }

    public function test_exam_seeder_preserves_existing_bookings_and_proof_evidence(): void
    {
        Storage::fake('local');

        $this->seed(ExamSeeder::class);

        $booking = Booking::where('status', 'Perlu Perbaikan')->firstOrFail();
        $booking->update([
            'activity_description' => 'Kegiatan Operasional Asli',
            'pic_name' => 'PIC Operasional',
        ]);

        $proof = $booking->proofs()->where('category', 'kunci')->firstOrFail();
        $proof->update([
            'original_name' => 'bukti-operasional-asli.png',
            'size' => 4321,
        ]);
        Storage::disk('local')->put($proof->path, 'OPERATIONAL_FILE_CONTENT');

        $this->seed(ExamSeeder::class);

        $booking->refresh();
        $proof->refresh();

        $this->assertSame('Kegiatan Operasional Asli', $booking->activity_description);
        $this->assertSame('PIC Operasional', $booking->pic_name);
        $this->assertSame('bukti-operasional-asli.png', $proof->original_name);
        $this->assertSame(4321, $proof->size);
        $this->assertSame('OPERATIONAL_FILE_CONTENT', Storage::disk('local')->get($proof->path));
    }

    public function test_exam_seeder_populates_consistent_supporting_records_across_statuses(): void
    {
        Storage::fake('local');

        $this->seed(ExamSeeder::class);

        $expectedStatuses = [
            'Menunggu',
            'Disetujui',
            'Ditolak',
            'Periksa Bukti',
            'Perlu Perbaikan',
            'Selesai',
        ];

        foreach ($expectedStatuses as $status) {
            $this->assertGreaterThan(0, Booking::where('status', $status)->count(), "Missing status: {$status}");
        }

        // Verify valid time ranges and no duplicate/overlapping slots within the seeded dataset.
        $bookings = Booking::with('proofs')->get();
        foreach ($bookings as $booking) {
            $this->assertLessThan($booking->end_time, $booking->start_time);
        }

        // Menunggu bookings have no admin decision and no proofs.
        foreach ($bookings->where('status', 'Menunggu') as $booking) {
            $this->assertNull($booking->decided_by);
            $this->assertNull($booking->decided_at);
            $this->assertCount(0, $booking->proofs);
        }

        // Disetujui and Ditolak bookings have decision metadata and no proofs.
        foreach ($bookings->whereIn('status', ['Disetujui', 'Ditolak']) as $booking) {
            $this->assertNotNull($booking->decided_by);
            $this->assertNotNull($booking->decided_at);
            $this->assertNotEmpty($booking->decision_reason);
            $this->assertCount(0, $booking->proofs);
        }

        // Periksa Bukti and Selesai bookings have both private proof categories without replacement flags.
        foreach ($bookings->whereIn('status', ['Periksa Bukti', 'Selesai']) as $booking) {
            $this->assertCount(2, $booking->proofs);
            $this->assertEqualsCanonicalizing(['ruangan', 'kunci'], $booking->proofs->pluck('category')->all());

            foreach ($booking->proofs as $proof) {
                $this->assertFalse($proof->requires_replacement);
                $this->assertTrue(Storage::disk('local')->exists($proof->path));
            }
        }

        // Perlu Perbaikan bookings have both proof categories, inspection note, and kunci flagged for replacement.
        foreach ($bookings->where('status', 'Perlu Perbaikan') as $booking) {
            $this->assertNotEmpty($booking->inspection_note);
            $this->assertCount(2, $booking->proofs);

            $ruanganProof = $booking->proofs->firstWhere('category', 'ruangan');
            $kunciProof = $booking->proofs->firstWhere('category', 'kunci');

            $this->assertNotNull($ruanganProof);
            $this->assertNotNull($kunciProof);
            $this->assertFalse($ruanganProof->requires_replacement);
            $this->assertTrue($kunciProof->requires_replacement);
            $this->assertTrue(Storage::disk('local')->exists($ruanganProof->path));
            $this->assertTrue(Storage::disk('local')->exists($kunciProof->path));
        }
    }
}