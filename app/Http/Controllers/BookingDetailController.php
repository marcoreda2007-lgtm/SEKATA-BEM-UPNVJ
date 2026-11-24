<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingDetailController extends Controller
{
    public function show(Request $request, int $id): View
    {
        $booking = Booking::with(['user', 'room', 'decider'])->findOrFail($id);

        $currentUser = $request->user();

        if (! $currentUser->isAdmin() && $booking->user_id !== $currentUser->id) {
            abort(403, 'Anda tidak memiliki izin untuk melihat detail peminjaman unit lain.');
        }

        return view('peminjaman.show', [
            'booking' => $booking,
        ]);
    }
}