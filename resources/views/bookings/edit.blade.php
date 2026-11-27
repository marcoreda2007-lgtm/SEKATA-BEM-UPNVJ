@extends('layouts.app')

@section('title', 'Edit Pengajuan Peminjaman')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h2 class="fw-bold">Edit Pengajuan Peminjaman</h2>
        <p class="text-muted">Perbarui detail kegiatan yang diajukan.</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('peminjaman.update', $booking->id) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nama PIC</label>
                    <input
                        type="text"
                        name="pic_name"
                        class="form-control"
                        value="{{ old('pic_name', $booking->pic_name) }}"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi kegiatan</label>
                    <textarea
                        name="activity_description"
                        class="form-control"
                        rows="3"
                        required
                    >{{ old('activity_description', $booking->activity_description) }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanggal peminjaman</label>
                    <input
                        type="date"
                        name="tanggal"
                        class="form-control"
                        value="{{ old('tanggal', $booking->tanggal->format('Y-m-d')) }}"
                        required
                    >
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Waktu mulai</label>
                        <input
                            type="time"
                            name="start_time"
                            class="form-control"
                            value="{{ old('start_time', substr($booking->start_time, 0, 5)) }}"
                            required
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Waktu selesai</label>
                        <input
                            type="time"
                            name="end_time"
                            class="form-control"
                            value="{{ old('end_time', substr($booking->end_time, 0, 5)) }}"
                            required
                        >
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input
                        type="checkbox"
                        name="responsibility_acknowledgement"
                        value="1"
                        class="form-check-input"
                        id="responsibility"
                        required
                        {{ old('responsibility_acknowledgement') ? 'checked' : '' }}
                    >
                    <label class="form-check-label" for="responsibility">
                        Saya menyatakan bertanggung jawab atas penggunaan ruangan selama masa peminjaman.
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>

                <a href="{{ route('peminjaman.show', $booking->id) }}" class="btn btn-outline-secondary">
                    Batal
                </a>
            </form>
        </div>
    </div>
</div>
@endsection