@extends('layouts.app')

@section('title', 'Detail Peminjaman #' . $booking->id . ' · SEKATA')

@push('styles')
<style>
    .pill-ditolak {
        background-color: #FDECEC;
        color: #9E2A2B;
        border-radius: 999px;
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .pill-periksa {
        background-color: #E6F0FA;
        color: #1D4ED8;
        border-radius: 999px;
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .pill-selesai {
        background-color: #E8ECEA;
        color: #2E4239;
        border-radius: 999px;
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .detail-label {
        font-size: 12px;
        color: var(--muted);
        margin-bottom: 2px;
    }
    .detail-box {
        background-color: #F7FAF8;
        border: 1px solid var(--line);
        border-radius: 8px;
        padding: 12px 14px;
        font-size: 14px;
    }
    .timeline-item {
        position: relative;
        padding-left: 20px;
        border-left: 2px solid var(--line);
        padding-bottom: 18px;
    }
    .timeline-item:last-child {
        padding-bottom: 0;
        border-left-color: transparent;
    }
    .timeline-dot {
        position: absolute;
        left: -6px;
        top: 4px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: var(--hijau-gelap);
    }
    .timeline-dot.kuning {
        background-color: #EAB308;
    }
    .timeline-dot.hijau {
        background-color: var(--hijau-bem);
    }
    .timeline-dot.merah {
        background-color: #DC2626;
    }
    .proof-slot {
        border: 1px dashed #C5D3CB;
        background-color: #F7FAF8;
        border-radius: 8px;
        padding: 14px;
        font-size: 13px;
        color: var(--muted);
    }
</style>
@endpush

@section('content')
@php
    $pillClass = match($booking->status) {
        'Disetujui' => 'pill-disetujui',
        'Menunggu' => 'pill-menunggu',
        'Perlu Perbaikan' => 'pill-perbaikan',
        'Ditolak' => 'pill-ditolak',
        'Periksa Bukti' => 'pill-periksa',
        'Selesai' => 'pill-selesai',
        default => 'pill-menunggu',
    };
@endphp

{{-- Header Halaman --}}
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <div class="text-uppercase fw-semibold" style="font-size: 11px; letter-spacing: 0.05em; color: var(--muted);">
            Pengajuan #{{ $booking->id }}
        </div>
        <h1 class="h4 fw-bold mb-0">Detail Peminjaman Sekretariat</h1>
    </div>

    <a href="{{ auth()->user()->isAdmin() ? url('/admin/peminjaman') : url('/jadwal') }}" class="btn btn-sekata-secondary">
        ← Kembali
    </a>
</div>

<div class="row g-3">
    {{-- Kolom Kiri: Rincian Jadwal, Keputusan SPPI & Bukti Pengembalian --}}
    <div class="col-12 col-lg-7">
        {{-- Kartu Informasi Utama --}}
        <div class="sekata-card p-3 p-md-4 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 pb-3 mb-3 border-bottom">
                <div>
                    <div class="fw-bold" style="font-size: 18px;">{{ $booking->user->unit_name }}</div>
                    <div style="font-size: 13px; color: var(--muted);">
                        Diajukan {{ $booking->created_at?->format('d M Y, H:i') }} WIB
                    </div>
                </div>
                <span class="{{ $pillClass }}">{{ $booking->status }}</span>
            </div>

            <div class="row g-3" style="font-size: 14px;">
                <div class="col-12 col-sm-6">
                    <div class="detail-label">Ruangan</div>
                    <div class="fw-semibold">{{ $booking->room->name }}</div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="detail-label">Tanggal & Jam Penggunaan</div>
                    <div class="fw-semibold">
                        {{ $booking->tanggal->format('d M Y') }},
                        {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }} WIB
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="detail-label">Nama PIC</div>
                    <div class="fw-semibold">{{ $booking->pic_name }}</div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="detail-label">Divisi PIC</div>
                    <div class="fw-semibold">{{ $booking->pic_division }}</div>
                </div>
                <div class="col-12">
                    <div class="detail-label">Deskripsi Kegiatan</div>
                    <div class="detail-box mt-1" style="white-space: pre-line;">{{ $booking->activity_description }}</div>
                </div>
            </div>
        </div>

        {{-- Kartu Keputusan SPPI & Catatan Inspeksi --}}
        <div class="sekata-card p-3 p-md-4 mb-3">
            <h2 class="h6 fw-bold mb-3">Keputusan SPPI & Catatan Pemeriksaan</h2>

            <div class="mb-3">
                <div class="detail-label">Alasan Keputusan SPPI</div>
                @if($booking->decision_reason)
                    <div class="detail-box mt-1">
                        <div>{{ $booking->decision_reason }}</div>
                        @if($booking->decider || $booking->decided_at)
                            <div class="mt-2" style="font-size: 12px; color: var(--muted);">
                                Oleh {{ $booking->decider?->unit_name ?? 'Admin Biro SPPI' }}
                                @if($booking->decided_at)
                                    · {{ $booking->decided_at->format('d M Y, H:i') }} WIB
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <div class="mt-1" style="font-size: 13px; color: var(--muted);">Belum ada alasan keputusan dari SPPI.</div>
                @endif
            </div>

            <div>
                <div class="detail-label">Catatan Pemeriksaan Bukti Pengembalian</div>
                @if($booking->inspection_note)
                    <div class="detail-box mt-1">{{ $booking->inspection_note }}</div>
                @else
                    <div class="mt-1" style="font-size: 13px; color: var(--muted);">Belum ada catatan pemeriksaan bukti.</div>
                @endif
            </div>
        </div>

        {{-- Kartu Status Bukti Pengembalian --}}
        <div class="sekata-card p-3 p-md-4">
                <h2 class="h6 fw-bold mb-0">Bukti Pengembalian Sekretariat</h2>

            <div class="row g-2">
                <div class="col-12 col-sm-6">
                    <div class="proof-slot">
                        <div class="fw-semibold text-dark mb-1">1. Foto Kondisi Ruangan</div>
                        <div>.</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="proof-slot">
                        <div class="fw-semibold text-dark mb-1">2. Foto Pengembalian Kunci</div>
                        <div>.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kolom Kanan: Riwayat Status --}}
    <div class="col-12 col-lg-5">
        <div class="sekata-card p-3 p-md-4">
            <h2 class="h6 fw-bold mb-0">Riwayat Status</h2>

            <div class="mt-2">
                {{-- 1. Pengajuan dibuat oleh unit --}}
                <div class="timeline-item">
                    <span class="timeline-dot"></span>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-1">
                        <span class="fw-semibold" style="font-size: 13px;">Pengajuan dibuat</span>
                        <span style="font-size: 11px; color: var(--muted);">
                            {{ $booking->created_at?->format('d M Y, H:i') }} WIB
                        </span>
                    </div>
                    <div style="font-size: 12px; color: var(--muted);">
                        Oleh: {{ $booking->user->unit_name }}
                    </div>
                </div>

                {{-- 2. Status Menunggu --}}
                <div class="timeline-item">
                    <span class="timeline-dot kuning"></span>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-1">
                        <span class="fw-semibold" style="font-size: 13px;">Menunggu</span>
                        <span style="font-size: 11px; color: var(--muted);">
                            {{ $booking->created_at?->format('d M Y, H:i') }} WIB
                        </span>
                    </div>
                </div>

                {{-- 3. Keputusan SPPI --}}
                @if($booking->decided_at || $booking->decision_reason || $booking->status !== 'Menunggu')
                    @php
                        $isDitolak = $booking->status === 'Ditolak';
                        $decisionLabel = $isDitolak ? 'Ditolak' : 'Disetujui';
                        $decisionDot = $isDitolak ? 'merah' : 'hijau';
                    @endphp
                    <div class="timeline-item">
                        <span class="timeline-dot {{ $decisionDot }}"></span>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-1">
                            <span class="fw-semibold" style="font-size: 13px;">{{ $decisionLabel }}</span>
                            @if($booking->decided_at)
                                <span style="font-size: 11px; color: var(--muted);">
                                    {{ $booking->decided_at->format('d M Y, H:i') }} WIB
                                </span>
                            @endif
                        </div>
                        <div style="font-size: 12px; color: var(--muted);">
                            Oleh: {{ $booking->decider?->unit_name ?? 'Admin Biro SPPI' }}
                        </div>
                        @if($booking->decision_reason)
                            <div class="detail-box mt-2" style="font-size: 13px; padding: 8px 10px;">
                                {{ $booking->decision_reason }}
                            </div>
                        @endif
                    </div>
                @endif

                {{-- 4. Status lanjutan pengembalian bukti --}}
                @if(in_array($booking->status, ['Periksa Bukti', 'Perlu Perbaikan', 'Selesai']))
                    @php
                        $proofDot = match($booking->status) {
                            'Periksa Bukti' => 'kuning',
                            'Perlu Perbaikan' => 'merah',
                            'Selesai' => 'hijau',
                            default => 'hijau',
                        };
                    @endphp
                    <div class="timeline-item">
                        <span class="timeline-dot {{ $proofDot }}"></span>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-1">
                            <span class="fw-semibold" style="font-size: 13px;">{{ $booking->status }}</span>
                            <span style="font-size: 11px; color: var(--muted);">
                                {{ $booking->updated_at?->format('d M Y, H:i') }} WIB
                            </span>
                        </div>
                        @if($booking->inspection_note)
                            <div class="detail-box mt-2" style="font-size: 13px; padding: 8px 10px;">
                                {{ $booking->inspection_note }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection