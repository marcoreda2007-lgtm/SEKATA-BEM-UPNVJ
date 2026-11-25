@extends('layouts.app')

@section('title', 'Notifikasi - SEKATA | BEM UPNVJ')

@section('content')
@php
    $typeBadges = [
        \App\Models\Notification::TYPE_BOOKING_CREATED => ['label' => 'Pengajuan Baru', 'class' => 'pill-menunggu'],
        \App\Models\Notification::TYPE_BOOKING_APPROVED => ['label' => 'Disetujui', 'class' => 'pill-disetujui'],
        \App\Models\Notification::TYPE_BOOKING_REJECTED => ['label' => 'Ditolak', 'class' => 'pill-perbaikan'],
        \App\Models\Notification::TYPE_PROOF_REVISION_REQUESTED => ['label' => 'Perlu Perbaikan', 'class' => 'pill-perbaikan'],
        \App\Models\Notification::TYPE_PROOF_COMPLETED => ['label' => 'Selesai', 'class' => 'pill-disetujui'],
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Notifikasi</h1>
        <p class="notif-subtitle mb-0">
            Pantau pembaruan pengajuan peminjaman, keputusan SPPI, dan pemeriksaan bukti.
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('notifikasi.index') }}"
           class="btn {{ $filter === 'all' ? 'btn-sekata-primary' : 'btn-sekata-secondary' }} notif-filter-btn d-inline-flex align-items-center">
            Semua ({{ $totalCount }})
        </a>
        <a href="{{ route('notifikasi.index', ['filter' => 'unread']) }}"
           class="btn {{ $filter === 'unread' ? 'btn-sekata-primary' : 'btn-sekata-secondary' }} notif-filter-btn d-inline-flex align-items-center">
            Belum dibaca ({{ $unreadCount }})
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="sekata-card p-3 p-md-4">
            @if($notifications->isEmpty())
                <div class="text-center py-5 px-3">
                    <h2 class="h5 fw-bold mb-2">
                        {{ $filter === 'unread' ? 'Semua notifikasi sudah dibaca' : 'Belum ada notifikasi' }}
                    </h2>
                    <p class="notif-subtitle mb-4">
                        @if(auth()->user()->isAdmin())
                            Notifikasi pengajuan baru dari bidang atau biro akan muncul di sini.
                        @else
                            Notifikasi keputusan SPPI dan hasil pemeriksaan bukti akan muncul di sini.
                        @endif
                    </p>
                    <a href="{{ auth()->user()->isAdmin() ? route('admin.peminjaman.index') : route('jadwal.index') }}"
                       class="btn btn-sekata-secondary d-inline-flex align-items-center">
                        {{ auth()->user()->isAdmin() ? 'Kembali ke antrean SPPI' : 'Kembali ke jadwal' }}
                    </a>
                </div>
            @else
                <div class="d-flex flex-column gap-3">
                    @foreach($notifications as $notification)
                        @php
                            $badge = $typeBadges[$notification->type] ?? ['label' => 'Info', 'class' => 'pill-menunggu'];
                            $isUnread = $notification->isUnread();
                            $createdWib = $notification->created_at
                                ->copy()
                                ->timezone('Asia/Jakarta')
                                ->format('d M Y - H:i') . ' WIB';
                        @endphp

                        <article class="notif-item {{ $isUnread ? 'notif-item-unread' : '' }}">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="{{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                    @if($isUnread)
                                        <span class="badge rounded-pill notif-badge-count">Baru</span>
                                    @else
                                        <span class="notif-meta">Sudah dibaca</span>
                                    @endif
                                </div>
                                <span class="notif-meta">{{ $createdWib }}</span>
                            </div>

                            <h2 class="notif-title mb-1">{{ $notification->title }}</h2>

                            <p class="notif-message mb-2">{{ $notification->message }}</p>

                            @if($notification->booking)
                                <div class="notif-meta mb-3">
                                    PIC: {{ $notification->booking->pic_name }} ({{ $notification->booking->pic_division }})
                                    - Status saat ini: <strong>{{ $notification->booking->status }}</strong>
                                </div>
                            @endif

                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <a href="{{ $notification->detailUrl() }}"
                                   class="btn btn-sekata-primary notif-action-btn d-inline-flex align-items-center">
                                    Lihat detail
                                </a>

                                @if($isUnread)
                                    <form method="POST" action="{{ route('notifikasi.read', $notification) }}">
                                        @csrf
                                        @method('PATCH')
                                        @if($filter === 'unread')
                                            <input type="hidden" name="filter" value="unread">
                                        @endif
                                        <button type="submit" class="btn btn-sekata-secondary notif-action-btn">
                                            Tandai dibaca
                                        </button>
                                    </form>
                                @elseif($notification->read_at)
                                    <span class="notif-meta ms-1">
                                        Dibaca {{ $notification->read_at->copy()->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
                                    </span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                @if($notifications->hasPages())
                    <div class="mt-4">
                        {{ $notifications->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="sekata-card p-4">
            <div class="text-uppercase notif-summary-label mb-2">Ringkasan Notifikasi</div>
            <h2 class="h5 fw-bold mb-3">Status Pembaruan</h2>

            <div class="d-flex justify-content-between align-items-center py-2 notif-summary-row">
                <span class="notif-subtitle">Belum dibaca</span>
                <span class="fw-bold notif-summary-unread">{{ $unreadCount }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2 mb-3 notif-summary-row">
                <span class="notif-subtitle">Total notifikasi</span>
                <span class="fw-bold">{{ $totalCount }}</span>
            </div>

            <p class="notif-summary-help mb-0">
                @if(auth()->user()->isAdmin())
                    Setiap pengajuan peminjaman baru dari bidang atau biro akan mengirim pemberitahuan ke akun Biro SPPI.
                @else
                    Setiap keputusan SPPI (Disetujui/Ditolak) dan hasil pemeriksaan bukti (Perlu Perbaikan/Selesai) akan muncul di halaman ini.
                @endif
            </p>
        </div>
    </div>
</div>
@endsection