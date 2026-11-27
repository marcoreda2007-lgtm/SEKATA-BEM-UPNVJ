@extends('layouts.app')

@section('title', 'Edit Fasilitas - SEKATA | BEM UPNVJ')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <div class="text-uppercase fw-semibold small text-secondary">Administrasi Biro SPPI</div>
            <h1 class="h4 fw-bold mb-0">Edit Fasilitas Sekretariat</h1>
        </div>
        <a href="{{ route('admin.fasilitas.index') }}" class="btn btn-sekata-secondary">
            ← Kembali
        </a>
    </div>

    <div class="sekata-card p-3 p-md-4" style="max-width: 640px;">
        <form method="POST" action="{{ route('admin.fasilitas.update', $facility->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Nama Fasilitas</label>
                <input type="text" id="name" name="name" class="form-control"
                    value="{{ old('name', $facility->name) }}" maxlength="100" required>
                @error('name')
                    <p class="text-danger small mt-1 mb-0">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi (Opsional)</label>
                <textarea id="description" name="description" class="form-control" rows="3"
                    maxlength="500">{{ old('description', $facility->description) }}</textarea>
                @error('description')
                    <p class="text-danger small mt-1 mb-0">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn btn-sekata-primary">
                Simpan Perubahan
            </button>
        </form>
    </div>
@endsection