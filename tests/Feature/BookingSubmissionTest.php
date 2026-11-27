<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingSubmissionTest extends TestCase
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

    private function createAdmin(): User
    {
        return User::factory()->create([
            'unit_name' => 'Biro SPPI',
            'unit_type' => 'biro',
            'role' => 'admin',
        ]);
    }

    private function createRoom(): void
    {
        Room::create([
            'name' => 'Sekretariat BEM UPNVJ',
            'description' => 'Ruang sekretariat BEM',
        ]);
    }

    private function bookingData(array $overrides = []): array
    {
        return array_merge([
            'pic_name' => 'Maulida',
            'activity_description' => 'Rapat koordinasi',
            'tanggal' => '2026-12-01',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'responsibility_acknowledgement' => '1',
        ], $overrides);
    }

    private function createExistingBooking(
        User $user,
        string $tanggal,
        string $start,
        string $end,
        string $status
    ): Booking {
        return Booking::create([
            'user_id' => $user->id,
            'pic_name' => 'PIC Lama',
            'activity_description' => 'Kegiatan sebelumnya',
            'tanggal' => $tanggal,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->post(
            route('booking.store'),
            $this->bookingData()
        );

        $response->assertRedirect(route('login'));
    }

    public function test_unit_can_submit_booking_and_user_id_cannot_be_forged(): void
    {
        $this->createRoom();

        $unit = $this->createUnit();
        $anotherUnit = $this->createUnit();
        $admin = $this->createAdmin();

        $response = $this->actingAs($unit)->post(
            route('booking.store'),
            $this->bookingData([
                'user_id' => $anotherUnit->id,
            ])
        );

        $response->assertRedirect(route('peminjaman.index'));

        $this->assertDatabaseHas('bookings', [
            'user_id' => $unit->id,
            'tanggal' => '2026-12-01 00:00:00',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'Menunggu',
        ]);

        $this->assertDatabaseMissing('bookings', [
            'user_id' => $anotherUnit->id,
            'tanggal' => '2026-12-01 00:00:00',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => 'booking_created',
        ]);
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $this->createRoom();

        $unit = $this->createUnit();

        $response = $this->actingAs($unit)->post(
            route('booking.store'),
            $this->bookingData([
                'start_time' => '10:00',
                'end_time' => '09:00',
            ])
        );

        $response->assertSessionHasErrors('end_time');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_pending_and_approved_bookings_block_overlapping_slots(): void
    {
        $this->createRoom();

        $unit = $this->createUnit();
        $otherUnit = $this->createUnit();

        foreach ([
            ['2026-12-02', 'Menunggu'],
            ['2026-12-03', 'Disetujui'],
        ] as [$date, $status]) {
            $this->createExistingBooking(
                $otherUnit,
                $date,
                '09:00',
                '11:00',
                $status
            );

            $response = $this->actingAs($unit)->post(
                route('booking.store'),
                $this->bookingData([
                    'tanggal' => $date,
                    'start_time' => '10:00',
                    'end_time' => '12:00',
                ])
            );

            $response->assertSessionHasErrors('tanggal');
        }

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_adjacent_booking_slots_are_allowed(): void
    {
        $this->createRoom();

        $unit = $this->createUnit();
        $otherUnit = $this->createUnit();

        $this->createExistingBooking(
            $otherUnit,
            '2026-12-04',
            '09:00',
            '10:00',
            'Menunggu'
        );

        $response = $this->actingAs($unit)->post(
            route('booking.store'),
            $this->bookingData([
                'tanggal' => '2026-12-04',
                'start_time' => '10:00',
                'end_time' => '11:00',
            ])
        );

        $response->assertRedirect(route('peminjaman.index'));

        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_rejected_booking_does_not_block_the_same_slot(): void
    {
        $this->createRoom();

        $unit = $this->createUnit();
        $otherUnit = $this->createUnit();

        $this->createExistingBooking(
            $otherUnit,
            '2026-12-05',
            '09:00',
            '10:00',
            'Ditolak'
        );

        $response = $this->actingAs($unit)->post(
            route('booking.store'),
            $this->bookingData([
                'tanggal' => '2026-12-05',
                'start_time' => '09:00',
                'end_time' => '10:00',
            ])
        );

        $response->assertRedirect(route('peminjaman.index'));

        $this->assertDatabaseCount('bookings', 2);
    }
}
