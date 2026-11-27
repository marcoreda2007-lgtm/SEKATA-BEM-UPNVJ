# Bukti verifikasi bukti pengembalian — 2510512127

Data di bawah ini adalah **data uji lokal**, bukan data operasional. Waktu yang tampil di halaman adalah **waktu server menerima unggahan**, bukan waktu foto diambil.

## Data uji

| Booking | Pemilik | Aktivitas | Status awal | Tujuan |
| --- | --- | --- | --- | --- |
| 6 | `medkominfo@sekata.test` | `Demo bukti pengembalian 2510512127 (data uji, bukan operasional)` | `Disetujui` | Alur unggah, unduh, perbaikan satu foto, dan `Selesai` |
| 7 | `medkominfo@sekata.test` | `Demo batas ukuran unggah 2510512127 (data uji, bukan operasional)` | `Disetujui` | Uji batas ukuran 6 MB dan 45 MB |

Akun lain yang dipakai: `sppi@sekata.test` (admin) dan `sospol@sekata.test` (unit lain). Kata sandi development sesuai `SEED_USER_PASSWORD` lokal (`password` pada mesin ini).

## Konfigurasi PHP yang dipakai server web

Dicek melalui file PHP sementara yang diakses lewat Apache, bukan hanya CLI:

```json
{
  "sapi": "apache2handler",
  "loaded_ini": "C:\\xampp\\php\\php.ini",
  "upload_max_filesize": "40M",
  "post_max_size": "40M",
  "memory_limit": "512M"
}
```

Batas aplikasi tetap **5 MB per foto**, lebih rendah dari batas server. Tidak ada `php.ini` yang diubah.

## Hasil browser nyata

| Skenario | Hasil | Bukti |
| --- | --- | --- |
| Unggah dua PNG nyata sebagai pemilik booking 6 | Status menjadi `Periksa Bukti`; dua tautan **Unduh foto** muncul | [proof-uploaded-desktop.png](proof-uploaded-desktop.png) |
| Unduh sebagai pemilik | `200`, `Content-Disposition: attachment; filename=bukti-ruangan.png`, `image/png`, 15.513 byte | — |
| Unduh sebagai admin | `200`, `Content-Disposition: attachment; filename=bukti-kunci.png`, 14.563 byte | — |
| Akun unit lain membuka detail dan unduh | Detail `403`, unduh `403` | — |
| Akses langsung `/storage/booking-proofs/...` | `403`; file tetap di `storage/app/private` | — |
| SPPI meminta perbaikan `kunci` | Status `Perlu Perbaikan`, hanya input `kunci` muncul, foto `ruangan` tetap ada | — |
| Pemilik mengganti `kunci` | Hanya baris `kunci` berubah; `ruangan` dan notification tetap konsisten | — |
| SPPI menandai `Selesai`, lalu pemilik mencoba submit lagi | Form hilang dan pesan `Bukti hanya dapat dikirim saat peminjaman berstatus...` muncul | [proof-after-selesai-desktop.png](proof-after-selesai-desktop.png) |
| Unggah PNG 6 MB lewat browser | Pesan `Ukuran foto maksimal 5 MB.`; booking tetap `Disetujui`, tidak ada file tersimpan | [proof-6mb-desktop.png](proof-6mb-desktop.png) |
| Unggah PNG 45 MB lewat browser | Apache/Laravel mengembalikan `413` dengan pesan `Berkas yang dikirim terlalu besar`; body ditolak, `booking_proofs` tetap 0 | [proof-413-desktop.png](proof-413-desktop.png) |
| Tampilan mobile 390 px | Form unggah dan status akhir terbaca tanpa overflow | [proof-form-mobile.png](proof-form-mobile.png), [proof-selesai-mobile.png](proof-selesai-mobile.png) |

Catatan: pada 413, XAMPP lokal dengan `display_startup_errors=On` menampilkan satu baris peringatan PHP di atas halaman aplikasi. Itu berasal dari konfigurasi development XAMPP, bukan dari view 413; matikan `display_startup_errors` di lingkungan produksi.

## Perintah verifikasi otomatis

```powershell
php artisan test
git diff --check
```

Hasil terakhir: `Tests: 52 passed (290 assertions)`, `TEST_EXIT=0`, `DIFF_CHECK_EXIT=0`.
