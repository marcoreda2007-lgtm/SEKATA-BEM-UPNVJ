@extends('layouts.app')

@section('title', 'Detail Peminjaman #' . $booking->id . ' - SEKATA | BEM UPNVJ')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/booking-detail.css') }}">
@endpush

@section('content')
@php
    $pillClass = match ($booking->status) {
        'Disetujui' => 'pill-disetujui',
        'Menunggu' => 'pill-menunggu',
        'Perlu Perbaikan' => 'pill-perbaikan',
        'Ditolak' => 'pill-ditolak',
        'Periksa Bukti' => 'pill-periksa',
        'Selesai' => 'pill-selesai',
        default => 'pill-menunggu',
    };

    $currentStatusDot = match ($booking->status) {
        'Disetujui', 'Selesai' => 'status-record-dot--hijau',
        'Menunggu', 'Periksa Bukti' => 'status-record-dot--kuning',
        'Ditolak', 'Perlu Perbaikan' => 'status-record-dot--merah',
        default => 'status-record-dot--netral',
    };

    // Label keputusan hanya disebut Disetujui/Ditolak jika status saat ini
    // memang mencerminkan keputusan tersebut; selain itu tampilkan netral.
    $decisionLabel = match ($booking->status) {
        'Disetujui' => 'Disetujui',
        'Ditolak' => 'Ditolak',
        default => 'Keputusan SPPI tercatat',
    };

    $decisionDot = match ($booking->status) {
        'Disetujui' => 'status-record-dot--hijau',
        'Ditolak' => 'status-record-dot--merah',
        default => 'status-record-dot--netral',
    };
@endphp

{{-- Header Halaman --}}
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <div class="text-uppercase fw-semibold detail-eyebrow">
            Pengajuan #{{ $booking->id }}
        </div>
        <h1 class="h4 fw-bold mb-0">Detail Peminjaman Sekretariat</h1>
    </div>

    <a href="{{ auth()->user()->isAdmin() ? route('admin.peminjaman.index') : route('jadwal.index') }}" class="btn btn-sekata-secondary">
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
                    <div class="fw-bold detail-unit-title">{{ $booking->user?->unit_name ?? '-' }}</div>
                    <div class="detail-meta">
                        @if($createdAtWib)
                            Diajukan {{ $createdAtWib }}
                        @else
                            Waktu pengajuan belum tercatat
                        @endif
                    </div>
                </div>
                <span class="{{ $pillClass }}">{{ $booking->status }}</span>
            </div>

            <div class="row g-3 small">
                <div class="col-12 col-sm-6">
                    <div class="detail-label">Ruangan</div>
                    <div class="fw-semibold fs-6">{{ $booking->room?->name ?? '-' }}</div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="detail-label">Tanggal & Jam Penggunaan</div>
                    <div class="fw-semibold fs-6">
                        {{ $booking->tanggal?->format('d M Y') ?? '-' }},
                        {{ substr((string) $booking->start_time, 0, 5) }}–{{ substr((string) $booking->end_time, 0, 5) }} WIB
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="detail-label">Nama PIC</div>
                    <div class="fw-semibold fs-6">{{ $booking->pic_name }}</div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="detail-label">Divisi PIC</div>
                    <div class="fw-semibold fs-6">{{ $booking->pic_division }}</div>
                </div>
                <div class="col-12">
                    <div class="detail-label">Deskripsi Kegiatan</div>
                    <div class="detail-box detail-box--pre mt-1">{{ $booking->activity_description }}</div>
                </div>
            </div>
        </div>

        {{-- Kartu Keputusan SPPI & Catatan Pemeriksaan --}}
        <div class="sekata-card p-3 p-md-4 mb-3">
            <h2 class="h6 fw-bold mb-3">Keputusan SPPI & Catatan Pemeriksaan</h2>

            <div class="mb-3">
                <div class="detail-label">Keputusan SPPI</div>
                @if($hasDecisionData)
                    <div class="detail-box mt-1">
                        <div>
                            {{ filled($booking->decision_reason) ? $booking->decision_reason : 'Keputusan tercatat tanpa alasan tertulis.' }}
                        </div>
                        @if($booking->decider || $decidedAtWib)
                            <div class="detail-meta-sm mt-2">
                                @if($booking->decider)
                                    Oleh {{ $booking->decider->unit_name }}
                                @endif
                                @if($booking->decider && $decidedAtWib)
                                    -
                                @endif
                                @if($decidedAtWib)
                                    {{ $decidedAtWib }}
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <div class="detail-meta mt-1">Data keputusan SPPI belum tercatat.</div>
                @endif
            </div>

            <div>
                <div class="detail-label">Catatan Pemeriksaan Bukti Pengembalian</div>
                @if(filled($booking->inspection_note))
                    <div class="detail-box detail-box--pre mt-1">{{ $booking->inspection_note }}</div>
                @else
                    <div class="detail-meta mt-1">Belum ada catatan pemeriksaan bukti.</div>
                @endif
            </div>
        </div>

        {{-- Kartu Bukti Pengembalian Sekretariat --}}
        <div class="sekata-card p-3 p-md-4">
            <h2 class="h6 fw-bold mb-2">Bukti Pengembalian Sekretariat</h2>
            <p class="detail-meta mb-3">
                Tampilan foto bukti pengembalian belum terintegrasi pada halaman ini dan akan disambungkan dengan modul bukti pengembalian.
            </p>

            <div class="row g-2">
                <div class="col-12 col-sm-6">
                    <div class="proof-placeholder">
                        <div class="fw-semibold text-dark mb-1">1. Foto Kondisi Ruangan</div>
                        <div>Belum terintegrasi.</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="proof-placeholder">
                        <div class="fw-semibold text-dark mb-1">2. Foto Pengembalian Kunci</div>
                        <div>Belum terintegrasi.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kolom Kanan: Ringkasan Status & Pencatatan --}}
    <div class="col-12 col-lg-5">
        <div class="sekata-card p-3 p-md-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 mb-3">
                <h2 class="h6 fw-bold mb-0">Informasi Status & Pencatatan</h2>
                <span class="detail-meta-sm">Data tersimpan</span>
            </div>

            <div class="mt-2">
                {{-- 1. Pengajuan tercatat --}}
                <div class="status-record-item">
                    <span class="status-record-dot"></span>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-1">
                        <span class="fw-semibold small">Pengajuan dibuat</span>
                        <span class="detail-meta-sm">{{ $createdAtWib ?? 'Waktu belum tercatat' }}</span>
                    </div>
                    <div class="detail-meta-sm">
                        Oleh: {{ $booking->user?->unit_name ?? '-' }}
                    </div>
                </div>

                {{-- 2. Keputusan SPPI (hanya berdasarkan data keputusan yang benar-benar tercatat) --}}
                <div class="status-record-item">
                    <span class="status-record-dot {{ $hasDecisionData ? $decisionDot : 'status-record-dot--netral' }}"></span>
                    @if($hasDecisionData)
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-1">
                            <span class="fw-semibold small">{{ $decisionLabel }}</span>
                            <span class="detail-meta-sm">{{ $decidedAtWib ?? 'Waktu keputusan belum tercatat' }}</span>
                        </div>
                        @if($booking->decider)
                            <div class="detail-meta-sm">
                                Oleh: {{ $booking->decider->unit_name }}
                            </div>
                        @endif
                        @if(filled($booking->decision_reason))
                            <div class="detail-box detail-box--compact mt-2">
                                {{ $booking->decision_reason }}
                            </div>
                        @endif
                    @else
                        <div class="fw-semibold small">Keputusan SPPI</div>
                        <div class="detail-meta-sm">Data keputusan belum tercatat.</div>
                    @endif
                </div>

                {{-- 3. Status saat ini & pembaruan data terakhir --}}
                <div class="status-record-item">
                    <span class="status-record-dot {{ $currentStatusDot }}"></span>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-1">
                        <span class="fw-semibold small">Status saat ini: {{ $booking->status }}</span>
                    </div>
                    <div class="detail-meta-sm">
                        Pembaruan data terakhir: {{ $updatedAtWib ?? 'Belum tercatat' }}
                    </div>
                    @if(filled($booking->inspection_note))
                        <div class="detail-box detail-box--compact detail-box--pre mt-2">
                            {{ $booking->inspection_note }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="detail-notice mt-3">
                Panel ini hanya menampilkan data yang tersimpan saat ini. Waktu pembaruan terakhir menunjukkan perubahan data terbaru, bukan waktu setiap perpindahan status. Riwayat status lengkap per tahap belum tersedia.
            </div>
        </div>
    </div>
</div>
@endsection
