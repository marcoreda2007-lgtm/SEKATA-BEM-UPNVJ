<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

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

        Booking::create([
            'user_id' => $request->user()->id,
            'pic_name' => $data['pic_name'],
            'activity_description' => $data['activity_description'],
            'tanggal' => $data['tanggal'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => 'Menunggu',
        ]);

        return redirect()->route('peminjaman.index')
            ->with('success', 'Pengajuan peminjaman berhasil dikirim.');
    }
}
