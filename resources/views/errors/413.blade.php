<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Berkas terlalu besar - SEKATA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5" style="max-width: 640px;">
        <div class="sekata-card p-4">
            <h1 class="h5 fw-bold mb-3">Berkas yang dikirim terlalu besar</h1>
            <p class="mb-2">
                Server menolak permintaan ini karena total data melebihi batas
                <strong>{{ ini_get('post_max_size') }}</strong>, sehingga tidak ada data yang tersimpan.
            </p>
            <p class="mb-3">
                Kompres foto terlebih dahulu, lalu ulangi pengiriman. Batas ukuran foto pada
                formulir bukti pengembalian adalah {{ intdiv((int) config('proof.photo_max_kb'), 1024) }} MB per foto.
            </p>
            <a href="{{ url()->previous() }}" class="btn btn-sekata-primary btn-sm">Kembali ke halaman sebelumnya</a>
        </div>
    </main>
</body>
</html>
