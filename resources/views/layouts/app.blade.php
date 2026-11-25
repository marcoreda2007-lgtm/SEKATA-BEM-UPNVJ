<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SEKATA | BEM UPNVJ')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notifications.css') }}">

    @stack('styles')
</head>

<body>
    @auth
        <div class="app-shell">
            <aside class="sekata-sidebar desktop-only">
                <a href="{{ url('/') }}" class="sidebar-brand">
                    <div class="brand-emblem">
                        <img src="{{ asset('images/logo-bem.png') }}" alt="BEM UPNVJ">
                    </div>
                    <div>
                        <div class="fw-bold lh-1" style="font-size: 18px; letter-spacing: 0.04em;">
                            SEKATA
                        </div>
                        <div style="font-size: 11px; color: #A7C2B5; margin-top: 3px;">
                            BEM UPNVJ
                        </div>
                    </div>
                </a>

                <nav class="sidebar-nav">
                    <a href="{{ route('jadwal.index') }}"
                        class="sidebar-link {{ request()->is('jadwal*') ? 'active' : '' }}">
                        Jadwal bersama
                    </a>

                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.peminjaman.index') }}"
                            class="sidebar-link {{ request()->is('admin/peminjaman*') ? 'active' : '' }}">
                            Antrean SPPI
                        </a>
                    @endif

                    <a href="{{ route('notifikasi.index') }}"
                        class="sidebar-link {{ request()->routeIs('notifikasi.*') ? 'active' : '' }}">
                        Notifikasi
                    </a>
                </nav>

                <div class="sidebar-footer">
                    <div class="active-account-box d-flex align-items-center gap-3 mb-2">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                            class="flex-shrink-0" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 21v-2a8 8 0 0 1 16 0v2"></path>
                        </svg>

                        <div class="text-start">
                            <div class="fw-bold">
                                {{ auth()->user()->unit_name }}
                            </div>
                            <div class="small fw-normal">
                                {{ auth()->user()->isAdmin() ? 'Admin' : 'Peminjam' }}
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-danger w-100">
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            <div class="offcanvas offcanvas-start text-white" tabindex="-1" id="mobileSidebar"
                aria-labelledby="mobileSidebarTitle" style="background-color: var(--hijau-gelap); width: 260px;">
                <div class="offcanvas-header border-bottom border-secondary border-opacity-25">
                    <div class="d-flex align-items-center gap-2">
                        <div class="brand-emblem" style="width: 36px; height: 36px;">
                            <img src="{{ asset('images/logo-bem.png') }}" alt="BEM UPNVJ">
                        </div>
                        <span class="fw-bold" id="mobileSidebarTitle">
                            SEKATA | BEM UPNVJ
                        </span>
                    </div>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                        aria-label="Tutup menu"></button>
                </div>

                <div class="offcanvas-body d-flex flex-column p-0">
                    <nav class="sidebar-nav">
                        <a href="{{ route('jadwal.index') }}"
                            class="sidebar-link {{ request()->is('jadwal*') ? 'active' : '' }}">
                            Jadwal bersama
                        </a>

                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.peminjaman.index') }}"
                                class="sidebar-link {{ request()->is('admin/peminjaman*') ? 'active' : '' }}">
                                Antrean SPPI
                            </a>
                        @endif

                        <a href="{{ route('notifikasi.index') }}"
                            class="sidebar-link {{ request()->routeIs('notifikasi.*') ? 'active' : '' }}">
                            Notifikasi
                        </a>
                    </nav>

                    <div class="sidebar-footer">
                        <div class="active-account-box d-flex align-items-center gap-3 mb-2">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.5" class="flex-shrink-0" aria-hidden="true">
                                <circle cx="12" cy="8" r="4"></circle>
                                <path d="M4 21v-2a8 8 0 0 1 16 0v2"></path>
                            </svg>

                            <div class="text-start">
                                <div class="fw-bold">
                                    {{ auth()->user()->unit_name }}
                                </div>
                                <div class="small fw-normal">
                                    {{ auth()->user()->isAdmin() ? 'Admin' : 'Peminjam' }}
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100">
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="app-main-wrapper">
                <header class="sekata-topbar">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sekata-secondary d-lg-none px-2 py-1" style="min-height: 38px;" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar"
                            aria-label="Buka menu">
                            ☰
                        </button>

                        <span class="fw-medium" style="font-size: 14px; color: var(--ink);">
                            Sekretariat BEM UPNVJ
                        </span>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        @hasSection('notifications')
                            @yield('notifications')
                        @else
                            @include('partials.notification-badge')
                        @endif
                    </div>
                </header>

                <main class="sekata-content">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Tutup pemberitahuan"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"
                                aria-label="Tutup pemberitahuan"></button>
                        </div>
                    @endif

                    @hasSection('content')
                        @yield('content')
                    @else
                            <div class="sekata-card p-4">
                                <h1 class="h3 fw-bold mb-2">
                                    {{ auth()->user()->isAdmin()
                        ? 'Antrean Biro SPPI'
                        : 'Jadwal sekretariat' }}
                                </h1>

                                <p class="text-secondary mb-0">
                                    {{ auth()->user()->isAdmin()
                        ? 'Periksa pengajuan peminjaman yang menunggu keputusan.'
                        : 'Lihat jadwal penggunaan sekretariat oleh seluruh bidang dan biro.' }}
                                </p>
                            </div>
                    @endif
                </main>
            </div>
        </div>
    @else
        @yield('content')
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>