<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingDetailTest extends TestCase
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

    private function makeRoom(array $overrides = []): Room
    {
        return Room::create(array_merge([
            'name' => 'Ruang Rapat Utama',
            'location' => 'Sekretariat BEM Lantai 2',
            'description' => 'Ruang rapat kapasitas 20 orang',
        ], $overrides));
    }

    private function makeBooking(User $owner, Room $room, array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'user_id' => $owner->id,
            'room_id' => $room->id,
            'tanggal' => '2026-11-28',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'activity_description' => 'Rapat koordinasi program kerja bulanan',
            'pic_name' => 'Nadia Putri',
            'pic_division' => 'Kajian Strategis',
            'status' => 'Menunggu',
            'decision_reason' => null,
            'inspection_note' => null,
            'decided_by' => null,
            'decided_at' => null,
        ], $overrides));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $owner = $this->makeUser();
        $room = $this->makeRoom();
        $booking = $this->makeBooking($owner, $room);

        $this->get(route('peminjaman.show', $booking->id))
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_own_booking_detail_with_wib_timestamps_and_honest_empty_decision(): void
    {
        $this->travelTo(
            Carbon::create(2026, 11, 24, 3, 0, 0, 'UTC')->timezone(config('app.timezone'))
        );

        $owner = $this->makeUser(['unit_name' => 'Bidang Pengabdian Masyarakat']);
        $room = $this->makeRoom(['name' => 'Ruang Diskusi A']);
        $booking = $this->makeBooking($owner, $room, [
            'status' => 'Menunggu',
        ]);

        $response = $this->actingAs($owner)
            ->get(route('peminjaman.show', $booking->id));

        $response->assertOk()
            ->assertSee('Detail Peminjaman Sekretariat')
            ->assertSee('Bidang Pengabdian Masyarakat')
            ->assertSee('Ruang Diskusi A')
            ->assertSee('09:00–11:00 WIB')
            ->assertSee('24 Nov 2026, 10:00 WIB')
            ->assertSee('Data keputusan SPPI belum tercatat.')
            ->assertSee('Data keputusan belum tercatat.')
            ->assertSee('Riwayat status lengkap per tahap belum tersedia.')
            ->assertSee(route('jadwal.index'), false)
            ->assertDontSee('Disetujui');
    }

    public function test_other_unit_gets_403_for_another_units_booking(): void
    {
        $owner = $this->makeUser(['unit_name' => 'Bidang A']);
        $otherUnit = $this->makeUser(['unit_name' => 'Bidang B']);
        $room = $this->makeRoom();
        $booking = $this->makeBooking($owner, $room);

        $this->actingAs($otherUnit)
            ->get(route('peminjaman.show', $booking->id))
            ->assertForbidden();
    }

    public function test_admin_can_view_any_booking_and_sees_recorded_decision_in_wib(): void
    {
        $owner = $this->makeUser(['unit_name' => 'Biro Media']);
        $admin = $this->makeUser([
            'name' => 'Admin SPPI',
            'unit_name' => 'SPPI BEM UPNVJ',
            'role' => 'admin',
        ]);
        $room = $this->makeRoom();
        $booking = $this->makeBooking($owner, $room, [
            'status' => 'Disetujui',
            'decision_reason' => 'Jadwal ruangan tersedia dan tidak bentrok.',
            'decided_by' => $admin->id,
            'decided_at' => Carbon::create(2026, 11, 24, 6, 30, 0, 'UTC')->timezone(config('app.timezone')),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('peminjaman.show', $booking->id));

        $response->assertOk()
            ->assertSee('Biro Media')
            ->assertSee('Disetujui')
            ->assertSee('Jadwal ruangan tersedia dan tidak bentrok.')
            ->assertSee('SPPI BEM UPNVJ')
            ->assertSee('24 Nov 2026, 13:30 WIB')
            ->assertSee(route('admin.peminjaman.index'), false);
    }

    public function test_non_decision_status_with_decision_data_does_not_assume_approved_label(): void
    {
        $owner = $this->makeUser();
        $admin = $this->makeUser([
            'unit_name' => 'SPPI BEM UPNVJ',
            'role' => 'admin',
        ]);
        $room = $this->makeRoom();
        $booking = $this->makeBooking($owner, $room, [
            'status' => 'Periksa Bukti',
            'decision_reason' => 'Silakan jaga kebersihan ruangan.',
            'decided_by' => $admin->id,
            'decided_at' => Carbon::create(2026, 11, 24, 5, 0, 0, 'UTC')->timezone(config('app.timezone')),
        ]);

        $response = $this->actingAs($owner)
            ->get(route('peminjaman.show', $booking->id));

        $response->assertOk()
            ->assertSee('Periksa Bukti')
            ->assertSee('Keputusan SPPI tercatat')
            ->assertDontSee('Disetujui');
    }

    public function test_missing_booking_id_returns_404(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get('/peminjaman/999999')
            ->assertNotFound();
    }

    public function test_non_numeric_booking_id_returns_404(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get('/peminjaman/abc')
            ->assertNotFound();
    }
}