<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return [
            'pic_name' => 'Reda',
            'activity_description' => 'Rapat bidang',
            'tanggal' => '2026-12-01',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'responsibility_acknowledgement' => '1',
        ];
    }

    public function test_guest_cannot_submit(): void
    {
        $this->post(route('booking.store'), $this->payload())->assertRedirect(route('login'));
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_submission_uses_authenticated_owner_and_notifies_admin(): void
    {
        $owner = User::factory()->create(['role' => 'unit', 'unit_name' => 'Ekraf', 'unit_type' => 'bidang']);
        $admin = User::factory()->create(['role' => 'admin', 'unit_name' => 'SPPI', 'unit_type' => 'biro']);
        $room = Room::create(['name' => 'Sekretariat BEM UPNVJ']);

        $this->actingAs($owner)->post(route('booking.store'), array_merge($this->payload(), [
            'user_id' => $admin->id, 'room_id' => 999, 'status' => 'Disetujui',
        ]))->assertRedirect(route('peminjaman.index'));

        $booking = Booking::sole();
        $this->assertSame($owner->id, $booking->user_id);
        $this->assertSame($room->id, $booking->room_id);
        $this->assertSame('Ekraf', $booking->pic_division);
        $this->assertSame('Menunggu', $booking->status);
        $this->assertDatabaseHas('notifications', ['booking_id' => $booking->id, 'user_id' => $admin->id]);
    }

    public function test_pending_and_approved_overlap_is_rejected_but_adjacent_slot_is_allowed(): void
    {
        $owner = User::factory()->create(['role' => 'unit', 'unit_name' => 'Ekraf', 'unit_type' => 'bidang']);
        $room = Room::create(['name' => 'Sekretariat BEM UPNVJ']);
        $booking = Booking::create(array_merge($this->payload(), [
            'user_id' => $owner->id, 'room_id' => $room->id, 'pic_division' => 'Ekraf',
            'start_time' => '09:00:00', 'end_time' => '11:00:00', 'status' => 'Menunggu',
        ]));

        foreach (['Menunggu', 'Disetujui'] as $status) {
            $booking->update(['status' => $status]);
            $this->actingAs($owner)->post(route('booking.store'), $this->payload())
                ->assertSessionHasErrors('tanggal');
            $this->assertDatabaseCount('bookings', 1);
            $this->assertDatabaseCount('notifications', 0);
        }

        $this->post(route('booking.store'), array_merge($this->payload(), [
            'start_time' => '11:00', 'end_time' => '12:00',
        ]))->assertRedirect(route('peminjaman.index'));
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_rejected_slot_is_available_to_sppi_too(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'unit_name' => 'SPPI', 'unit_type' => 'biro']);
        $room = Room::create(['name' => 'Sekretariat BEM UPNVJ']);
        Booking::create(array_merge($this->payload(), [
            'user_id' => $admin->id, 'room_id' => $room->id, 'pic_division' => 'SPPI',
            'start_time' => '09:00:00', 'end_time' => '11:00:00', 'status' => 'Ditolak',
        ]));
        $this->actingAs($admin)->post(route('booking.store'), $this->payload())
            ->assertRedirect(route('peminjaman.index'));
        $this->assertDatabaseCount('bookings', 2);
    }

    public function test_invalid_time_and_missing_acknowledgement_do_not_save(): void
    {
        $owner = User::factory()->create(['role' => 'unit', 'unit_name' => 'Ekraf', 'unit_type' => 'bidang']);
        $this->actingAs($owner)->post(route('booking.store'), array_merge($this->payload(), [
            'end_time' => '08:00', 'responsibility_acknowledgement' => null,
        ]))->assertSessionHasErrors(['end_time', 'responsibility_acknowledgement']);
        $this->assertDatabaseCount('bookings', 0);
    }
}
