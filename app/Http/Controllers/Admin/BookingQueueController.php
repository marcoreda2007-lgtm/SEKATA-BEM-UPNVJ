<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;

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
}