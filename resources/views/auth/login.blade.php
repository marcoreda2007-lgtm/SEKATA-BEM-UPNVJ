@extends('layouts.app')

@section('title', 'Masuk - SEKATA · BEM UPNVJ')

@push('styles')
<style>
    .login-screen {
        min-height: 100vh;
        background-color: #103A2B;
        border-top: 5px solid #F28C28;
        color: #ffffff;
        display: flex;
        align-items: center;
        padding: 32px 0;
    }
    .login-card {
        background-color: #ffffff;
        color: #16261F;
        border-radius: 16px;
        padding: 36px 32px;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
    }
    .quick-role-card {
        border: 1px solid #DCE5DF;
        border-radius: 12px;
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        transition: border-color 0.15s ease, background-color 0.15s ease;
    }
    .quick-role-card:hover {
        border-color: #146344;
        background-color: #F8FBF9;
    }
    .btn-buka {
        background-color: #ffffff;
        border: 1px solid #C9D6CE;
        color: #16261F;
        font-weight: 600;
        font-size: 13px;
        min-height: 36px;
        border-radius: 8px;
        padding: 6px 14px;
        white-space: nowrap;
    }
    .btn-buka:hover {
        background-color: #146344;
        border-color: #146344;
        color: #ffffff;
    }
    .phoenix-badge {
        width: 140px;
        height: 140px;
        border-radius: 24px;
        background: radial-gradient(circle at 30% 30%, rgba(242, 140, 40, 0.22), rgba(20, 99, 68, 0.15));
        border: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 28px 0;
    }
</style>
@endpush

@section('content')
<div class="login-screen">
    <div class="container" style="max-width: 1080px;">
        <div class="row align-items-center g-5">
            {{-- Left Branding Column (Mulai demo.png) --}}
            <div class="col-12 col-lg-6">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="brand-emblem" style="width: 44px; height: 44px;">
                        <img src="{{ asset('images/logo-bem.png') }}" alt="BEM UPNVJ" onerror="this.style.display='none'; this.parentElement.textContent='BEM';">
                    </div>
                    <span class="fw-semibold" style="font-size: 14px; letter-spacing: 0.04em; color: #E2EFE8;">BEM UPNVJ</span>
                </div>

                <div class="phoenix-badge">
                    <img src="{{ asset('images/logo-gelora.png') }}" alt="Gelora Juang" style="max-width: 110px; max-height: 110px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <span style="display: none; font-size: 13px; font-weight: 700; letter-spacing: 0.12em; color: #F28C28; text-align: center;">KABINET<br>GELORA JUANG</span>
                </div>

                <div class="text-uppercase fw-semibold mb-2" style="font-size: 13px; letter-spacing: 0.12em; color: #9BB7A9;">
                    Gelora Juang
                </div>
                <h1 class="fw-extrabold mb-2" style="font-size: clamp(40px, 6vw, 56px); line-height: 1.05; letter-spacing: -0.02em;">
                    SEKATA
                </h1>
                <p class="fw-semibold mb-2" style="font-size: 20px; color: #F2F7F4;">
                    Sekretariat Kita Tertata
                </p>
                <p class="mb-0" style="font-size: 15px; color: #A8C3B5;">
                    Satu jadwal, satu kesepakatan.
                </p>
            </div>

            {{-- Right Login Card Column --}}
            <div class="col-12 col-lg-6">
                <div class="login-card">
                    <h2 class="fw-bold mb-1" style="font-size: 28px; letter-spacing: -0.01em;">Masuk ke SEKATA</h2>
                    <p class="mb-4" style="font-size: 14px; color: #53685E;">
                        Masuk dengan akun resmi Bidang atau Biro BEM UPNVJ.
                    </p>

                    @if($errors->any())
                        <div class="alert alert-danger py-2 small" style="border-radius: 8px;">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.attempt') }}" id="loginForm">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold" style="font-size: 14px;">Email Unit</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="contoh: ekraf@sekata.test"
                                required
                                autofocus
                            >
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold" style="font-size: 14px;">Kata Sandi</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control"
                                placeholder="Masukkan kata sandi"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-sekata-primary w-100 mb-4">
                            Masuk
                        </button>
                    </form>

                    {{-- Quick-fill demo accounts matching Mulai demo.png --}}
                    <div class="d-flex flex-column gap-2">
                        <div class="quick-role-card">
                            <div>
                                <div class="fw-bold" style="font-size: 14px;">Ekraf</div>
                                <div style="font-size: 12px; color: #53685E;">Cek slot dan ajukan jadwal</div>
                            </div>
                            <button type="button" class="btn-buka" onclick="fillAccount('ekraf@sekata.test')">Isi →</button>
                        </div>

                        <div class="quick-role-card">
                            <div>
                                <div class="fw-bold" style="font-size: 14px;">Pengkeima</div>
                                <div style="font-size: 12px; color: #53685E;">Lihat jadwal dan ajukan peminjaman</div>
                            </div>
                            <button type="button" class="btn-buka" onclick="fillAccount('pengkeima@sekata.test')">Isi →</button>
                        </div>

                        <div class="quick-role-card">
                            <div>
                                <div class="fw-bold" style="font-size: 14px;">Biro SPPI</div>
                                <div style="font-size: 12px; color: #53685E;">Kelola antrean jadwal dan bukti pengembalian</div>
                            </div>
                            <button type="button" class="btn-buka" onclick="fillAccount('sppi@sekata.test')">Isi →</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function fillAccount(email) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = 'password';
        document.getElementById('email').focus();
    }
</script>
@endpush