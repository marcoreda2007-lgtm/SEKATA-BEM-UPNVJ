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
    <style>
        :root {
            --hijau-bem: #146344;
            --hijau-gelap: #103A2B;
            --hijau-nav: #1D4B3A;
            --oranye-gelora: #F28C28;
            --bg-canvas: #F3F6F4;
            --ink: #16261F;
            --muted: #53685E;
            --line: #DCE5DF;
            --status-disetujui: #E3F0E7;
            --status-menunggu: #FFF0DD;
            --status-perbaikan: #FAE8E4;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-canvas);
            color: var(--ink);
            min-height: 100vh;
        }
        .btn-sekata-primary {
            background-color: var(--hijau-bem);
            border: 1px solid var(--hijau-bem);
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
            min-height: 44px;
            border-radius: 8px;
            padding: 10px 18px;
            transition: background-color 0.15s ease;
        }
        .btn-sekata-primary:hover {
            background-color: var(--hijau-gelap);
            border-color: var(--hijau-gelap);
            color: #ffffff;
        }
        .btn-sekata-secondary {
            background-color: #ffffff;
            border: 1px solid #C9D6CE;
            color: var(--ink);
            font-weight: 600;
            font-size: 14px;
            min-height: 44px;
            border-radius: 8px;
            padding: 10px 16px;
        }
        .btn-sekata-secondary:hover {
            background-color: #F7FAF8;
            border-color: #9FB3A7;
            color: var(--ink);
        }
        .form-control, .form-select {
            min-height: 44px;
            border: 1px solid #C5D3CB;
            border-radius: 8px;
            font-size: 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--hijau-bem);
            box-shadow: 0 0 0 3px rgba(20, 99, 68, 0.15);
        }
        .sekata-card {
            background: #ffffff;
            border: 1px solid var(--line);
            border-radius: 12px;
        }
        .pill-disetujui {
            background-color: var(--status-disetujui);
            color: #146344;
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .pill-menunggu {
            background-color: var(--status-menunggu);
            color: #9A5510;
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .pill-perbaikan {
            background-color: var(--status-perbaikan);
            color: #9E2A2B;
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Authenticated Sidebar Shell */
        .app-shell {
            display: flex;
            min-height: 100vh;
        }
        .sekata-sidebar {
            width: 256px;
            background-color: var(--hijau-gelap);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        .sidebar-brand {
            padding: 22px 20px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #ffffff;
        }
        .brand-emblem {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .brand-emblem img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            mix-blend-mode: lighten;
        }
        .sidebar-nav {
            padding: 20px 14px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .sidebar-link {
            position: relative;
            display: flex;
            align-items: center;
            min-height: 44px;
            padding: 10px 16px;
            border-radius: 8px;
            color: #A7C2B5;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.15s ease;
        }
        .sidebar-link:hover {
            background-color: rgba(255, 255, 255, 0.06);
            color: #ffffff;
        }
        .sidebar-link.active {
            background-color: var(--hijau-nav);
            color: #ffffff;
            font-weight: 600;
        }
        .sidebar-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            bottom: 8px;
            width: 4px;
            border-radius: 0 4px 4px 0;
            background-color: var(--oranye-gelora);
        }
        .sidebar-footer {
            padding: 16px 14px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
        .active-account-box {
            background-color: #184434;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 600;
            color: #E6F2EC;
            text-align: center;
        }
        .app-main-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .sekata-topbar {
            background-color: #ffffff;
            border-bottom: 1px solid var(--line);
            min-height: 68px;
            padding: 12px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .sekata-content {
            padding: 28px;
            flex-grow: 1;
        }
        @media (max-width: 991.98px) {
            .sekata-sidebar.desktop-only {
                display: none;
            }
            .sekata-topbar {
                padding: 12px 16px;
            }
            .sekata-content {
                padding: 20px 16px;
            }
        }
    </style>
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
                    <a href="{{ url('/admin/peminjaman') }}" class="sidebar-link {{ request()->is('admin/peminjaman*') ? 'active' : '' }}">
                        Antrean SPPI
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                <div class="text-uppercase mb-2" style="font-size: 11px; letter-spacing: 0.06em; color: #8AA899; font-weight: 600;">
                    Akun Aktif
                </div>
                <div class="active-account-box mb-2">
                    {{ auth()->user()->unit_name }} · {{ auth()->user()->isAdmin() ? 'Admin' : 'Peminjam' }}
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm w-100 text-white-50" style="min-height: 38px; border: 1px solid rgba(255,255,255,0.16); border-radius: 8px; font-size: 13px;">
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        {{-- Mobile Offcanvas Drawer --}}
        <div class="offcanvas offcanvas-start text-white" tabindex="-1" id="mobileSidebar" style="background-color: var(--hijau-gelap); width: 260px;">
            <div class="offcanvas-header border-bottom border-secondary border-opacity-25">
                <div class="d-flex align-items-center gap-2">
                    <div class="brand-emblem" style="width: 36px; height: 36px;">
                        <img src="{{ asset('images/logo-bem.jpg') }}" alt="BEM UPNVJ">
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
                        <a href="{{ url('/admin/peminjaman') }}" class="sidebar-link {{ request()->is('admin/peminjaman*') ? 'active' : '' }}">
                            Antrean SPPI
                        </a>
                    @endif
                </nav>
                <div class="sidebar-footer">
                    <div class="active-account-box mb-2">
                        {{ auth()->user()->unit_name }} · {{ auth()->user()->isAdmin() ? 'Admin' : 'Peminjam' }}
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light w-100" style="min-height: 38px;">Keluar</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right Workspace --}}
        <div class="app-main-wrapper">
            <header class="sekata-topbar">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sekata-secondary d-lg-none px-2 py-1" style="min-height: 38px;" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                        ☰
                    </button>
                    <span class="fw-medium" style="font-size: 14px; color: var(--ink);">Sekretariat BEM UPNVJ</span>
                </div>

                <div class="d-flex align-items-center gap-3">
                    {{-- Slot notifikasi untuk modul Adis --}}
                    @yield('notifications')

                    <span class="btn-sekata-secondary d-none d-sm-inline-flex align-items-center py-1 px-3" style="min-height: 38px; font-size: 13px; cursor: default;">
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
                                    Masuk sebagai <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->unit_name }}).
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