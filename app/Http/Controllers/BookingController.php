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

            // Kunci baris ruang sekretariat agar pengajuan
            // lain harus menunggu sebelum mengecek jadwal.
            $room = Room::query()
                ->where('name', 'Sekretariat BEM UPNVJ')
                ->lockForUpdate()
                ->first();

            if (! $room) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Data ruang sekretariat belum tersedia. Silakan hubungi administrator.',
                ]);
            }

            // Cek jadwal yang masih aktif.
            // Jadwal Ditolak tidak dianggap bentrok.
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

            // Simpan booking menggunakan ID akun yang sedang login.
            $booking = Booking::create([
                'user_id' => $request->user()->id,
                'pic_name' => $data['pic_name'],
                'activity_description' => $data['activity_description'],
                'tanggal' => $data['tanggal'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => 'Menunggu',
            ]);

            // Kirim notifikasi menggunakan service milik Adis.
            NotificationService::notifyBookingCreated($booking);
        }, 3);

        return redirect()
            ->route('peminjaman.index')
            ->with('success', 'Pengajuan peminjaman berhasil dikirim.');
    }
}
