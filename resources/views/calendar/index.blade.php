
@extends('layouts.app')

@section('title', 'Jadwal Sekretariat')

@section('content')
<style>
    .calendar-page {
        color: #173b2d;
    }

    /* Heading */
    .calendar-title {
        font-size: 25px;
        font-weight: 800;
        color: #173b2d;
        margin-bottom: 5px;
    }

    .calendar-subtitle {
        font-size: 12px;
        color: #718278;
    }

    .calendar-add-btn {
        background: #087443;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 9px 16px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: background .2s;
    }

    .calendar-add-btn:hover {
        background: #065c35;
        color: #fff;
    }

    /* Week navigation */
    .calendar-navigation {
        display: flex;
        align-items: center;
        gap: 9px;
    }

    .calendar-nav-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #dce9df;
        border-radius: 6px;
        background: #fff;
        color: #17613f;
        text-decoration: none;
        font-size: 15px;
        transition: all .2s;
    }

    .calendar-nav-btn:hover {
        background: #e8f4eb;
        color: #075b35;
    }

    .calendar-week-label {
        font-size: 14px;
        font-weight: 700;
        color: #173b2d;
        margin-left: 2px;
    }

    .calendar-wib {
        font-size: 10px;
        color: #718278;
    }

    .calendar-today-btn {
        border: 1px solid #dce9df;
        background: #fff;
        color: #17613f;
        border-radius: 6px;
        padding: 7px 13px;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
    }

    .calendar-today-btn:hover {
        background: #e8f4eb;
        color: #075b35;
    }

    /* Calendar table */
    .calendar-wrapper {
        background: #fff;
        border: 1px solid #dce9df;
        border-radius: 10px;
        overflow: hidden;
    }

    .calendar-table {
        min-width: 950px;
        table-layout: fixed;
        border-color: #e1ece4;
    }

    .calendar-table th,
    .calendar-table td {
        border-color: #e1ece4;
    }

    .calendar-table thead th {
        background: #e3f0e6;
        color: #174b34;
        height: 59px;
        padding: 8px 6px;
        vertical-align: middle;
        font-size: 11px;
    }

    .calendar-table thead th:first-child {
        background: #e3f0e6;
        text-align: left;
        padding-left: 14px;
        min-width: 150px;
        width: 16%;
    }

    .calendar-table thead th:not(:first-child) {
        width: 12%;
    }

    .calendar-day-name {
        font-size: 10px;
        font-weight: 500;
        color: #60786a;
    }

    .calendar-day-number {
        font-size: 14px;
        font-weight: 800;
        color: #174b34;
    }

    .calendar-table tbody td {
        height: 42px;
        padding: 4px;
        vertical-align: top;
        background: #fff;
    }

    .calendar-table tbody td:first-child {
        padding: 10px 12px;
        vertical-align: middle;
        font-size: 11px;
        font-weight: 600;
        color: #244a37;
        background: #fff;
    }

    .calendar-table .today-column {
        background: #edf6ef;
    }

    .calendar-table thead .today-column {
        background: #cfe5d5;
    }

    .calendar-table tbody td.today-column {
        background: #edf6ef;
    }

    /* Booking cards */
    .calendar-booking {
        border-radius: 5px;
        padding: 6px 7px;
        margin-bottom: 3px;
        font-size: 10px;
        line-height: 1.4;
        border: 1px solid transparent;
        border-left-width: 3px;
    }

    .calendar-booking.approved {
        background: #dff1e4;
        border-color: #d1e9d9;
        border-left-color: #25834e;
        color: #17613b;
    }

    .calendar-booking.pending {
        background: #fff3d9;
        border-color: #f6e8c4;
        border-left-color: #d99a24;
        color: #85580c;
    }

    .calendar-booking-time {
        font-weight: 700;
        font-size: 10px;
    }

    .calendar-booking-description {
        font-size: 9px;
        margin-top: 2px;
        color: inherit;
        opacity: .9;
        overflow-wrap: anywhere;
    }

    .calendar-booking-status {
        font-size: 9px;
        margin-top: 2px;
    }

    .calendar-empty {
        font-size: 10px;
        color: #bdc9c0;
        padding: 4px;
    }

    @media (max-width: 767px) {
        .calendar-title {
            font-size: 22px;
        }

        .calendar-navigation {
            flex-wrap: wrap;
        }
    }
</style>

<div class="container-fluid py-4 calendar-page">

    {{-- Heading --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="calendar-title">Jadwal sekretariat</h2>
            <p class="calendar-subtitle mb-0">
                Periksa jam pemakaian semua bidang dan biro sebelum mengajukan.
            </p>
        </div>

        <a href="{{ route('booking.create') }}" class="calendar-add-btn">
            + Ajukan peminjaman
        </a>
    </div>

    {{-- Week navigation --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">

        <div class="calendar-navigation">
            <a href="{{ route('jadwal.index', ['week' => $weekStart->copy()->subWeek()->toDateString()]) }}"
               class="calendar-nav-btn"
               title="Minggu sebelumnya">
                &lsaquo;
            </a>

            <a href="{{ route('jadwal.index', ['week' => $weekStart->copy()->addWeek()->toDateString()]) }}"
               class="calendar-nav-btn"
               title="Minggu berikutnya">
                &rsaquo;
            </a>

            <span class="calendar-week-label">
                {{ $weekStart->format('d') }}–{{ $weekEnd->format('d F Y') }}
            </span>

            <span class="calendar-wib">WIB</span>

            <a href="{{ route('jadwal.index', ['week' => now()->toDateString()]) }}"
               class="calendar-today-btn ms-2">
                Hari Ini
            </a>
        </div>

    </div>

    {{-- Calendar table --}}
    <div class="calendar-wrapper shadow-sm">
        <div class="table-responsive">

            <table class="table table-bordered align-middle mb-0 calendar-table">

                <thead>
                    <tr>
                        <th>Bidang dan biro</th>

                        @for ($day = $weekStart->copy(); $day->lte($weekEnd); $day->addDay())
                            <th class="text-center {{ $day->isToday() ? 'today-column' : '' }}">
                                <div class="calendar-day-name">
                                    {{ $day->locale('id')->translatedFormat('D') }}
                                </div>

                                <div class="calendar-day-number">
                                    {{ $day->format('d') }}
                                </div>
                            </th>
                        @endfor
                    </tr>
                </thead>

                <tbody>
                    @forelse ($units as $unit)
                        <tr>
                            <td>
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

                                <td class="{{ $day->isToday() ? 'today-column' : '' }}">

                                    @forelse ($dayBookings as $booking)
                                        <div class="calendar-booking {{ $booking->status === 'Disetujui' ? 'approved' : 'pending' }}">

                                            <div class="calendar-booking-time">
                                                {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}
                                            </div>

                                            <div class="calendar-booking-description">
                                                {{ $booking->activity_description }}
                                            </div>

                                            <div class="calendar-booking-status">
                                                {{ $booking->status }}
                                            </div>

                                        </div>
                                    @empty
                                        <span class="calendar-empty"> </span>
                                    @endforelse

                                </td>
                            @endfor
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Belum ada bidang atau biro yang tersedia.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>

        </div>
    </div>

</div>
@endsection