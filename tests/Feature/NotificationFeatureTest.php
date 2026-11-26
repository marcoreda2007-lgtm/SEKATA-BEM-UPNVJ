<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\Room;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class NotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createRoom(): Room
    {
        return Room::create([
            'name' => 'Sekretariat BEM UPNVJ',
            'location' => 'Sekretariat BEM UPNVJ',
            'description' => 'Ruang sekretariat bersama untuk pengujian.',
        ]);
    }

    private function createUser(string $email, string $unitName, string $unitType = 'bidang', string $role = 'unit'): User
    {
        return User::create([
            'name' => $unitName,
            'email' => $email,
            'unit_name' => $unitName,
            'unit_type' => $unitType,
            'role' => $role,
            'password' => Hash::make('password'),
        ]);
    }

    private function createBooking(User $owner, Room $room, array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'user_id' => $owner->id,
            'room_id' => $room->id,
            'tanggal' => '2026-11-26',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'activity_description' => 'Rapat koordinasi pengujian notifikasi',
            'pic_name' => 'Adis',
            'pic_division' => $owner->unit_name,
            'status' => 'Menunggu',
        ], $overrides));
    }

    public function test_guest_is_redirected_to_login_from_notification_routes(): void
    {
        $room = $this->createRoom();
        $ekraf = $this->createUser('ekraf@sekata.test', 'Bidang Ekraf');
        $booking = $this->createBooking($ekraf, $room, ['status' => 'Disetujui']);
        $notification = NotificationService::notifyBookingDecision($booking);

        $this->get('/notifikasi')->assertRedirect(route('login'));
        $this->patch('/notifikasi/' . $notification->id)->assertRedirect(route('login'));
    }

    public function test_sppi_receives_new_booking_notification_and_badge_only_for_recipient(): void
    {
        $room = $this->createRoom();
        $sppi = $this->createUser('sppi@sekata.test', 'Biro SPPI', 'biro', 'admin');
        $ekraf = $this->createUser('ekraf@sekata.test', 'Bidang Ekraf');
        $pengkeima = $this->createUser('pengkeima@sekata.test', 'Bidang Pengkeima');

        $booking = $this->createBooking($ekraf, $room, ['status' => 'Menunggu']);
        NotificationService::notifyBookingCreated($booking);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $sppi->id,
            'booking_id' => $booking->id,
            'type' => Notification::TYPE_BOOKING_CREATED,
            'read_at' => null,
        ]);
        $this->assertSame(1, Notification::count());

        // Badge muncul di halaman SPPI dan daftar memuat tautan detail pengajuan yang benar
        $this->actingAs($sppi)
            ->get('/admin/peminjaman')
            ->assertOk()
            ->assertSee('data-unread-badge', false);

        $this->actingAs($sppi)
            ->get('/notifikasi')
            ->assertOk()
            ->assertSee('Pengajuan Peminjaman Baru')
            ->assertSee('/peminjaman/' . $booking->id);

        // Unit lain tidak melihat notifikasi maupun badge SPPI
        $this->actingAs($pengkeima)
            ->get('/notifikasi')
            ->assertOk()
            ->assertDontSee('Pengajuan Peminjaman Baru')
            ->assertDontSee('data-unread-badge', false);
    }

    public function test_owner_receives_decision_and_proof_review_notifications_with_reasons_and_notes(): void
    {
        $room = $this->createRoom();
        $sppi = $this->createUser('sppi@sekata.test', 'Biro SPPI', 'biro', 'admin');
        $ekraf = $this->createUser('ekraf@sekata.test', 'Bidang Ekraf');
        $pengkeima = $this->createUser('pengkeima@sekata.test', 'Bidang Pengkeima');

        $approved = $this->createBooking($ekraf, $room, [
            'status' => 'Disetujui',
            'decision_reason' => 'Slot kosong dan jadwal sesuai.',
        ]);
        $rejected = $this->createBooking($ekraf, $room, [
            'status' => 'Ditolak',
            'decision_reason' => 'Bentrok agenda BEM.',
        ]);
        $revision = $this->createBooking($ekraf, $room, [
            'status' => 'Perlu Perbaikan',
            'inspection_note' => 'Foto kunci kurang jelas.',
        ]);
        $completed = $this->createBooking($ekraf, $room, [
            'status' => 'Selesai',
            'inspection_note' => 'Ruangan bersih dan kunci kembali.',
        ]);

        NotificationService::notifyBookingDecision($approved);
        NotificationService::notifyBookingDecision($rejected);
        NotificationService::notifyProofReviewed($revision);
        NotificationService::notifyProofReviewed($completed);

        $this->assertSame(4, Notification::forUser($ekraf->id)->unread()->count());
        $this->assertSame(0, Notification::forUser($pengkeima->id)->count());
        $this->assertSame(0, Notification::forUser($sppi->id)->count());

        $this->actingAs($ekraf)
            ->get('/notifikasi')
            ->assertOk()
            ->assertSee('Slot kosong dan jadwal sesuai.')
            ->assertSee('Bentrok agenda BEM.')
            ->assertSee('Foto kunci kurang jelas.')
            ->assertSee('Ruangan bersih dan kunci kembali.');
    }

    public function test_other_users_cannot_mark_someone_elses_notification_as_read(): void
    {
        $room = $this->createRoom();
        $sppi = $this->createUser('sppi@sekata.test', 'Biro SPPI', 'biro', 'admin');
        $ekraf = $this->createUser('ekraf@sekata.test', 'Bidang Ekraf');
        $pengkeima = $this->createUser('pengkeima@sekata.test', 'Bidang Pengkeima');

        $booking = $this->createBooking($ekraf, $room, ['status' => 'Disetujui']);
        $notification = NotificationService::notifyBookingDecision($booking);

        $this->actingAs($pengkeima)
            ->patch('/notifikasi/' . $notification->id)
            ->assertForbidden();

        // Admin sekalipun tidak boleh mengubah status baca notifikasi pribadi unit lain
        $this->actingAs($sppi)
            ->patch('/notifikasi/' . $notification->id)
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_recipient_can_mark_as_read_and_repeated_mark_as_read_stays_consistent(): void
    {
        $room = $this->createRoom();
        $ekraf = $this->createUser('ekraf@sekata.test', 'Bidang Ekraf');

        $booking1 = $this->createBooking($ekraf, $room, ['status' => 'Disetujui']);
        $booking2 = $this->createBooking($ekraf, $room, ['status' => 'Ditolak']);

        $first = NotificationService::notifyBookingDecision($booking1);
        NotificationService::notifyBookingDecision($booking2);

        $this->assertSame(2, Notification::forUser($ekraf->id)->unread()->count());

        $this->actingAs($ekraf)
            ->patch('/notifikasi/' . $first->id)
            ->assertRedirect(route('notifikasi.index'));

        $first->refresh();
        $this->assertNotNull($first->read_at);
        $firstReadTimestamp = $first->read_at->toDateTimeString();
        $this->assertSame(1, Notification::forUser($ekraf->id)->unread()->count());

        // Penandaan dibaca berulang tetap konsisten dan tidak menimpa waktu baca awal
        $this->travel(5)->minutes();
        $this->actingAs($ekraf)
            ->patch('/notifikasi/' . $first->id)
            ->assertRedirect(route('notifikasi.index'));

        $first->refresh();
        $this->assertSame($firstReadTimestamp, $first->read_at->toDateTimeString());
        $this->assertSame(1, Notification::forUser($ekraf->id)->unread()->count());
        $this->assertSame(2, Notification::forUser($ekraf->id)->count());
    }

    public function test_filter_unread_empty_state_and_pagination_work(): void
    {
        $room = $this->createRoom();
        $ekraf = $this->createUser('ekraf@sekata.test', 'Bidang Ekraf');
        $pengkeima = $this->createUser('pengkeima@sekata.test', 'Bidang Pengkeima');

        for ($i = 1; $i <= 12; $i++) {
            $booking = $this->createBooking($ekraf, $room, [
                'status' => 'Disetujui',
                'decision_reason' => 'Keputusan #' . $i,
            ]);
            $notif = NotificationService::notifyBookingDecision($booking);

            if ($i === 1) {
                $notif->markAsRead();
            }
        }

        $page1 = $this->actingAs($ekraf)->get('/notifikasi');
        $page1->assertOk()->assertSee('Semua (12)')->assertSee('Belum dibaca (11)');
        $this->assertCount(10, $page1->viewData('notifications'));

        $page2 = $this->actingAs($ekraf)->get('/notifikasi?page=2');
        $page2->assertOk();
        $this->assertCount(2, $page2->viewData('notifications'));

        $unreadOnly = $this->actingAs($ekraf)->get('/notifikasi?filter=unread');
        $unreadOnly->assertOk()->assertSee('Belum dibaca (11)');

        $this->actingAs($pengkeima)
            ->get('/notifikasi')
            ->assertOk()
            ->assertSee('Belum ada notifikasi');
    }

    public function test_rolled_back_transaction_does_not_leave_fake_notifications(): void
    {
        $room = $this->createRoom();
        $this->createUser('sppi@sekata.test', 'Biro SPPI', 'biro', 'admin');
        $ekraf = $this->createUser('ekraf@sekata.test', 'Bidang Ekraf');

        // Rollback saat submit pengajuan baru
        try {
            DB::transaction(function () use ($ekraf, $room) {
                $booking = $this->createBooking($ekraf, $room, ['status' => 'Menunggu']);
                NotificationService::notifyBookingCreated($booking);

                throw new RuntimeException('Simulasi gagal transaksi submit');
            });
        } catch (RuntimeException) {
            // Diharapkan terjadi rollback
        }

        $this->assertSame(0, Booking::count());
        $this->assertSame(0, Notification::count());

        // Rollback saat perubahan keputusan SPPI
        $existing = $this->createBooking($ekraf, $room, ['status' => 'Menunggu']);

        try {
            DB::transaction(function () use ($existing) {
                $existing->update([
                    'status' => 'Disetujui',
                    'decision_reason' => 'Akan di-rollback',
                ]);
                NotificationService::notifyBookingDecision($existing);

                throw new RuntimeException('Simulasi gagal transaksi keputusan');
            });
        } catch (RuntimeException) {
            // Diharapkan terjadi rollback
        }

        $this->assertSame('Menunggu', $existing->fresh()->status);
        $this->assertSame(0, Notification::count());
    }

    public function test_invalid_or_non_occurring_transitions_do_not_create_extra_notifications(): void
    {
        $room = $this->createRoom();
        $this->createUser('sppi@sekata.test', 'Biro SPPI', 'biro', 'admin');
        $ekraf = $this->createUser('ekraf@sekata.test', 'Bidang Ekraf');

        $pending = $this->createBooking($ekraf, $room, ['status' => 'Menunggu']);
        $approved = $this->createBooking($ekraf, $room, ['status' => 'Disetujui']);

        // Status tidak sesuai kontrak ditolak tanpa membuat notifikasi
        try {
            NotificationService::notifyBookingCreated($approved);
            $this->fail('Seharusnya menolak status bukan Menunggu.');
        } catch (InvalidArgumentException) {
        }

        try {
            NotificationService::notifyBookingDecision($pending);
            $this->fail('Seharusnya menolak status Menunggu untuk keputusan.');
        } catch (InvalidArgumentException) {
        }

        try {
            NotificationService::notifyProofReviewed($approved);
            $this->fail('Seharusnya menolak status Disetujui untuk pemeriksaan bukti.');
        } catch (InvalidArgumentException) {
        }

        $this->assertSame(0, Notification::count());

        // Simulasi keputusan berulang: hanya transisi pertama (Menunggu -> Disetujui) yang memicu notifikasi
        $decideOnce = function (Booking $booking): bool {
            return DB::transaction(function () use ($booking) {
                $fresh = Booking::findOrFail($booking->id);

                if ($fresh->status !== 'Menunggu') {
                    return false;
                }

                $fresh->update([
                    'status' => 'Disetujui',
                    'decision_reason' => 'Disetujui pertama kali.',
                ]);
                NotificationService::notifyBookingDecision($fresh);

                return true;
            });
        };

        $this->assertTrue($decideOnce($pending));
        $this->assertFalse($decideOnce($pending));
        $this->assertSame(1, Notification::count());
    }

    public function test_sppi_decision_via_patch_route_creates_one_notification_for_owner(): void
    {
        $room = $this->createRoom();
        $sppi = $this->createUser('sppi@sekata.test', 'Biro SPPI', 'biro', 'admin');
        $ekraf = $this->createUser('ekraf@sekata.test', 'Bidang Ekraf');
        $pengkeima = $this->createUser('pengkeima@sekata.test', 'Bidang Pengkeima');

        $approvedBooking = $this->createBooking($ekraf, $room, ['status' => 'Menunggu']);

        $this->actingAs($sppi)
            ->patch(route('admin.peminjaman.decide', $approvedBooking->id), [
                'status' => 'Disetujui',
                'decision_reason' => 'Jadwal sesuai dan slot tersedia.',
            ])
            ->assertRedirect(route('peminjaman.show', $approvedBooking->id));

        $this->assertSame('Disetujui', $approvedBooking->fresh()->status);
        $this->assertSame(1, Notification::count());
        $this->assertDatabaseHas('notifications', [
            'user_id' => $ekraf->id,
            'booking_id' => $approvedBooking->id,
            'type' => Notification::TYPE_BOOKING_APPROVED,
            'read_at' => null,
        ]);
        $this->assertSame(0, Notification::forUser($pengkeima->id)->count());
        $this->assertSame(0, Notification::forUser($sppi->id)->count());

        // Uji juga keputusan Ditolak melalui route PATCH yang sama
        $rejectedBooking = $this->createBooking($ekraf, $room, [
            'tanggal' => '2026-11-27',
            'status' => 'Menunggu',
        ]);

        $this->actingAs($sppi)
            ->patch(route('admin.peminjaman.decide', $rejectedBooking->id), [
                'status' => 'Ditolak',
                'decision_reason' => 'Bentrok agenda BEM.',
            ])
            ->assertRedirect(route('peminjaman.show', $rejectedBooking->id));

        $this->assertSame('Ditolak', $rejectedBooking->fresh()->status);
        $this->assertSame(2, Notification::count());
        $this->assertDatabaseHas('notifications', [
            'user_id' => $ekraf->id,
            'booking_id' => $rejectedBooking->id,
            'type' => Notification::TYPE_BOOKING_REJECTED,
        ]);
    }

    public function test_repeated_decision_via_patch_route_does_not_add_extra_notification(): void
    {
        $room = $this->createRoom();
        $sppi = $this->createUser('sppi@sekata.test', 'Biro SPPI', 'biro', 'admin');
        $ekraf = $this->createUser('ekraf@sekata.test', 'Bidang Ekraf');

        $booking = $this->createBooking($ekraf, $room, ['status' => 'Menunggu']);

        $this->actingAs($sppi)
            ->patch(route('admin.peminjaman.decide', $booking->id), [
                'status' => 'Disetujui',
                'decision_reason' => 'Keputusan pertama.',
            ])
            ->assertRedirect(route('peminjaman.show', $booking->id));

        $this->assertSame(1, Notification::count());

        $this->actingAs($sppi)
            ->patch(route('admin.peminjaman.decide', $booking->id), [
                'status' => 'Ditolak',
                'decision_reason' => 'Percobaan keputusan ulang.',
            ])
            ->assertSessionHasErrors('decision');

        $this->assertSame('Disetujui', $booking->fresh()->status);
        $this->assertSame(1, Notification::count());
    }

    public function test_invalid_decision_via_patch_route_does_not_create_notification(): void
    {
        $room = $this->createRoom();
        $sppi = $this->createUser('sppi@sekata.test', 'Biro SPPI', 'biro', 'admin');
        $ekraf = $this->createUser('ekraf@sekata.test', 'Bidang Ekraf');

        $booking = $this->createBooking($ekraf, $room, ['status' => 'Menunggu']);

        // Alasan kosong ditolak validasi
        $this->actingAs($sppi)
            ->patch(route('admin.peminjaman.decide', $booking->id), [
                'status' => 'Disetujui',
                'decision_reason' => '',
            ])
            ->assertSessionHasErrors('decision_reason');

        // Status tidak valid ditolak validasi
        $this->actingAs($sppi)
            ->patch(route('admin.peminjaman.decide', $booking->id), [
                'status' => 'Menunggu',
                'decision_reason' => 'Status tidak valid.',
            ])
            ->assertSessionHasErrors('status');

        // Unit non-admin ditolak middleware role:admin
        $this->actingAs($ekraf)
            ->patch(route('admin.peminjaman.decide', $booking->id), [
                'status' => 'Disetujui',
                'decision_reason' => 'Coba akses admin.',
            ])
            ->assertForbidden();

        $this->assertSame('Menunggu', $booking->fresh()->status);
        $this->assertSame(0, Notification::count());
    }

    public function test_notification_detail_link_uses_peminjaman_show_route_and_enforces_authorization(): void
    {
        $room = $this->createRoom();
        $sppi = $this->createUser('sppi@sekata.test', 'Biro SPPI', 'biro', 'admin');
        $ekraf = $this->createUser('ekraf@sekata.test', 'Bidang Ekraf');
        $pengkeima = $this->createUser('pengkeima@sekata.test', 'Bidang Pengkeima');

        $booking = $this->createBooking($ekraf, $room, [
            'status' => 'Disetujui',
            'decision_reason' => 'Disetujui untuk rapat.',
        ]);
        $notification = NotificationService::notifyBookingDecision($booking);

        $this->assertSame(route('peminjaman.show', $booking->id), $notification->detailUrl());

        $this->actingAs($ekraf)
            ->get($notification->detailUrl())
            ->assertOk();

        $this->actingAs($sppi)
            ->get($notification->detailUrl())
            ->assertOk();

        $this->actingAs($pengkeima)
            ->get($notification->detailUrl())
            ->assertForbidden();
    }

    private function attachCompleteProofs(Booking $booking): void
    {
        foreach (['ruangan', 'kunci'] as $category) {
            $booking->proofs()->create([
                'category' => $category,
                'path' => "booking-proofs/{$booking->id}/{$category}.jpg",
                'original_name' => "{$category}.jpg",
                'mime_type' => 'image/jpeg',
                'size' => 1024,
                'uploaded_at' => now(),
                'requires_replacement' => false,
            ]);
        }
    }

    public function test_proof_inspection_perlu_perbaikan_via_endpoint_creates_one_notification_for_owner(): void
    {
        $room = $this->createRoom();
        $sppi = $this->createUser('sppi@sekata.test', 'Biro SPPI', 'biro', 'admin');
        $ekraf = $this->createUser('ekraf@sekata.test', 'Bidang Ekraf');
        $pengkeima = $this->createUser('pengkeima@sekata.test', 'Bidang Pengkeima');

        $booking = $this->createBooking($ekraf, $room, ['status' => 'Periksa Bukti']);
        $this->attachCompleteProofs($booking);

        $this->actingAs($sppi)
            ->post(route('admin.peminjaman.pemeriksaan.store', $booking), [
                'decision' => 'perbaikan',
                'inspection_note' => 'Foto kunci buram, mohon unggah ulang.',
                'requested_category' => 'kunci',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Perlu Perbaikan', $booking->fresh()->status);
        $this->assertSame(1, Notification::count());

        $notification = Notification::firstOrFail();
        $this->assertSame($ekraf->id, $notification->user_id);
        $this->assertSame($booking->id, $notification->booking_id);
        $this->assertSame(Notification::TYPE_PROOF_REVISION_REQUESTED, $notification->type);
        $this->assertStringContainsString('Foto kunci buram, mohon unggah ulang.', $notification->message);
        $this->assertSame(route('peminjaman.show', $booking->id), $notification->detailUrl());
        $this->assertSame(0, Notification::forUser($pengkeima->id)->count());

        $this->actingAs($ekraf)
            ->get($notification->detailUrl())
            ->assertOk();
    }

    public function test_proof_inspection_selesai_via_endpoint_creates_one_notification_for_owner(): void
    {
        $room = $this->createRoom();
        $sppi = $this->createUser('sppi@sekata.test', 'Biro SPPI', 'biro', 'admin');
        $ekraf = $this->createUser('ekraf@sekata.test', 'Bidang Ekraf');

        $booking = $this->createBooking($ekraf, $room, ['status' => 'Periksa Bukti']);
        $this->attachCompleteProofs($booking);

        $this->actingAs($sppi)
            ->post(route('admin.peminjaman.pemeriksaan.store', $booking), [
                'decision' => 'selesai',
                'inspection_note' => 'Ruangan bersih dan kunci telah kembali.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Selesai', $booking->fresh()->status);
        $this->assertSame(1, Notification::count());

        $notification = Notification::firstOrFail();
        $this->assertSame($ekraf->id, $notification->user_id);
        $this->assertSame($booking->id, $notification->booking_id);
        $this->assertSame(Notification::TYPE_PROOF_COMPLETED, $notification->type);
        $this->assertStringContainsString('Ruangan bersih dan kunci telah kembali.', $notification->message);
        $this->assertSame(route('peminjaman.show', $booking->id), $notification->detailUrl());

        $this->actingAs($ekraf)
            ->get($notification->detailUrl())
            ->assertOk();
    }

    public function test_invalid_or_repeated_proof_inspection_via_endpoint_does_not_add_notification(): void
    {
        $room = $this->createRoom();
        $sppi = $this->createUser('sppi@sekata.test', 'Biro SPPI', 'biro', 'admin');
        $ekraf = $this->createUser('ekraf@sekata.test', 'Bidang Ekraf');

        $booking = $this->createBooking($ekraf, $room, ['status' => 'Periksa Bukti']);
        $this->attachCompleteProofs($booking);

        // Permintaan perbaikan tanpa catatan ditolak validasi dan tidak membuat notifikasi
        $this->actingAs($sppi)
            ->post(route('admin.peminjaman.pemeriksaan.store', $booking), [
                'decision' => 'perbaikan',
                'inspection_note' => '',
                'requested_category' => 'kunci',
            ])
            ->assertSessionHasErrors('inspection_note');

        $this->assertSame(0, Notification::count());

        // Pemeriksaan pertama menjadi Perlu Perbaikan menghasilkan 1 notifikasi
        $this->actingAs($sppi)
            ->post(route('admin.peminjaman.pemeriksaan.store', $booking), [
                'decision' => 'perbaikan',
                'inspection_note' => 'Perbaiki foto kunci.',
                'requested_category' => 'kunci',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Perlu Perbaikan', $booking->fresh()->status);
        $this->assertSame(1, Notification::count());

        // Pemeriksaan ulang yang tidak valid (status masih Perlu Perbaikan) tidak menambah notifikasi
        $this->actingAs($sppi)
            ->post(route('admin.peminjaman.pemeriksaan.store', $booking), [
                'decision' => 'selesai',
            ])
            ->assertSessionHasErrors('booking');

        $this->assertSame('Perlu Perbaikan', $booking->fresh()->status);
        $this->assertSame(1, Notification::count());
    }
}
