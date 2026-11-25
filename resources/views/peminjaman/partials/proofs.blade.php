@php
    $proofs = $booking->proofs->keyBy('category');
    $isOwner = (int) auth()->id() === (int) $booking->user_id;
    $isAdmin = auth()->user()->isAdmin();
    $labels = [
        'ruangan' => 'Foto Kondisi Ruangan',
        'kunci' => 'Foto Pengembalian Kunci',
    ];
    $requested = $proofs->first(fn ($proof) => $proof->requires_replacement);
@endphp

<div class="sekata-card p-3 p-md-4">
    <h2 class="h6 fw-bold mb-2">Bukti Pengembalian Sekretariat</h2>
    <p class="detail-meta mb-3">
        Waktu di bawah adalah waktu unggah yang dicatat server, bukan waktu foto diambil.
    </p>

    <div class="row g-3 mb-3">
        @foreach ($labels as $category => $label)
            @php $proof = $proofs->get($category); @endphp
            <div class="col-12 col-sm-6">
                <div class="proof-placeholder h-100">
                    <div class="fw-semibold text-dark mb-2">{{ $loop->iteration }}. {{ $label }}</div>

                    @if ($proof)
                        <img src="{{ route('peminjaman.bukti.show', [$booking, $category]) }}"
                             alt="{{ $label }} untuk pengajuan #{{ $booking->id }}"
                             class="img-fluid rounded border mb-2">
                        <div class="detail-meta-sm">
                            Diunggah {{ $proof->uploaded_at->copy()->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
                        </div>
                        @if ($proof->requires_replacement)
                            <span class="pill-perbaikan mt-2 d-inline-block">Perlu diperbaiki</span>
                        @endif
                    @else
                        <div class="detail-meta-sm">Belum diunggah.</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if ($isOwner && $booking->status === 'Disetujui')
        <form method="POST" action="{{ route('peminjaman.bukti.store', $booking) }}" enctype="multipart/form-data">
            @csrf
            @foreach ($labels as $category => $label)
                <div class="mb-2">
                    <label for="bukti-{{ $category }}" class="form-label small fw-semibold">{{ $label }}</label>
                    <input id="bukti-{{ $category }}" type="file" name="{{ $category }}"
                           accept="image/jpeg,image/png"
                           class="form-control form-control-sm @error($category) is-invalid @enderror">
                    @error($category)
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            @endforeach
            <button type="submit" class="btn btn-sekata-primary btn-sm">Kirim bukti</button>
        </form>
    @endif

    @if ($isOwner && $booking->status === 'Perlu Perbaikan')
        @if ($requested)
            <form method="POST" action="{{ route('peminjaman.bukti.store', $booking) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="category" value="{{ $requested->category }}">
                <div class="mb-2">
                    <label for="bukti-{{ $requested->category }}" class="form-label small fw-semibold">
                        Ganti {{ $labels[$requested->category] }}
                    </label>
                    <input id="bukti-{{ $requested->category }}" type="file" name="{{ $requested->category }}"
                           accept="image/jpeg,image/png"
                           class="form-control form-control-sm @error($requested->category) is-invalid @enderror">
                    @error($requested->category)
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @error('category')
                    <div class="alert alert-danger py-2 small">{{ $message }}</div>
                @enderror
                <button type="submit" class="btn btn-sekata-primary btn-sm">Kirim foto pengganti</button>
            </form>
        @else
            <p class="detail-meta mb-0">Tidak ada foto yang diminta untuk diperbaiki.</p>
        @endif
    @endif

    @if ($isAdmin && $booking->status === 'Periksa Bukti')
        <form method="POST" action="{{ route('admin.peminjaman.pemeriksaan.store', $booking) }}">
            @csrf
            <div class="mb-2">
                <label for="keputusan-bukti" class="form-label small fw-semibold">Keputusan pemeriksaan</label>
                <select id="keputusan-bukti" name="decision" class="form-select form-select-sm" required>
                    <option value="selesai">Selesai — kedua bukti diterima</option>
                    <option value="perbaikan">Minta perbaikan satu foto</option>
                </select>
            </div>
            <div class="mb-2">
                <label for="kategori-perbaikan" class="form-label small fw-semibold">Kategori yang diperbaiki</label>
                <select id="kategori-perbaikan" name="requested_category" class="form-select form-select-sm">
                    <option value="">— hanya untuk permintaan perbaikan —</option>
                    @foreach ($labels as $category => $label)
                        <option value="{{ $category }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-2">
                <label for="catatan-pemeriksaan" class="form-label small fw-semibold">Catatan pemeriksaan</label>
                <textarea id="catatan-pemeriksaan" name="inspection_note" rows="2"
                          class="form-control form-control-sm @error('inspection_note') is-invalid @enderror">{{ old('inspection_note') }}</textarea>
                @error('inspection_note')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            @error('booking')
                <div class="alert alert-danger py-2 small">{{ $message }}</div>
            @enderror
            <button type="submit" class="btn btn-sekata-primary btn-sm">Simpan hasil pemeriksaan</button>
        </form>
    @endif
</div>
