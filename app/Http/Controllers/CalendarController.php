<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index()
    {
        // Get the start and end dates of the current week.
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        // Get all units from the users table.
        $units = User::query()
            ->whereNotNull('unit_name')
            ->where('unit_name', '!=', '')
            ->select('unit_name', 'unit_type')
            ->distinct()
            ->orderBy('unit_name')
            ->get();

        // Get bookings within the current week.
        $bookings = Booking::with(['user', 'room'])
            ->whereBetween('tanggal', [
                $weekStart->toDateString(),
                $weekEnd->toDateString()
            ])
            ->orderBy('tanggal')
            ->orderBy('start_time')
            ->get();

        // Send the data to the calendar page.
        return view('calendar.index', compact(
            'units',
            'bookings',
            'weekStart',
            'weekEnd'
        ));
    }
}
