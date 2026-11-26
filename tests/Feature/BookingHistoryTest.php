<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('peminjaman.index'))->assertRedirect(route('login'));
    }

    public function test_unit_only_sees_own_bookings_even_when_another_user_id_is_requested(): void
    {
        $owner = User::factory()->create(['role' => 'unit', 'unit_name' => 'Bidang Ekraf', 'unit_type' => 'bidang']);
        $other = User::factory()->create(['role' => 'unit', 'unit_name' => 'Bidang Pengkeima', 'unit_type' => 'bidang']);
        $approved = $this->makeBooking($owner, 'Disetujui');
        $rejected = $this->makeBooking($owner, 'Ditolak');
        $foreign = $this->makeBooking($other, 'Menunggu');

        $this->actingAs($owner)
            ->get(route('peminjaman.index', ['user_id' => $other->id]))
            ->assertOk()
            ->assertSee($approved->activity_description)
            ->assertSee($rejected->activity_description)
            ->assertDontSee($foreign->activity_description)
            ->assertSee(route('peminjaman.show', $approved->id), false)
            ->assertViewHas('bookings', fn ($bookings) => $bookings->total() === 2);
    }

    public function test_admin_sees_all_statuses_with_newest_first_and_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'unit_name' => 'Biro SPPI', 'unit_type' => 'biro']);
        $owner = User::factory()->create(['role' => 'unit', 'unit_name' => 'Bidang Ekraf', 'unit_type' => 'bidang']);
        $statuses = ['Menunggu', 'Disetujui', 'Ditolak', 'Periksa Bukti', 'Perlu Perbaikan', 'Selesai'];
        $ids = [];

        for ($i = 0; $i < 11; $i++) {
            $ids[] = $this->makeBooking($i % 2 === 0 ? $owner : $admin, $statuses[$i % 6])->id;
        }

        $expected = array_reverse($ids);
        $this->actingAs($admin)->get(route('peminjaman.index'))
            ->assertOk()
            ->assertViewHas('bookings', fn ($bookings) => $bookings->total() === 11
                && $bookings->pluck('id')->all() === array_slice($expected, 0, 10));

        $this->get(route('peminjaman.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('bookings', fn ($bookings) => $bookings->pluck('id')->all() === [$ids[0]]);
    }

    public function test_unit_with_no_bookings_sees_empty_state(): void
    {
        $owner = User::factory()->create(['role' => 'unit', 'unit_name' => 'Bidang Ekraf', 'unit_type' => 'bidang']);
        $other = User::factory()->create(['role' => 'unit', 'unit_name' => 'Bidang Pengkeima', 'unit_type' => 'bidang']);
        $this->makeBooking($other, 'Disetujui');

        $this->actingAs($owner)->get(route('peminjaman.index'))
            ->assertOk()->assertSee('Belum ada pengajuan.');
    }

    private function makeBooking(User $owner, string $status): Booking
    {

        return Booking::create([
            'user_id' => $owner->id,
            'tanggal' => '2026-11-28',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'activity_description' => 'Kegiatan ' . fake()->uuid(),
            'pic_name' => 'PIC Pengajuan',
            'status' => $status,
            'created_at' => '2026-11-25 03:00:00',
        ]);
    }
}
