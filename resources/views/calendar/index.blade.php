
@extends('layouts.app')

@section('title', 'Jadwal Sekretariat')

@section('content')
<div class="container-fluid py-4">

    {{-- Calendar heading --}}
    <div class="mb-4">
        <h2 class="fw-bold text-dark">Calendar</h2>
        <p class="text-muted mb-0">
            Weekly room booking schedule
        </p>
    </div>

    {{-- Week navigation --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">

        <div>
            <h5 class="fw-semibold mb-1">
                {{ $weekStart->format('d M Y') }}
                -
                {{ $weekEnd->format('d M Y') }}
            </h5>
            <small class="text-muted">Monday - Sunday</small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('jadwal.index', ['week' => $weekStart->copy()->subWeek()->toDateString()]) }}"
               class="btn btn-outline-secondary">
                Previous
            </a>

            <a href="{{ route('jadwal.index', ['week' => now()->toDateString()]) }}"
               class="btn btn-outline-secondary">
                Today
            </a>

            <a href="{{ route('jadwal.index', ['week' => $weekStart->copy()->addWeek()->toDateString()]) }}"
               class="btn btn-outline-secondary">
                Next
            </a>
        </div>

    </div>

    {{-- Calendar table --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th class="p-3" style="min-width: 150px;">
                                Unit
                            </th>

                            @for ($day = $weekStart->copy(); $day->lte($weekEnd); $day->addDay())
                                <th class="p-3 text-center" style="min-width: 130px;">
                                    <div class="fw-semibold">
                                        {{ $day->format('D') }}
                                    </div>
                                    <small class="text-muted">
                                        {{ $day->format('d M') }}
                                    </small>
                                </th>
                            @endfor
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($units as $unit)
                            <tr>
                                <td class="p-3 fw-semibold">
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

                                    <td class="p-2 align-top">
                                        @forelse ($dayBookings as $booking)
                                            <div class="rounded p-2 mb-2 border
                                                {{ $booking->status === 'Disetujui' ? 'bg-success-subtle border-success-subtle' : 'bg-warning-subtle border-warning-subtle' }}">

                                                <div class="fw-semibold">
                                                    {{ substr($booking->start_time, 0, 5) }}
                                                    -
                                                    {{ substr($booking->end_time, 0, 5) }}
                                                </div>

                                                <div class="small mt-1">
                                                    {{ $booking->activity_description }}
                                                </div>

                                                <div class="mt-2">
                                                    @if ($booking->status === 'Disetujui')
                                                        <span class="badge text-bg-success">
                                                            {{ $booking->status }}
                                                        </span>
                                                    @else
                                                        <span class="badge text-bg-warning">
                                                            {{ $booking->status }}
                                                        </span>
                                                    @endif
                                                </div>

                                            </div>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </td>
                                @endfor
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted p-4">
                                    No units available.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>

            </div>
        </div>
    </div>

</div>
@endsection