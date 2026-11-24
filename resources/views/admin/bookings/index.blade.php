@extends('layouts.app')

@section('title', 'Antrean Pengajuan | SEKATA')

@section('content')
    <h1 class="h3 mb-2">Antrean Pengajuan</h1>

    <p class="text-secondary mb-4">
        Periksa pengajuan peminjaman yang menunggu keputusan.
    </p>

    <div class="sekata-card p-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <caption class="caption-top">
                    Daftar pengajuan berstatus Menunggu
                </caption>

                <thead>
                    <tr>
                        <th scope="col">Unit</th>
                        <th scope="col">Kegiatan</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td>{{ $booking->user->unit_name }}</td>
                            <td>{{ $booking->activity_description }}</td>
                            <td>
                                <span class="pill-menunggu">
                                    {{ $booking->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">
                                Belum ada pengajuan yang perlu diperiksa.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection