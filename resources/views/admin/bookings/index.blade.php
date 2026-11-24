<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antrean Pengajuan | SEKATA</title>
    <link rel="stylesheet" href="{{ asset('css/admin-bookings.css') }}">
</head>
<body>
    <div class="container">
        <p class="identitas">SEKATA - Biro SPPI</p>

        <h1>Antrean Pengajuan</h1>

        <p class="deskripsi">
            Periksa pengajuan peminjaman yang menunggu keputusan.
        </p>

        <table>
            <caption>Daftar pengajuan berstatus Menunggu</caption>

            <thead>
                <tr>
                    <th scope="col">Unit</th>
                    <th scope="col">Kegiatan</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($bookings as $booking)
                    <tr>
                        <td>{{ $booking->user->unit_name }}</td>
                        <td>{{ $booking->activity_description }}</td>
                        <td>
                            <span class="status">
                                {{ $booking->status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">
                            Belum ada pengajuan yang perlu diperiksa.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>