@extends('layouts.app')

@section('title', 'Penitipan Barang - SEKATA | BEM UPNVJ')

@section('content')
<h1 class="h3 mb-2">Penitipan Barang</h1>
<p class="text-secondary mb-4">
    {{ auth()->user()->isAdmin() ? 'Pantau barang titipan dari seluruh bidang dan biro.' : 'Catat barang yang dititipkan di sekretariat tanpa perlu meminjam ruangan.' }}
</p>

@if ($errors->has('collection') || $errors->has('confirmed') || $errors->has('archive'))
    <div class="alert alert-danger" role="alert">
        {{ $errors->first('collection') ?: ($errors->first('confirmed') ?: $errors->first('archive')) }}
    </div>
@endif

@if (auth()->user()->isUnit())
    <section class="sekata-card p-3 p-md-4 mb-4" aria-labelledby="form-penitipan">
        <h2 class="h5 mb-3" id="form-penitipan">Titipkan barang</h2>
        @if ($errors->any() && ! $errors->has('collection') && ! $errors->has('confirmed') && ! $errors->has('archive'))
            <div class="alert alert-danger" role="alert">
                <p class="mb-1">Penitipan belum tersimpan. Periksa isian berikut:</p>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('penitipan.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="depositor-name" class="form-label">Nama penitip</label>
                    <input id="depositor-name" name="depositor_name" class="form-control" value="{{ old('depositor_name') }}" maxlength="100" required>
                </div>
                <div class="col-md-6">
                    <label for="deposited-on" class="form-label">Tanggal penitipan</label>
                    <input id="deposited-on" name="deposited_on" type="date" class="form-control" value="{{ old('deposited_on', now('Asia/Jakarta')->format('Y-m-d')) }}" required>
                </div>
                <div class="col-12">
                    <label for="item-name" class="form-label">Nama barang</label>
                    <input id="item-name" name="item_name" class="form-control" value="{{ old('item_name') }}" maxlength="255" required>
                </div>
                <div class="col-12">
                    <label for="item-description" class="form-label">Deskripsi barang</label>
                    <textarea id="item-description" name="description" class="form-control" rows="3" maxlength="2000" aria-describedby="description-help" required>{{ old('description') }}</textarea>
                    <div id="description-help" class="form-text">Cantumkan jumlah dan ciri barang agar mudah dikenali.</div>
                </div>
                <div class="col-12">
                    <label for="deposit-photos" class="form-label">Foto barang</label>
                    <input id="deposit-photos" name="photos[]" type="file" class="form-control" accept="image/jpeg,image/png" multiple required aria-describedby="photos-help photos-error">
                    <div id="photos-help" class="form-text">Pilih 1–5 foto JPG/PNG, maksimal 5 MB per foto. Jika pengiriman gagal, pilih ulang foto.</div>
                    <p id="photos-error" class="text-danger small mt-2" role="alert"></p>
                    <div id="photos-preview" class="row g-2 mt-1" aria-label="Pratinjau foto"></div>
                </div>
            </div>
            <button class="btn btn-sekata-primary mt-3" type="submit">Simpan penitipan</button>
        </form>
    </section>
@endif

<section aria-labelledby="daftar-penitipan">
    <h2 class="h5 mb-3" id="daftar-penitipan">Daftar penitipan</h2>
    @forelse ($deposits as $deposit)
        <article class="sekata-card p-3 p-md-4 mb-3" aria-labelledby="barang-{{ $deposit->id }}">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div class="d-flex align-items-center gap-3">
                    @if ($deposit->photos->first())
                        <img src="{{ route('penitipan.photo', $deposit->photos->first()) }}"
                             alt="Foto barang: {{ $deposit->item_name }}" width="80" height="80"
                             class="rounded object-fit-cover flex-shrink-0" loading="lazy">
                    @endif
                    <div>
                    <span class="badge rounded-pill {{ $deposit->status === 'Dititipkan' ? 'text-bg-warning' : 'text-bg-success' }} px-3 py-2 mb-2">
                        {{ $deposit->status }}
                    </span>
                    <h3 class="h5 mb-1 text-break" id="barang-{{ $deposit->id }}">{{ $deposit->item_name }}</h3>
                    <p class="text-secondary mb-0">{{ $deposit->user->unit_name }}</p>
                    </div>
                </div>
                <div class="align-self-end">
                <button type="button" class="btn btn-sekata-primary" data-bs-toggle="collapse"
                        data-bs-target="#detail-penitipan-{{ $deposit->id }}"
                        aria-controls="detail-penitipan-{{ $deposit->id }}" aria-expanded="false">
                    Lihat detail
                </button>
                </div>
            </div>
            <div class="collapse" id="detail-penitipan-{{ $deposit->id }}">
                <div class="border-top mt-3 pt-3">
            <dl class="row mb-3">
                <dt class="col-sm-3 text-secondary fw-normal">Penitip</dt>
                <dd class="col-sm-9">{{ $deposit->depositor_name }}</dd>
                <dt class="col-sm-3 text-secondary fw-normal">Tanggal penitipan</dt>
                <dd class="col-sm-9">{{ $deposit->deposited_on->format('d/m/Y') }}</dd>
            </dl>
            <p class="text-break">{{ $deposit->description }}</p>
            <div class="row g-2">
                @foreach ($deposit->photos as $photo)
                    <div class="col-6 col-md-3">
                        <a href="{{ route('penitipan.photo', $photo) }}" class="d-block" target="_blank" rel="noopener" aria-label="Buka foto {{ $loop->iteration }}: {{ $deposit->item_name }} (tab baru)">
                            <img src="{{ route('penitipan.photo', $photo) }}" alt="Foto {{ $loop->iteration }}: {{ $deposit->item_name }}" width="240" height="180" class="w-100 rounded object-fit-cover" loading="lazy">
                        </a>
                    </div>
                @endforeach
            </div>
            @if ($deposit->collected_at)
                <div class="border-top mt-3 pt-3">
                    <p class="mb-1">Diambil {{ $deposit->collected_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
                    <p class="small text-secondary mb-0">Dilaporkan oleh penitip.</p>
                    @if ($deposit->archived_at && auth()->user()->isAdmin())
                        <p class="small text-secondary mt-1 mb-0">Sudah dihapus dari daftar pemilik.</p>
                    @endif
                </div>
            @endif
            @if (auth()->user()->isUnit() && (int) auth()->id() === (int) $deposit->user_id && $deposit->status === 'Dititipkan')
                <div class="border-top mt-3 pt-3">
                    <button type="button" class="btn btn-sekata-primary" data-bs-toggle="modal" data-bs-target="#ambil-{{ $deposit->id }}">
                        Tandai sudah diambil
                    </button>
                    <p class="small text-secondary mt-2 mb-0">Klik setelah barang diambil dari sekretariat.</p>
                </div>
                <div class="modal fade" id="ambil-{{ $deposit->id }}" tabindex="-1" aria-labelledby="ambil-title-{{ $deposit->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title h5" id="ambil-title-{{ $deposit->id }}">Konfirmasi pengambilan</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup konfirmasi"></button>
                            </div>
                            <form method="POST" action="{{ route('penitipan.collect', $deposit) }}">
                                @csrf
                                @method('PATCH')
                                <div class="modal-body">
                                    <p>Pastikan <strong>{{ $deposit->item_name }}</strong> sudah diambil dari sekretariat.</p>
                                    <div class="form-check">
                                        <input id="collected-{{ $deposit->id }}" class="form-check-input" type="checkbox" name="confirmed" value="1" required>
                                        <label class="form-check-label" for="collected-{{ $deposit->id }}">Barang ini sudah saya ambil.</label>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-sekata-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-sekata-primary">Konfirmasi pengambilan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
            @if (auth()->user()->isUnit() && (int) auth()->id() === (int) $deposit->user_id && $deposit->status === 'Diambil' && ! $deposit->archived_at)
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#arsip-{{ $deposit->id }}">
                        Hapus dari daftar
                    </button>
                </div>
                <div class="modal fade" id="arsip-{{ $deposit->id }}" tabindex="-1" aria-labelledby="arsip-title-{{ $deposit->id }}" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title h5" id="arsip-title-{{ $deposit->id }}">Hapus dari daftar?</h4>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup konfirmasi"></button>
                            </div>
                            <form method="POST" action="{{ route('penitipan.archive', $deposit) }}">
                                @csrf
                                @method('PATCH')
                                <div class="modal-body">
                                    <p><strong>{{ $deposit->item_name }}</strong> akan hilang dari daftar Anda. Catatan dan foto tetap tersedia untuk SPPI.</p>
                                    <div class="form-check">
                                        <input id="archive-confirmed-{{ $deposit->id }}" class="form-check-input" type="checkbox" name="confirmed" value="1" required>
                                        <label for="archive-confirmed-{{ $deposit->id }}" class="form-check-label">Saya ingin menghapus penitipan ini dari daftar saya.</label>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-sekata-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-danger">Hapus dari daftar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
                </div>
            </div>
        </article>
    @empty
        <p class="sekata-card p-4 text-center text-secondary">Belum ada penitipan barang.</p>
    @endforelse
    {{ $deposits->links('pagination::bootstrap-5') }}
</section>
@endsection

@push('scripts')
<script src="{{ asset('js/item-deposits.js') }}" defer></script>
@endpush
