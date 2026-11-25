<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingProof;
use App\Models\Room;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;
use Throwable;

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

    public function test_another_unit_cannot_upload_or_view_the_detail_page(): void
    {
        Storage::fake('local');

        $booking = $this->makeBooking($this->makeUser());
        $other = $this->makeUser();

        $this->actingAs($other)
            ->post(route('peminjaman.bukti.store', $booking), [
                'ruangan' => $this->fakePhoto('ruangan.png'),
                'kunci' => $this->fakePhoto('kunci.png'),
            ])
            ->assertForbidden();

        $this->actingAs($other)
            ->get(route('peminjaman.show', $booking->id))
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

    public function test_detail_page_shows_upload_form_for_owner_when_approved(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser();
        $booking = $this->makeBooking($owner);

        $this->actingAs($owner)
            ->get(route('peminjaman.show', $booking->id))
            ->assertOk()
            ->assertSee('Bukti Pengembalian Sekretariat')
            ->assertSee('name="ruangan"', false)
            ->assertSee('name="kunci"', false)
            ->assertSee('Kirim bukti')
            ->assertDontSee('Simpan hasil pemeriksaan');
    }

    public function test_detail_page_shows_inspection_form_for_admin_when_under_review(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser();
        $booking = $this->makeBooking($owner, 'Periksa Bukti');
        $this->makeProof($booking, 'ruangan');
        $this->makeProof($booking, 'kunci');

        $this->actingAs($this->makeUser(role: 'admin'))
            ->get(route('peminjaman.show', $booking->id))
            ->assertOk()
            ->assertSee('Simpan hasil pemeriksaan')
            ->assertSee('name="decision"', false)
            ->assertSee('name="requested_category"', false)
            ->assertDontSee('Kirim bukti pengganti');
    }

    public function test_detail_page_shows_sppi_note_and_single_replacement_form_to_owner(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser();
        $booking = $this->makeBooking($owner, 'Perlu Perbaikan');
        $booking->update(['inspection_note' => 'Foto kunci masih blur.']);
        $this->makeProof($booking, 'ruangan', requiresReplacement: true);
        $this->makeProof($booking, 'kunci');

        $this->actingAs($owner)
            ->get(route('peminjaman.show', $booking->id))
            ->assertOk()
            ->assertSee('Catatan SPPI: Foto kunci masih blur.')
            ->assertSee('Kirim foto pengganti')
            ->assertSee('name="category"', false)
            ->assertDontSee('name="kunci"', false);
    }

    public function test_full_flow_through_the_detail_page_keeps_the_unrequested_photo(): void
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

        $this->actingAs($owner)
            ->get(route('peminjaman.show', $booking->id))
            ->assertOk()
            ->assertSee("/peminjaman/{$booking->id}/bukti/ruangan")
            ->assertSee("/peminjaman/{$booking->id}/bukti/kunci")
            ->assertDontSee('Kirim bukti');

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

        $this->actingAs($owner)
            ->get(route('peminjaman.show', $booking->id))
            ->assertOk()
            ->assertSee('Catatan SPPI: Foto kunci belum terlihat jelas.')
            ->assertSee('Kirim foto pengganti');

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
        Storage::disk('local')->assertExists($ruanganPath);

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

        $this->actingAs($owner)
            ->get(route('peminjaman.show', $booking->id))
            ->assertOk()
            ->assertSee('Selesai')
            ->assertDontSee('Kirim foto pengganti');
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

    public function test_transaction_failure_cleans_up_new_files(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser();
        $booking = $this->makeBooking($owner);

        // Gagal di akhir transaksi, setelah kedua file selesai ditulis.
        Booking::updating(function (): void {
            throw new RuntimeException('forced database failure');
        });

        try {
            $this->actingAs($owner)->post(route('peminjaman.bukti.store', $booking), [
                'ruangan' => $this->fakePhoto('ruangan.png'),
                'kunci' => $this->fakePhoto('kunci.png'),
            ]);
        } catch (Throwable) {
            // Kegagalan yang dipaksakan memang dilempar ulang oleh controller.
        } finally {
            Booking::flushEventListeners();
        }

        $this->assertCount(0, Storage::disk('local')->allFiles());
        $this->assertDatabaseCount('booking_proofs', 0);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'Disetujui',
        ]);
    }

    public function test_old_file_delete_failure_after_commit_keeps_the_new_file(): void
    {
        $owner = $this->makeUser();
        $booking = $this->makeBooking($owner, 'Perlu Perbaikan');
        $this->makeProof($booking, 'ruangan');
        $kunci = $this->makeProof($booking, 'kunci', requiresReplacement: true);

        $newPath = "booking-proofs/{$booking->id}/kunci-baru.png";
        $deleted = [];

        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('putFile')->once()->andReturn($newPath);
        $disk->shouldReceive('delete')->andReturnUsing(function ($path) use (&$deleted, $kunci) {
            $deleted[] = $path;

            if ($path === $kunci->path) {
                throw new RuntimeException('disk cleanup failed');
            }

            return true;
        });

        Storage::shouldReceive('disk')->andReturn($disk);

        $this->from("/peminjaman/{$booking->id}")
            ->actingAs($owner)
            ->post(route('peminjaman.bukti.store', $booking), [
                'category' => 'kunci',
                'kunci' => $this->fakePhoto('kunci-baru.png'),
            ])
            ->assertSessionHasNoErrors();

        // Hanya file lama yang dicoba dihapus; file baru tidak pernah ikut terhapus.
        $this->assertSame([$kunci->path], $deleted);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'Periksa Bukti',
        ]);
        $this->assertDatabaseHas('booking_proofs', [
            'booking_id' => $booking->id,
            'category' => 'kunci',
            'path' => $newPath,
        ]);
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
