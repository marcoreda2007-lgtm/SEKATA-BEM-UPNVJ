<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingProof;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingProofTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_two_private_proofs_and_only_owner_or_admin_can_view_them(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser();
        $booking = $this->makeBooking($owner);

        $response = $this->from("/peminjaman/{$booking->id}")
            ->actingAs($owner)
            ->post(route('peminjaman.bukti.store', $booking), [
                'ruangan' => $this->fakePhoto('ruangan.png'),
                'kunci' => $this->fakePhoto('kunci.png'),
            ]);

        $response->assertRedirect("/peminjaman/{$booking->id}");
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('booking_proofs', 2);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'Periksa Bukti',
        ]);

        $proof = BookingProof::query()
            ->where('booking_id', $booking->id)
            ->where('category', 'ruangan')
            ->firstOrFail();

        Storage::disk('local')->assertExists($proof->path);

        $this->actingAs($owner)
            ->get(route('peminjaman.bukti.show', [$booking, 'ruangan']))
            ->assertOk();

        $this->actingAs($this->makeUser())
            ->get(route('peminjaman.bukti.show', [$booking, 'ruangan']))
            ->assertForbidden();

        $this->actingAs($this->makeUser(role: 'admin'))
            ->get(route('peminjaman.bukti.show', [$booking, 'ruangan']))
            ->assertOk();
    }

    public function test_single_photo_is_rejected_and_booking_stays_approved(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser();
        $booking = $this->makeBooking($owner);

        $this->from("/peminjaman/{$booking->id}")
            ->actingAs($owner)
            ->post(route('peminjaman.bukti.store', $booking), [
                'ruangan' => $this->fakePhoto('ruangan.png'),
            ])
            ->assertSessionHasErrors('kunci');

        $this->assertDatabaseCount('booking_proofs', 0);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'Disetujui',
        ]);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_another_unit_cannot_upload_proofs(): void
    {
        Storage::fake('local');

        $booking = $this->makeBooking($this->makeUser());

        $this->actingAs($this->makeUser())
            ->post(route('peminjaman.bukti.store', $booking), [
                'ruangan' => $this->fakePhoto('ruangan.png'),
                'kunci' => $this->fakePhoto('kunci.png'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('booking_proofs', 0);
    }

    public function test_non_admin_cannot_inspect_proofs(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser();
        $booking = $this->makeBooking($owner, 'Periksa Bukti');
        $this->makeProof($booking, 'ruangan');
        $this->makeProof($booking, 'kunci');

        $this->from("/admin/peminjaman/{$booking->id}")
            ->actingAs($owner)
            ->post(route('admin.peminjaman.pemeriksaan.store', $booking), [
                'decision' => 'selesai',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'Periksa Bukti',
        ]);
    }

    public function test_upload_is_rejected_when_status_is_not_eligible(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser();
        $booking = $this->makeBooking($owner, 'Menunggu');

        $this->from("/peminjaman/{$booking->id}")
            ->actingAs($owner)
            ->post(route('peminjaman.bukti.store', $booking), [
                'ruangan' => $this->fakePhoto('ruangan.png'),
                'kunci' => $this->fakePhoto('kunci.png'),
            ])
            ->assertSessionHasErrors('booking');

        $this->assertDatabaseCount('booking_proofs', 0);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'Menunggu',
        ]);
    }

    public function test_invalid_file_is_rejected_before_anything_is_stored(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser();
        $booking = $this->makeBooking($owner);

        $this->from("/peminjaman/{$booking->id}")
            ->actingAs($owner)
            ->post(route('peminjaman.bukti.store', $booking), [
                'ruangan' => UploadedFile::fake()->create('catatan.pdf', 10, 'application/pdf'),
                'kunci' => $this->fakePhoto('kunci.png'),
            ])
            ->assertSessionHasErrors('ruangan');

        $this->assertDatabaseCount('booking_proofs', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_sppi_can_request_one_replacement_then_finish_the_inspection(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser();
        $booking = $this->makeBooking($owner);
        $admin = $this->makeUser(role: 'admin');

        $this->from("/peminjaman/{$booking->id}")
            ->actingAs($owner)
            ->post(route('peminjaman.bukti.store', $booking), [
                'ruangan' => $this->fakePhoto('ruangan.png'),
                'kunci' => $this->fakePhoto('kunci.png'),
            ])
            ->assertSessionHasNoErrors();

        $ruanganPath = BookingProof::query()
            ->where('booking_id', $booking->id)
            ->where('category', 'ruangan')
            ->value('path');

        $this->from("/admin/peminjaman/{$booking->id}")
            ->actingAs($admin)
            ->post(route('admin.peminjaman.pemeriksaan.store', $booking), [
                'decision' => 'perbaikan',
                'requested_category' => 'kunci',
                'inspection_note' => 'Foto kunci belum terlihat jelas.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'Perlu Perbaikan',
        ]);
        $this->assertDatabaseHas('booking_proofs', [
            'booking_id' => $booking->id,
            'category' => 'kunci',
            'requires_replacement' => true,
        ]);

        $this->from("/peminjaman/{$booking->id}")
            ->actingAs($owner)
            ->post(route('peminjaman.bukti.store', $booking), [
                'category' => 'kunci',
                'kunci' => $this->fakePhoto('kunci-baru.png'),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'Periksa Bukti',
        ]);
        $this->assertDatabaseHas('booking_proofs', [
            'booking_id' => $booking->id,
            'category' => 'kunci',
            'requires_replacement' => false,
        ]);
        $this->assertSame(
            $ruanganPath,
            BookingProof::query()
                ->where('booking_id', $booking->id)
                ->where('category', 'ruangan')
                ->value('path'),
        );

        $this->from("/admin/peminjaman/{$booking->id}")
            ->actingAs($admin)
            ->post(route('admin.peminjaman.pemeriksaan.store', $booking), [
                'decision' => 'selesai',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'Selesai',
        ]);
    }

    public function test_replacement_of_a_category_not_requested_is_rejected(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser();
        $booking = $this->makeBooking($owner, 'Perlu Perbaikan');
        $ruangan = $this->makeProof($booking, 'ruangan', requiresReplacement: true);
        $this->makeProof($booking, 'kunci');

        $this->from("/peminjaman/{$booking->id}")
            ->actingAs($owner)
            ->post(route('peminjaman.bukti.store', $booking), [
                'category' => 'kunci',
                'kunci' => $this->fakePhoto('kunci-baru.png'),
            ])
            ->assertSessionHasErrors('category');

        $this->assertSame($ruangan->path, $ruangan->fresh()->path);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'Perlu Perbaikan',
        ]);
    }

    public function test_repeat_upload_after_completion_does_not_change_status_or_files(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser();
        $booking = $this->makeBooking($owner, 'Selesai');
        $ruangan = $this->makeProof($booking, 'ruangan');
        $kunci = $this->makeProof($booking, 'kunci');

        $this->from("/peminjaman/{$booking->id}")
            ->actingAs($owner)
            ->post(route('peminjaman.bukti.store', $booking), [
                'ruangan' => $this->fakePhoto('ruangan-baru.png'),
                'kunci' => $this->fakePhoto('kunci-baru.png'),
            ])
            ->assertSessionHasErrors('booking');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'Selesai',
        ]);
        $this->assertDatabaseCount('booking_proofs', 2);
        $this->assertSame($ruangan->path, $ruangan->fresh()->path);
        $this->assertSame($kunci->path, $kunci->fresh()->path);
        Storage::disk('local')->assertExists($ruangan->path);
        Storage::disk('local')->assertExists($kunci->path);
    }

    public function test_proof_partial_renders_for_owner_and_admin(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser();
        $booking = $this->makeBooking($owner);
        $this->makeProof($booking, 'ruangan');
        $this->makeProof($booking, 'kunci');

        $this->actingAs($owner);
        $this->withViewErrors([]);
        $ownerHtml = view('peminjaman.partials.proofs', ['booking' => $booking->fresh()])->render();

        $this->assertStringContainsString('Bukti Pengembalian Sekretariat', $ownerHtml);
        $this->assertStringContainsString('Kirim bukti', $ownerHtml);
        $this->assertStringContainsString('bukan waktu foto diambil', $ownerHtml);
        $this->assertStringNotContainsString('Simpan hasil pemeriksaan', $ownerHtml);

        $booking->update(['status' => 'Periksa Bukti']);

        $this->actingAs($this->makeUser(role: 'admin'));
        $this->withViewErrors([]);
        $adminHtml = view('peminjaman.partials.proofs', ['booking' => $booking->fresh()])->render();

        $this->assertStringContainsString('Simpan hasil pemeriksaan', $adminHtml);
        $this->assertStringNotContainsString('Kirim bukti', $adminHtml);
    }

    // 1x1 PNG asli; UploadedFile::fake()->image() butuh ekstensi GD yang tidak terpasang di sini.
    private function fakePhoto(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='),
        );
    }

    private function makeUser(string $role = 'unit'): User
    {
        return User::query()->create([
            'name' => $role === 'admin' ? 'SPPI' : 'Peminjam',
            'email' => uniqid($role, true).'@example.test',
            'unit_name' => $role === 'admin' ? 'SPPI' : 'Ekraf',
            'unit_type' => 'biro',
            'role' => $role,
            'password' => 'password',
        ]);
    }

    private function makeBooking(User $owner, string $status = 'Disetujui'): Booking
    {
        $room = Room::query()->create([
            'name' => 'Sekretariat BEM',
        ]);

        return Booking::query()->create([
            'user_id' => $owner->id,
            'room_id' => $room->id,
            'tanggal' => '2026-11-25',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'activity_description' => 'Rapat kerja.',
            'pic_name' => 'PIC Ekraf',
            'pic_division' => 'Ekraf',
            'status' => $status,
        ]);
    }

    private function makeProof(Booking $booking, string $category, bool $requiresReplacement = false): BookingProof
    {
        $path = Storage::disk('local')->putFile(
            "booking-proofs/{$booking->id}",
            $this->fakePhoto("{$category}.png"),
        );

        return $booking->proofs()->create([
            'category' => $category,
            'path' => $path,
            'original_name' => "{$category}.png",
            'mime_type' => 'image/png',
            'size' => 100,
            'uploaded_at' => now(),
            'requires_replacement' => $requiresReplacement,
        ]);
    }
}
