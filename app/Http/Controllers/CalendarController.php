<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        // Get the selected week from the URL.
        $selectedDate = $request->query('week');

        // Validate the selected date.
        if ($selectedDate) {
            try {
                $date = Carbon::createFromFormat('!Y-m-d', $selectedDate);

                if (!$date || $date->format('Y-m-d') !== $selectedDate) {
                    abort(400, 'Tanggal minggu tidak valid.');
                }
            } catch (\Exception $e) {
                abort(400, 'Tanggal minggu tidak valid.');
            }
        } else {
            $date = Carbon::today();
        }

        // Get the start and end dates of the selected week.
        $weekStart = $date->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        // Get all units from the users table.
        $units = User::query()
            ->whereNotNull('unit_name')
            ->where('unit_name', '!=', '')
            ->select('unit_name', 'unit_type')
            ->distinct()
            ->orderBy('unit_name')
            ->get();

        // Get bookings within the selected week.
        $bookings = Booking::with(['user'])
            ->whereIn('status', ['Menunggu', 'Disetujui'])
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
