
@extends('layouts.app')

@section('title', 'Jadwal Sekretariat')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-800">
            Calendar
        </h1>
        <p class="text-sm text-gray-500">
            Weekly room booking schedule
        </p>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow">
        <table class="w-full min-w-[1000px] border-collapse text-sm">

            <thead>
                <tr class="bg-gray-100">
                    <th class="border p-3 text-left">Unit</th>

                    @for ($day = $weekStart->copy(); $day->lte($weekEnd); $day->addDay())
                        <th class="border p-3 text-center">
                            <div>{{ $day->format('D') }}</div>
                            <div class="text-gray-500">
                                {{ $day->format('d M') }}
                            </div>
                        </th>
                    @endfor
                </tr>
            </thead>

            <tbody>
                @forelse ($units as $unit)
                    <tr>
                        <td class="border p-3 font-semibold">
                            {{ $unit->unit_name }}
                        </td>

                        @for ($day = $weekStart->copy(); $day->lte($weekEnd); $day->addDay())
                            @php
                                $dayBookings = $bookings->filter(function ($booking) use ($unit, $day) {
                                    return $booking->user
                                        && $booking->user->unit_name === $unit->unit_name
                                        && $booking->tanggal->toDateString() === $day->toDateString();
                                });
                            @endphp

                            <td class="border p-2 align-top">
                                @forelse ($dayBookings as $booking)
                                    <div class="mb-2 rounded-lg bg-blue-100 p-2 text-blue-800">
                                        <p class="font-semibold">
                                            {{ substr($booking->start_time, 0, 5) }}
                                            -
                                            {{ substr($booking->end_time, 0, 5) }}
                                        </p>

                                        <p class="mt-1">
                                            {{ $booking->activity_description }}
                                        </p>

                                        <p class="mt-1 text-xs">
                                            {{ $booking->status }}
                                        </p>
                                    </div>
                                @empty
                                    <span class="text-gray-300">-</span>
                                @endforelse
                            </td>
                        @endfor
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="border p-4 text-center text-gray-500">
                            No units available.
                        </td>
                    </tr>
                @endforelse
            </tbody>

        </table>
    </div>

</div>
@endsection