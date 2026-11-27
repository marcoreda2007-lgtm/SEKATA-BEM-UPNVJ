<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Facility;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFacilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Pengurus Bidang',
            'email' => fake()->unique()->safeEmail(),
            'unit_name' => 'Bidang Riset & Keilmuan',
            'unit_type' => 'bidang',
            'role' => 'unit',
            'password' => 'password',
        ], $overrides));
    }

    private function makeRoom(): Room
    {
        return Room::create([
            'name' => 'Ruang Rapat Utama',
            'location' => 'Sekretariat BEM Lantai 2',
            'description' => 'Ruang rapat kapasitas 20 orang',
        ]);
    }

    private function makeBooking(User $owner, Room $room): Booking
    {
        return Booking::create([
            'user_id' => $owner->id,
            'room_id' => $room->id,
            'tanggal' => '2026-11-28',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'activity_description' => 'Rapat koordinasi program kerja bulanan',
            'pic_name' => 'Nadia Putri',
            'pic_division' => 'Kajian Strategis',
            'status' => 'Menunggu',
        ]);
    }

    public function test_owner_can_attach_and_detach_facility_on_own_booking(): void
    {
        $owner = $this->makeUser();
        $room = $this->makeRoom();
        $booking = $this->makeBooking($owner, $room);
        $facility = Facility::create([
            'name' => 'Proyektor LCD',
            'description' => 'Proyektor ruang sekretariat',
        ]);

        // Attach fasilitas
        $this->actingAs($owner)
            ->post(route('peminjaman.fasilitas.store', $booking->id), [
                'facility_id' => $facility->id,
            ])
            ->assertRedirect(route('peminjaman.show', $booking->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('booking_facility', [
            'booking_id' => $booking->id,
            'facility_id' => $facility->id,
        ]);

        $this->actingAs($owner)
            ->get(route('peminjaman.show', $booking->id))
            ->assertOk()
            ->assertSee('Proyektor LCD')
            ->assertSee('1 fasilitas terpasang');

        // Detach fasilitas
        $this->actingAs($owner)
            ->delete(route('peminjaman.fasilitas.destroy', [$booking->id, $facility->id]))
            ->assertRedirect(route('peminjaman.show', $booking->id))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('booking_facility', [
            'booking_id' => $booking->id,
            'facility_id' => $facility->id,
        ]);
    }

    public function test_admin_can_attach_and_detach_facility_on_any_booking(): void
    {
        $owner = $this->makeUser();
        $admin = $this->makeUser([
            'name' => 'Admin SPPI',
            'unit_name' => 'Biro SPPI',
            'role' => 'admin',
        ]);
        $room = $this->makeRoom();
        $booking = $this->makeBooking($owner, $room);
        $facility = Facility::create([
            'name' => 'Sound System & Mikrofon',
            'description' => 'Perangkat pengeras suara',
        ]);

        $this->actingAs($admin)
            ->post(route('peminjaman.fasilitas.store', $booking->id), [
                'facility_id' => $facility->id,
            ])
            ->assertRedirect(route('peminjaman.show', $booking->id));

        $this->assertDatabaseHas('booking_facility', [
            'booking_id' => $booking->id,
            'facility_id' => $facility->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('peminjaman.fasilitas.destroy', [$booking->id, $facility->id]))
            ->assertRedirect(route('peminjaman.show', $booking->id));

        $this->assertDatabaseMissing('booking_facility', [
            'booking_id' => $booking->id,
            'facility_id' => $facility->id,
        ]);
    }

    public function test_duplicate_facility_attachment_on_same_booking_is_rejected(): void
    {
        $owner = $this->makeUser();
        $room = $this->makeRoom();
        $booking = $this->makeBooking($owner, $room);
        $facility = Facility::create([
            'name' => 'Papan Tulis & Spidol',
            'description' => 'Papan tulis putih',
        ]);

        $booking->facilities()->attach($facility->id);

        $this->actingAs($owner)
            ->from(route('peminjaman.show', $booking->id))
            ->post(route('peminjaman.fasilitas.store', $booking->id), [
                'facility_id' => $facility->id,
            ])
            ->assertRedirect(route('peminjaman.show', $booking->id))
            ->assertSessionHasErrors('facility_id');

        $this->assertSame(1, $booking->facilities()->count());
    }

    public function test_nonexistent_facility_id_is_rejected(): void
    {
        $owner = $this->makeUser();
        $room = $this->makeRoom();
        $booking = $this->makeBooking($owner, $room);

        $this->actingAs($owner)
            ->from(route('peminjaman.show', $booking->id))
            ->post(route('peminjaman.fasilitas.store', $booking->id), [
                'facility_id' => 999999,
            ])
            ->assertRedirect(route('peminjaman.show', $booking->id))
            ->assertSessionHasErrors('facility_id');

        $this->assertSame(0, $booking->facilities()->count());
    }

    public function test_other_unit_cannot_attach_or_detach_facility_on_another_units_booking(): void
    {
        $owner = $this->makeUser(['unit_name' => 'Bidang A']);
        $otherUnit = $this->makeUser(['unit_name' => 'Bidang B']);
        $room = $this->makeRoom();
        $booking = $this->makeBooking($owner, $room);
        $facility = Facility::create([
            'name' => 'Kursi Lipat Tambahan',
            'description' => 'Kursi lipat cadangan',
        ]);

        $this->actingAs($otherUnit)
            ->post(route('peminjaman.fasilitas.store', $booking->id), [
                'facility_id' => $facility->id,
            ])
            ->assertForbidden();

        $booking->facilities()->attach($facility->id);

        $this->actingAs($otherUnit)
            ->delete(route('peminjaman.fasilitas.destroy', [$booking->id, $facility->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('booking_facility', [
            'booking_id' => $booking->id,
            'facility_id' => $facility->id,
        ]);
    }

    public function test_non_admin_unit_cannot_manage_master_facilities(): void
    {
        $unitUser = $this->makeUser(['role' => 'unit']);
        $facility = Facility::create([
            'name' => 'Kabel Roll & HDMI',
            'description' => 'Kabel listrik dan HDMI',
        ]);

        $this->actingAs($unitUser)
            ->get(route('admin.fasilitas.index'))
            ->assertForbidden();

        $this->actingAs($unitUser)
            ->post(route('admin.fasilitas.store'), [
                'name' => 'Kamera Dokumentasi',
            ])
            ->assertForbidden();

        $this->actingAs($unitUser)
            ->get(route('admin.fasilitas.edit', $facility->id))
            ->assertForbidden();

        $this->actingAs($unitUser)
            ->put(route('admin.fasilitas.update', $facility->id), [
                'name' => 'Nama Diubah Paksa',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_update_facility_with_same_name_but_cannot_duplicate_other_facility_name(): void
    {
        $admin = $this->makeUser([
            'name' => 'Admin SPPI',
            'unit_name' => 'Biro SPPI',
            'role' => 'admin',
        ]);

        $projector = Facility::create([
            'name' => 'Proyektor LCD',
            'description' => 'Deskripsi lama',
        ]);

        $soundSystem = Facility::create([
            'name' => 'Sound System & Mikrofon',
            'description' => 'Perangkat audio',
        ]);

        // Update dengan nama yang sama dengan recordnya sendiri harus berhasil (unique ignore)
        $this->actingAs($admin)
            ->put(route('admin.fasilitas.update', $projector->id), [
                'name' => 'Proyektor LCD',
                'description' => 'Deskripsi baru yang diperbarui',
            ])
            ->assertRedirect(route('admin.fasilitas.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('facilities', [
            'id' => $projector->id,
            'name' => 'Proyektor LCD',
            'description' => 'Deskripsi baru yang diperbarui',
        ]);

        // Update nama dengan nama milik fasilitas lain harus ditolak validasi
        $this->actingAs($admin)
            ->from(route('admin.fasilitas.edit', $projector->id))
            ->put(route('admin.fasilitas.update', $projector->id), [
                'name' => $soundSystem->name,
                'description' => 'Mencoba duplikat nama',
            ])
            ->assertRedirect(route('admin.fasilitas.edit', $projector->id))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseHas('facilities', [
            'id' => $projector->id,
            'name' => 'Proyektor LCD',
        ]);
    }
}