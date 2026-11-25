<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ItemDeposit;
use App\Models\ItemDepositPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItemDepositTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_or_submit_deposits(): void
    {
        $this->get(route('penitipan.index'))->assertRedirect(route('login'));
        $this->post(route('penitipan.store'))->assertRedirect(route('login'));
    }

    public function test_upload_saves_five_private_photos_and_ignores_forged_owner_and_status(): void
    {
        Storage::fake('local');
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $data = $this->submission(5);
        $data['user_id'] = $other->id;
        $data['status'] = 'Diambil';
        $data['collected_at'] = now();

        $this->actingAs($owner)->post(route('penitipan.store'), $data)
            ->assertRedirect(route('penitipan.index'))->assertSessionHasNoErrors();

        $deposit = ItemDeposit::firstOrFail();
        $this->assertSame($owner->id, $deposit->user_id);
        $this->assertSame('Dititipkan', $deposit->status);
        $this->assertNull($deposit->collected_at);
        $this->assertCount(5, $deposit->photos);
        foreach ($deposit->photos as $photo) {
            Storage::disk('local')->assertExists($photo->path);
        }
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_missing_six_oversized_and_non_image_photos_are_rejected(): void
    {
        Storage::fake('local');
        $this->actingAs($this->makeUser());
        $this->post(route('penitipan.store'), $this->submission(0))->assertSessionHasErrors('photos');
        $this->post(route('penitipan.store'), $this->submission(6))->assertSessionHasErrors('photos');

        $data = $this->submission();
        $data['photos'] = [UploadedFile::fake()->create('barang.pdf', 10, 'application/pdf')];
        $this->post(route('penitipan.store'), $data)->assertSessionHasErrors('photos.0');

        $data['photos'] = [UploadedFile::fake()->createWithContent('besar.png', $this->png())->size(5121)];
        $this->post(route('penitipan.store'), $data)->assertSessionHasErrors('photos.0');
        $this->assertDatabaseCount('item_deposits', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_only_owner_and_admin_can_view_deposit_and_private_photo(): void
    {
        Storage::fake('local');
        $owner = $this->makeUser();
        $this->actingAs($owner)->post(route('penitipan.store'), $this->submission())
            ->assertSessionHasNoErrors();
        $photo = ItemDepositPhoto::firstOrFail();

        $this->get(route('penitipan.index'))->assertOk()->assertSee('Kotak uji penitipan');
        $this->get(route('penitipan.photo', $photo))->assertOk();

        $this->actingAs($this->makeUser());
        $this->get(route('penitipan.index'))->assertOk()->assertDontSee('Kotak uji penitipan');
        $this->get(route('penitipan.photo', $photo))->assertForbidden();

        $this->actingAs($this->makeUser('admin'));
        $this->get(route('penitipan.index'))->assertOk()->assertSee('Kotak uji penitipan')
            ->assertDontSee('Simpan penitipan');
        $this->get(route('penitipan.photo', $photo))->assertOk();
        $this->post(route('penitipan.store'), $this->submission())->assertForbidden();
        $this->assertDatabaseCount('item_deposits', 1);
    }

    public function test_failed_photo_record_rolls_back_deposit_and_removes_uploaded_file(): void
    {
        Storage::fake('local');
        ItemDepositPhoto::creating(function () {
            throw new \RuntimeException('Simulasi gagal menyimpan foto');
        });

        try {
            $this->actingAs($this->makeUser())->post(route('penitipan.store'), $this->submission())
                ->assertStatus(500);
        } finally {
            ItemDepositPhoto::flushEventListeners();
        }

        $this->assertDatabaseCount('item_deposits', 0);
        $this->assertDatabaseCount('item_deposit_photos', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_owner_can_confirm_collection_once_and_keep_photos_in_history(): void
    {
        Storage::fake('local');
        $owner = $this->makeUser();
        $this->actingAs($owner)->post(route('penitipan.store'), $this->submission())
            ->assertSessionHasNoErrors();
        $deposit = ItemDeposit::firstOrFail();
        $photo = $deposit->photos->first();

        $this->get(route('penitipan.index'))->assertSee('Konfirmasi pengambilan');
        $this->travelTo(now()->startOfSecond());
        $collectedAt = now();
        $this->patch(route('penitipan.collect', $deposit), ['confirmed' => '1'])
            ->assertRedirect(route('penitipan.index'))->assertSessionHasNoErrors();

        $deposit->refresh();
        $this->assertSame('Diambil', $deposit->status);
        $this->assertTrue($deposit->collected_at->equalTo($collectedAt));
        Storage::disk('local')->assertExists($photo->path);
        $this->get(route('penitipan.photo', $photo))->assertOk();
        $this->get(route('penitipan.index'))->assertSee('Diambil')
            ->assertSee('Dilaporkan oleh penitip.')->assertDontSee('Konfirmasi pengambilan');

        $this->travel(5)->minutes();
        $this->patch(route('penitipan.collect', $deposit), ['confirmed' => '1'])
            ->assertSessionHasErrors('collection');
        $this->assertTrue($deposit->fresh()->collected_at->equalTo($collectedAt));
        $this->assertDatabaseCount('item_deposits', 1);
        $this->assertDatabaseCount('item_deposit_photos', 1);
    }

    public function test_collection_requires_confirmation_and_rejects_other_units_admin_and_guest(): void
    {
        Storage::fake('local');
        $owner = $this->makeUser();
        $this->actingAs($owner)->post(route('penitipan.store'), $this->submission());
        $deposit = ItemDeposit::firstOrFail();
        $url = route('penitipan.collect', $deposit);

        $this->patch($url)->assertSessionHasErrors('confirmed');
        $this->patch($url, ['confirmed' => '0'])->assertSessionHasErrors('confirmed');
        $this->actingAs($this->makeUser())->patch($url, ['confirmed' => '1'])->assertForbidden();
        $this->actingAs($this->makeUser('admin'));
        $this->get(route('penitipan.index'))->assertDontSee('Konfirmasi pengambilan');
        $this->patch($url, ['confirmed' => '1'])->assertForbidden();
        auth()->logout();
        $this->patch($url, ['confirmed' => '1'])->assertRedirect(route('login'));

        $this->assertSame('Dititipkan', $deposit->fresh()->status);
        $this->assertNull($deposit->fresh()->collected_at);
    }

    public function test_archiving_hides_collected_deposit_from_owner_but_keeps_sppi_record_and_photo(): void
    {
        Storage::fake('local');
        $owner = $this->makeUser();
        $this->actingAs($owner)->post(route('penitipan.store'), $this->submission());
        $deposit = ItemDeposit::firstOrFail();
        $photo = $deposit->photos->first();
        $this->patch(route('penitipan.collect', $deposit), ['confirmed' => '1']);
        $this->get(route('penitipan.index'))->assertSee('Hapus dari daftar');

        $url = route('penitipan.archive', $deposit);
        $this->patch($url)->assertSessionHasErrors('confirmed');
        $this->assertNull($deposit->fresh()->archived_at);
        $this->travelTo(now()->startOfSecond());
        $archivedAt = now();
        $this->patch($url, ['confirmed' => '1'])->assertRedirect(route('penitipan.index'))
            ->assertSessionHasNoErrors();
        $this->assertTrue($deposit->fresh()->archived_at->equalTo($archivedAt));
        $this->get(route('penitipan.index'))->assertDontSee('Kotak uji penitipan');

        $this->travel(5)->minutes();
        $this->patch($url, ['confirmed' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue($deposit->fresh()->archived_at->equalTo($archivedAt));

        $this->actingAs($this->makeUser('admin'));
        $this->get(route('penitipan.index'))->assertSee('Kotak uji penitipan')
            ->assertSee('Sudah dihapus dari daftar pemilik.')->assertDontSee('Hapus dari daftar');
        $this->get(route('penitipan.photo', $photo))->assertOk();
        Storage::disk('local')->assertExists($photo->path);
        $this->assertDatabaseCount('item_deposits', 1);
        $this->assertDatabaseCount('item_deposit_photos', 1);
        $this->assertSame('Diambil', $deposit->fresh()->status);
    }

    public function test_archiving_rejects_active_deposits_other_units_admin_and_guest(): void
    {
        Storage::fake('local');
        $owner = $this->makeUser();
        $this->actingAs($owner)->post(route('penitipan.store'), $this->submission());
        $deposit = ItemDeposit::firstOrFail();
        $url = route('penitipan.archive', $deposit);

        $this->get(route('penitipan.index'))->assertDontSee('Hapus dari daftar');
        $this->patch($url, ['confirmed' => '1'])->assertSessionHasErrors('archive');
        $this->patch(route('penitipan.collect', $deposit), ['confirmed' => '1']);
        $this->actingAs($this->makeUser())->patch($url, ['confirmed' => '1'])->assertForbidden();
        $this->actingAs($this->makeUser('admin'))->patch($url, ['confirmed' => '1'])->assertForbidden();
        auth()->logout();
        $this->patch($url, ['confirmed' => '1'])->assertRedirect(route('login'));
        $this->assertNull($deposit->fresh()->archived_at);
    }

    private function makeUser(string $role = 'unit'): User
    {
        return User::factory()->create([
            'unit_name' => $role === 'admin' ? 'Biro SPPI' : 'Bidang Ekraf',
            'unit_type' => $role === 'admin' ? 'biro' : 'bidang',
            'role' => $role,
        ]);
    }

    private function submission(int $count = 1): array
    {
        $photos = [];
        for ($i = 0; $i < $count; $i++) {
            $photos[] = UploadedFile::fake()->createWithContent("barang-{$i}.png", $this->png());
        }

        return [
            'depositor_name' => 'Reda',
            'item_name' => 'Kotak uji penitipan',
            'description' => 'Satu kotak alat tulis.',
            'deposited_on' => '2026-11-25',
            'photos' => $photos,
        ];
    }

    private function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    }

    public function test_deposit_can_have_photos_without_a_room_booking(): void
    {
        $owner = User::factory()->create([
            'unit_name' => 'Bidang Ekraf',
            'unit_type' => 'bidang',
            'role' => 'unit',
        ]);

        $deposit = $owner->itemDeposits()->create([
            'depositor_name' => 'Reda',
            'item_name' => 'Kotak perlengkapan',
            'description' => 'Satu kotak berisi alat tulis kegiatan.',
            'deposited_on' => '2026-11-25',
        ]);

        $photo = $deposit->photos()->create([
            'path' => 'item-deposits/example.jpg',
            'original_name' => 'kotak.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $deposit->refresh();
        $this->assertSame('Dititipkan', $deposit->status);
        $this->assertNull($deposit->collected_at);
        $this->assertTrue($deposit->user->is($owner));
        $this->assertTrue($deposit->photos->first()->is($photo));
        $this->assertTrue($photo->itemDeposit->is($deposit));
        $this->assertSame('2026-11-25', $deposit->deposited_on->format('Y-m-d'));
        $this->assertDatabaseCount('bookings', 0);
    }
}
