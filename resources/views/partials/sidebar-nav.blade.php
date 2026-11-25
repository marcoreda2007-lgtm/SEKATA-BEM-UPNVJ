@php
    $menuItems = [
        ['route' => 'jadwal.index', 'label' => 'Jadwal bersama', 'path' => 'M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z'],
    ];
    if (auth()->user()->isAdmin()) {
        $menuItems[] = ['route' => 'admin.peminjaman.index', 'label' => 'Antrean SPPI', 'path' => 'M9 5h12M9 12h12M9 19h12M3 5h1M3 12h1M3 19h1'];
    }
    $menuItems[] = ['route' => 'peminjaman.index', 'label' => 'Riwayat Pengajuan', 'path' => 'M3 11a9 9 0 1 1 2.6 7M3 4v7h7m2-4v5l3 2'];
    $menuItems[] = ['route' => 'penitipan.index', 'label' => 'Penitipan Barang', 'path' => 'm12 3 9 5-9 5-9-5 9-5Zm-9 5v9l9 5 9-5V8m-9 5v9'];
    $menuItems[] = ['route' => 'notifikasi.index', 'label' => 'Notifikasi', 'path' => 'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4'];
@endphp
<nav class="sidebar-nav" aria-label="Menu utama">
    @foreach ($menuItems as $item)
        @php $active = request()->routeIs($item['route']); @endphp
        <a href="{{ route($item['route']) }}" class="sidebar-link {{ $active ? 'active' : '' }}"
           aria-label="{{ $item['label'] }}" @if ($active) aria-current="page" @endif>
            <svg class="sidebar-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="{{ $item['path'] }}"></path>
            </svg>
            <span class="sidebar-label">{{ $item['label'] }}</span>
            <span class="sidebar-tooltip" aria-hidden="true">{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
