@extends('layouts.app')

@section('title', 'Kelola Fasilitas Sekretariat - SEKATA | BEM UPNVJ')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <div class="text-uppercase fw-semibold small text-secondary">Administrasi Biro SPPI</div>
            <h1 class="h4 fw-bold mb-0">Fasilitas Sekretariat</h1>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="sekata-card p-3 p-md-4">
                <h2 class="h6 fw-bold mb-3">Tambah Fasilitas Baru</h2>
                <form method="POST" action="{{ route('admin.fasilitas.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Fasilitas</label>
                        <input type="text" id="name" name="name" class="form-control"
                            value="{{ old('name') }}" maxlength="100" required>
                        @error('name')
                            <p class="text-danger small mt-1 mb-0">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi (Opsional)</label>
                        <textarea id="description" name="description" class="form-control" rows="3"
                            maxlength="500">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-danger small mt-1 mb-0">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-sekata-primary w-100">
                        Simpan Fasilitas
                    </button>
                </form>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="sekata-card p-3 p-md-4">
                <h2 class="h6 fw-bold mb-3">Daftar Fasilitas Terdaftar</h2>
                @if($facilities->isEmpty())
                    <p class="text-secondary small mb-0">Belum ada data fasilitas.</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nama Fasilitas</th>
                                    <th>Deskripsi</th>
                                    <th class="text-center">Dipakai</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($facilities as $facility)
                                    <tr>
                                        <td class="fw-semibold">{{ $facility->name }}</td>
                                        <td class="small text-secondary">{{ $facility->description ?? '-' }}</td>
                                        <td class="text-center small">{{ $facility->bookings_count }}x</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.fasilitas.edit', $facility->id) }}"
                                                class="btn btn-sm btn-sekata-secondary">
                                                Edit
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection