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
            // Semua pengajuan memakai baris sekretariat yang sama sebagai pengunci.
            $room = Room::where('name', 'Sekretariat BEM UPNVJ')->lockForUpdate()->firstOrFail();

            $overlap = Booking::where('room_id', $room->id)
                ->whereDate('tanggal', $data['tanggal'])
                ->whereIn('status', ['Menunggu', 'Disetujui'])
                ->where('start_time', '<', $data['end_time'] . ':00')
                ->where('end_time', '>', $data['start_time'] . ':00')
                ->lockForUpdate()
                ->first();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'tanggal' => 'Jadwal tidak tersedia. Pilih tanggal atau jam lain.',
                ]);
            }

            $booking = Booking::create([
                'user_id' => $request->user()->id,
                'room_id' => $room->id,
                'pic_division' => $request->user()->unit_name,
                'pic_name' => $data['pic_name'],
                'activity_description' => $data['activity_description'],
                'tanggal' => $data['tanggal'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => 'Menunggu',
            ]);

            NotificationService::notifyBookingCreated($booking);
        });

        return redirect()->route('peminjaman.index')
            ->with('success', 'Pengajuan peminjaman berhasil dikirim.');
    }
}
