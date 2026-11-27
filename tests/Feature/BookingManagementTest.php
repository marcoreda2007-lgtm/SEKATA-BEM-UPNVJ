<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createUnit(): User
    {
        return User::factory()->create([
            'unit_name' => 'Bidang Ekraf',
            'unit_type' => 'bidang',
            'role' => 'unit',
        ]);
    }

    private function createRoom(): void
    {
        Room::create([
            'name' => 'Sekretariat BEM UPNVJ',
            'description' => 'Ruang sekretariat BEM',
        ]);
    }

    private function createBooking(
        User $user,
        array $overrides = []
    ): Booking {
        return Booking::create(array_merge([
            'user_id' => $user->id,
            'pic_name' => 'Maulida',
            'activity_description' => 'Rapat koordinasi',
            'tanggal' => '2026-12-01',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Menunggu',
        ], $overrides));
    }

    private function bookingData(array $overrides = []): array
    {
        return array_merge([
            'pic_name' => 'Maulida Baru',
            'activity_description' => 'Rapat evaluasi',
            'tanggal' => '2026-12-02',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'responsibility_acknowledgement' => '1',
        ], $overrides);
    }

    public function test_guest_cannot_access_booking_management_routes(): void
    {
        $this->createRoom();

        $unit = $this->createUnit();
        $booking = $this->createBooking($unit);

        $this->get(route('peminjaman.edit', $booking->id))
            ->assertRedirect(route('login'));

        $this->put(
            route('peminjaman.update', $booking->id),
            $this->bookingData()
        )->assertRedirect(route('login'));

        $this->delete(
            route('peminjaman.destroy', $booking->id)
        )->assertRedirect(route('login'));
    }

    public function test_owner_can_open_edit_page_for_own_pending_booking(): void
    {
        $unit = $this->createUnit();
        $booking = $this->createBooking($unit);

        $response = $this->actingAs($unit)
            ->get(route('peminjaman.edit', $booking->id));

        $response->assertOk()
            ->assertViewIs('bookings.edit')
            ->assertSee('Maulida')
            ->assertSee('Rapat koordinasi');
    }

    public function test_owner_can_update_own_pending_booking(): void
    {
        $this->createRoom();

        $unit = $this->createUnit();
        $booking = $this->createBooking($unit);

        $response = $this->actingAs($unit)->put(
            route('peminjaman.update', $booking->id),
            $this->bookingData()
        );

        $response->assertRedirect(
            route('peminjaman.show', $booking->id)
        );

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'user_id' => $unit->id,
            'pic_name' => 'Maulida Baru',
            'activity_description' => 'Rapat evaluasi',
            'tanggal' => '2026-12-02 00:00:00',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'Menunggu',
        ]);
    }

    public function test_owner_can_delete_own_pending_booking(): void
    {
        $this->createRoom();

        $unit = $this->createUnit();
        $booking = $this->createBooking($unit);

        $response = $this->actingAs($unit)->delete(
            route('peminjaman.destroy', $booking->id)
        );

        $response->assertRedirect(route('peminjaman.index'));

        $this->assertDatabaseMissing('bookings', [
            'id' => $booking->id,
        ]);
    }

    public function test_unit_cannot_edit_or_delete_another_units_booking(): void
    {
        $this->createRoom();

        $owner = $this->createUnit();
        $anotherUnit = $this->createUnit();

        $booking = $this->createBooking($owner);

        $this->actingAs($anotherUnit)
            ->get(route('peminjaman.edit', $booking->id))
            ->assertForbidden();

        $this->actingAs($anotherUnit)
            ->put(
                route('peminjaman.update', $booking->id),
                $this->bookingData()
            )
            ->assertForbidden();

        $this->actingAs($anotherUnit)
            ->delete(route('peminjaman.destroy', $booking->id))
            ->assertForbidden();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_booking_that_is_not_pending_cannot_be_edited_or_deleted(): void
    {
        $this->createRoom();

        $unit = $this->createUnit();

        foreach (['Disetujui', 'Ditolak'] as $status) {
            $booking = $this->createBooking($unit, [
                'status' => $status,
            ]);

            $this->actingAs($unit)
                ->get(route('peminjaman.edit', $booking->id))
                ->assertForbidden();

            $this->actingAs($unit)
                ->put(
                    route('peminjaman.update', $booking->id),
                    $this->bookingData()
                )
                ->assertForbidden();

            $this->actingAs($unit)
                ->delete(route('peminjaman.destroy', $booking->id))
                ->assertForbidden();

            $this->assertDatabaseHas('bookings', [
                'id' => $booking->id,
                'status' => $status,
            ]);
        }
    }

    public function test_admin_cannot_edit_or_delete_booking(): void
    {
        $this->createRoom();

        $unit = $this->createUnit();

        $admin = User::factory()->create([
            'unit_name' => 'Biro SPPI',
            'unit_type' => 'biro',
            'role' => 'admin',
        ]);

        $booking = $this->createBooking($unit);

        $this->actingAs($admin)
            ->get(route('peminjaman.edit', $booking->id))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('peminjaman.destroy', $booking->id))
            ->assertForbidden();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
        ]);
    }

    public function test_update_rejects_overlapping_booking_schedule(): void
    {
        $this->createRoom();

        $unit = $this->createUnit();
        $anotherUnit = $this->createUnit();

        $booking = $this->createBooking($unit);

        $this->createBooking($anotherUnit, [
            'tanggal' => '2026-12-02',
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $response = $this->actingAs($unit)->put(
            route('peminjaman.update', $booking->id),
            $this->bookingData([
                'tanggal' => '2026-12-02',
                'start_time' => '11:00',
                'end_time' => '13:00',
            ])
        );

        $response->assertSessionHasErrors('tanggal');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'tanggal' => '2026-12-01 00:00:00',
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);
    }
}
