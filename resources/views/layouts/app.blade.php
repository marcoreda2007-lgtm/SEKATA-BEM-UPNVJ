<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SEKATA · BEM UPNVJ')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>

<body>
    @auth
        <div class="app-shell">
            {{-- Desktop Sidebar --}}
            <aside class="sekata-sidebar desktop-only">
                <a href="{{ url('/') }}" class="sidebar-brand">
                    <div class="brand-emblem">
                        <img src="{{ asset('images/logo-bem.png') }}" alt="BEM UPNVJ">
                    </div>
                    <div>
                        <div class="fw-bold lh-1" style="font-size: 18px; letter-spacing: 0.04em;">SEKATA</div>
                        <div style="font-size: 11px; color: #A7C2B5; margin-top: 3px;">BEM UPNVJ</div>
                    </div>
                </a>

                <nav class="sidebar-nav">
                    <a href="{{ url('/jadwal') }}" class="sidebar-link {{ request()->is('jadwal*') ? 'active' : '' }}">
                        Jadwal bersama
                    </a>

                    @if(auth()->user()->isAdmin())
                        <a href="{{ url('/admin/peminjaman') }}"
                            class="sidebar-link {{ request()->is('admin/peminjaman*') ? 'active' : '' }}">
                            Antrean SPPI
                        </a>
                    @endif
                </nav>

                <div class="sidebar-footer">
                    <div class="text-uppercase mb-2"
                        style="font-size: 11px; letter-spacing: 0.06em; color: #8AA899; font-weight: 600;">
                        Akun Aktif
                    </div>
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

            {{-- Mobile Offcanvas Drawer --}}
            <div class="offcanvas offcanvas-start text-white" tabindex="-1" id="mobileSidebar"
                style="background-color: var(--hijau-gelap); width: 260px;">
                <div class="offcanvas-header border-bottom border-secondary border-opacity-25">
                    <div class="d-flex align-items-center gap-2">
                        <div class="brand-emblem" style="width: 36px; height: 36px;">
                            <img src="{{ asset('images/logo-bem.png') }}" alt="BEM UPNVJ">
                        </div>
                        <span class="fw-bold">SEKATA · BEM UPNVJ</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
                </div>
                <div class="offcanvas-body d-flex flex-column p-0">
                    <nav class="sidebar-nav">
                        <a href="{{ url('/jadwal') }}" class="sidebar-link {{ request()->is('jadwal*') ? 'active' : '' }}">
                            Jadwal bersama
                        </a>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ url('/admin/peminjaman') }}"
                                class="sidebar-link {{ request()->is('admin/peminjaman*') ? 'active' : '' }}">
                                Antrean SPPI
                            </a>
                        @endif
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

            {{-- Right Workspace --}}
            <div class="app-main-wrapper">
                <header class="sekata-topbar">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sekata-secondary d-lg-none px-2 py-1" style="min-height: 38px;" type="button"
                            data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                            ☰
                        </button>
                        <span class="fw-medium" style="font-size: 14px; color: var(--ink);">Sekretariat BEM UPNVJ</span>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        {{-- Slot notifikasi untuk modul Adis --}}
                        @yield('notifications')

                        <span class="btn-sekata-secondary d-none d-sm-inline-flex align-items-center py-1 px-3"
                            style="min-height: 38px; font-size: 13px; cursor: default;">
                            Akun aktif
                        </span>
                        <span class="fw-semibold" style="font-size: 12px; color: var(--muted);">WIB</span>
                    </div>
                </header>

                <main class="sekata-content">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @hasSection('content')
                        @yield('content')
                    @else
                        <div class="sekata-card p-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div>
                                    <h1 class="h3 fw-bold mb-1">
                                        {{ auth()->user()->isAdmin() ? 'Antrean Biro SPPI' : 'Jadwal sekretariat' }}
                                    </h1>
                                    <p class="mb-0" style="color: var(--muted); font-size: 14px;">
                                        Masuk sebagai <strong>{{ auth()->user()->name }}</strong>
                                        ({{ auth()->user()->unit_name }}).
                                    </p>
                                </div>
                                <span class="pill-disetujui">Role: {{ strtoupper(auth()->user()->role) }}</span>
                            </div>
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