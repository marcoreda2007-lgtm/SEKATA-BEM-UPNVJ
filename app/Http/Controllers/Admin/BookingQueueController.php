<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingQueueController extends Controller
{
    public function index()
    {
        $bookings = Booking::with('user')
            ->where('status', 'Menunggu')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
        ]);
    }

    public function decide(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:Disetujui,Ditolak',
            'decision_reason' => 'required|string|max:1000',
        ]);

        DB::transaction(function () use ($id, $data, $request) {
            // Lock the shared SPPI admin row.
            $admin = DB::table('users')
                ->where('role', 'admin')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$admin) {
                throw ValidationException::withMessages([
                    'decision' => 'Akun admin SPPI tidak ditemukan.',
                ]);
            }

            $booking = Booking::where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->status !== 'Menunggu') {
                throw ValidationException::withMessages([
                    'decision' => 'Pengajuan ini sudah diproses. Muat ulang halaman.',
                ]);
            }

            $booking->status = $data['status'];
            $booking->decision_reason = $data['decision_reason'];
            $booking->decided_by = $request->user()->id;
            $booking->decided_at = now();
            $booking->save();

            NotificationService::notifyBookingDecision($booking);
        });

        return redirect()->route('peminjaman.show', $id)
            ->with('success', 'Keputusan berhasil disimpan.');
    }
}
