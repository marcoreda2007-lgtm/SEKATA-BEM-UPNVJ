<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function create()
    {
        return view('bookings.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pic_name' => 'required|string|max:255',
            'activity_description' => 'required|string',
            'tanggal' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'responsibility_acknowledgement' => 'accepted',
        ], [
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
            'responsibility_acknowledgement.accepted' => 'Pernyataan tanggung jawab harus disetujui.',
        ]);

        DB::transaction(function () use ($request, $data) {
            $this->lockSecretariatRoom();

            $hasConflict = Booking::query()
                ->whereDate('tanggal', $data['tanggal'])
                ->whereIn('status', ['Menunggu', 'Disetujui'])
                ->where('start_time', '<', $data['end_time'])
                ->where('end_time', '>', $data['start_time'])
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Jadwal sekretariat sudah digunakan pada waktu tersebut. Silakan pilih tanggal atau jam lain.',
                ]);
            }

            $booking = Booking::create([
                'user_id' => $request->user()->id,
                'pic_name' => $data['pic_name'],
                'activity_description' => $data['activity_description'],
                'tanggal' => $data['tanggal'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => 'Menunggu',
            ]);

            NotificationService::notifyBookingCreated($booking);
        }, 3);

        return redirect()
            ->route('peminjaman.index')
            ->with('success', 'Pengajuan peminjaman berhasil dikirim.');
    }

    public function edit(Request $request, int $id)
    {
        $booking = Booking::findOrFail($id);

        $this->authorizeBookingManagement($booking, $request);

        return view('bookings.edit', compact('booking'));
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'pic_name' => 'required|string|max:255',
            'activity_description' => 'required|string',
            'tanggal' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'responsibility_acknowledgement' => 'accepted',
        ], [
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
            'responsibility_acknowledgement.accepted' => 'Pernyataan tanggung jawab harus disetujui.',
        ]);

        DB::transaction(function () use ($request, $id, $data) {
            $this->lockSecretariatRoom();

            $booking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($id);

            $this->authorizeBookingManagement($booking, $request);

            $hasConflict = Booking::query()
                ->where('id', '!=', $booking->id)
                ->whereDate('tanggal', $data['tanggal'])
                ->whereIn('status', ['Menunggu', 'Disetujui'])
                ->where('start_time', '<', $data['end_time'])
                ->where('end_time', '>', $data['start_time'])
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Jadwal sekretariat sudah digunakan pada waktu tersebut. Silakan pilih tanggal atau jam lain.',
                ]);
            }

            $booking->update([
                'pic_name' => $data['pic_name'],
                'activity_description' => $data['activity_description'],
                'tanggal' => $data['tanggal'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
            ]);
        }, 3);

        return redirect()
            ->route('peminjaman.show', $id)
            ->with('success', 'Pengajuan peminjaman berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id)
    {
        DB::transaction(function () use ($request, $id) {
            $this->lockSecretariatRoom();

            $booking = Booking::query()
                ->lockForUpdate()
                ->findOrFail($id);

            $this->authorizeBookingManagement($booking, $request);

            $booking->delete();
        }, 3);

        return redirect()
            ->route('peminjaman.index')
            ->with('success', 'Pengajuan peminjaman berhasil dihapus.');
    }

    private function lockSecretariatRoom(): void
    {
        $room = Room::query()
            ->where('name', 'Sekretariat BEM UPNVJ')
            ->lockForUpdate()
            ->first();

        if (! $room) {
            throw ValidationException::withMessages([
                'tanggal' => 'Data ruang sekretariat belum tersedia. Silakan hubungi administrator.',
            ]);
        }
    }

    private function authorizeBookingManagement(
        Booking $booking,
        Request $request
    ): void {
        if (
            $request->user()->isAdmin()
            || (int) $booking->user_id !== (int) $request->user()->id
        ) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah pengajuan ini.');
        }

        if ($booking->status !== 'Menunggu') {
            abort(403, 'Pengajuan hanya dapat diedit atau dihapus selama status masih Menunggu.');
        }
    }
}