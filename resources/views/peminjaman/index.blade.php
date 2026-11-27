@extends('layouts.app')

@section('title', 'Riwayat Pengajuan - SEKATA | BEM UPNVJ')

@section('content')
<h1 class="h3 mb-2">Riwayat Pengajuan</h1>
<p class="text-secondary mb-4">Lihat pengajuan peminjaman dan status terbarunya.</p>

<div class="sekata-card p-3">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <caption class="caption-top">Daftar pengajuan dari yang terbaru</caption>
            <thead>
                <tr>
                    <th scope="col">Biro & Bidang</th>
                    <th scope="col">Kegiatan</th>
                    <th scope="col">Jadwal</th>
                    <th scope="col">Status</th>
                    <th scope="col">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookings as $booking)
                    <tr>
                        <td>{{ $booking->user->unit_name }}</td>
                        <td>{{ $booking->activity_description }}</td>
                        <td>
                            {{ $booking->tanggal->format('d/m/Y') }}
                            <div class="small text-secondary text-nowrap">
                                {{ substr($booking->start_time, 0, 5) . ' - ' . substr($booking->end_time, 0, 5) . ' WIB' }}
                            </div>
                        </td>
                        <td>{{ $booking->status }}</td>
                        <td>
    <div class="d-flex flex-wrap gap-1">
        <a href="{{ route('peminjaman.show', $booking->id) }}"
           class="btn btn-sekata-secondary btn-sm">
            Lihat detail
        </a>

        @if (!auth()->user()->isAdmin() && $booking->status === 'Menunggu')
            <a href="{{ route('peminjaman.edit', $booking->id) }}"
               class="btn btn-warning btn-sm">
                Edit
            </a>

            <form method="POST"
                  action="{{ route('peminjaman.destroy', $booking->id) }}"
                  onsubmit="return confirm('Yakin ingin menghapus pengajuan ini?')">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger btn-sm">
                    Hapus
                </button>
            </form>
        @endif
    </div>
</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-4">Belum ada pengajuan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($bookings->hasPages())
        <div class="mt-3">{{ $bookings->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endsection
