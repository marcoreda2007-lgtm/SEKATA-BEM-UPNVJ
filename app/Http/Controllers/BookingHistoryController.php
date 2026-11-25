<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with('user');

        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        $bookings = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('peminjaman.index', ['bookings' => $bookings]);
    }
}
