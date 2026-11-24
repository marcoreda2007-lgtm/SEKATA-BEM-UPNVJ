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
        padding: 40px 0;
    }
    .bem-header-logo {
        width: 52px;
        height: 52px;
        object-fit: contain;
        mix-blend-mode: lighten;
        flex-shrink: 0;
    }
    .login-card {
        background-color: #ffffff;
        color: #16261F;
        border-radius: 16px;
        padding: 36px 34px;
        box-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
        max-width: 440px;
        margin-left: auto;
        margin-right: auto;
    }
    .phoenix-showcase {
        margin: 22px 0;
        display: flex;
        align-items: center;
    }
    .phoenix-showcase img {
        width: 165px;
        height: auto;
        filter: drop-shadow(0 12px 24px rgba(0, 0, 0, 0.28));
    }
    @media (min-width: 992px) {
        .login-card {
            margin-right: 0;
        }
    }
</style>
@endpush

@section('content')
<div class="login-screen">
    <div class="container" style="max-width: 1040px;">
        <div class="row align-items-center g-4 g-lg-5">
            {{-- Left Branding Column --}}
            <div class="col-12 col-lg-6">
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ asset('images/logo-bem.png') }}" alt="BEM UPNVJ" class="bem-header-logo">
                    <span class="fw-semibold" style="font-size: 15px; letter-spacing: 0.04em; color: #E2EFE8;">BEM UPNVJ</span>
                </div>

                <div class="phoenix-showcase">
                    <img src="{{ asset('images/logo-gelora.webp') }}" alt="Kabinet Gelora Juang">
                </div>

                <div>
                    <div class="text-uppercase fw-semibold mb-2" style="font-size: 13px; letter-spacing: 0.12em; color: #9BB7A9;">
                        Gelora Juang
                    </div>
                    <h1 class="fw-extrabold mb-2" style="font-size: clamp(40px, 6vw, 54px); line-height: 1.05; letter-spacing: -0.02em;">
                        SEKATA
                    </h1>
                    <p class="fw-semibold mb-1" style="font-size: 19px; color: #F2F7F4;">
                        Sekretariat Kita Tertata
                    </p>
                    <p class="mb-0" style="font-size: 15px; color: #A8C3B5;">
                        Satu jadwal, satu kesepakatan.
                    </p>
                </div>
            </div>

            {{-- Right Login Card Column --}}
            <div class="col-12 col-lg-6">
                <div class="login-card">
                    <h2 class="fw-bold mb-1" style="font-size: 26px; letter-spacing: -0.01em;">Masuk ke SEKATA</h2>
                    <p class="mb-4" style="font-size: 14px; color: #53685E;">
                        Gunakan akun resmi Bidang atau Biro BEM UPNVJ.
                    </p>

                    @if($errors->any())
                        <div class="alert alert-danger py-2 small mb-3" style="border-radius: 8px;">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.attempt') }}">
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

                        <button type="submit" class="btn btn-sekata-primary w-100">
                            Masuk
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection