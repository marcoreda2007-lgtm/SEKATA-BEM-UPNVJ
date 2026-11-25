<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingProof;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BookingProofController extends Controller
{
    private const CATEGORIES = ['ruangan', 'kunci'];

    public function store(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureOwner($request, $booking);

        // Bentuk file divalidasi sebelum apa pun disimpan, untuk kedua alur.
        $request->validate([
            'ruangan' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'kunci' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'category' => ['nullable', 'in:ruangan,kunci'],
        ]);

        $storedPaths = [];
        $replaced = false;
        $oldPathToDelete = null;

        try {
            DB::transaction(function () use ($request, $booking, &$storedPaths, &$replaced, &$oldPathToDelete): void {
                // Modul ini tidak menyentuh rooms, jadi cukup booking yang dikunci.
                // Urutan rooms -> booking milik Reda/Nabil tidak dilanggar.
                $locked = Booking::query()->lockForUpdate()->findOrFail($booking->id);

                if ($locked->status === 'Disetujui') {
                    $this->storeInitialProofs($locked, $request, $storedPaths);

                    return;
                }

                if ($locked->status === 'Perlu Perbaikan') {
                    $oldPathToDelete = $this->replaceRequestedProof($locked, $request, $storedPaths);
                    $replaced = true;

                    return;
                }

                throw ValidationException::withMessages([
                    'booking' => 'Bukti hanya dapat dikirim saat peminjaman berstatus Disetujui atau Perlu Perbaikan.',
                ]);
            });
        } catch (Throwable $exception) {
            // File baru dibersihkan; file lama belum disentuh karena penggantian gagal.
            Storage::disk('local')->delete($storedPaths);

            throw $exception;
        }

        // Transaksi sudah commit. Kegagalan membersihkan file lama dicatat, tetapi
        // tidak boleh membatalkan file baru dan baris database yang sudah tersimpan.
        if ($oldPathToDelete !== null) {
            try {
                Storage::disk('local')->delete($oldPathToDelete);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return back()->with('success', $replaced
            ? 'Foto pengganti berhasil dikirim untuk diperiksa kembali.'
            : 'Bukti pengembalian berhasil dikirim untuk diperiksa SPPI.');
    }

    public function show(Request $request, Booking $booking, string $category): StreamedResponse
    {
        $this->ensureOwnerOrAdmin($request, $booking);

        abort_unless(in_array($category, self::CATEGORIES, true), 404);

        $proof = $booking->proofs()->where('category', $category)->firstOrFail();

        return Storage::disk('local')->response(
            $proof->path,
            $proof->original_name,
            [
                'Content-Type' => $proof->mime_type ?? 'application/octet-stream',
                'Cache-Control' => 'private, no-store, max-age=0',
            ],
        );
    }

    public function inspect(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'decision' => ['required', 'in:selesai,perbaikan'],
            'inspection_note' => ['nullable', 'string', 'max:2000', 'required_if:decision,perbaikan'],
            'requested_category' => ['nullable', 'in:ruangan,kunci', 'required_if:decision,perbaikan'],
        ]);

        DB::transaction(function () use ($booking, $validated): void {
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            if ($locked->status !== 'Periksa Bukti') {
                throw ValidationException::withMessages([
                    'booking' => 'Hanya bukti yang sedang diperiksa yang dapat diberi keputusan.',
                ]);
            }

            if ($locked->proofs()->count() !== count(self::CATEGORIES)) {
                throw ValidationException::withMessages([
                    'booking' => 'Dua foto bukti belum lengkap.',
                ]);
            }

            $locked->proofs()->update(['requires_replacement' => false]);

            if ($validated['decision'] === 'perbaikan') {
                $locked->proofs()
                    ->where('category', $validated['requested_category'])
                    ->update(['requires_replacement' => true]);

                $locked->update([
                    'status' => 'Perlu Perbaikan',
                    'inspection_note' => $validated['inspection_note'],
                ]);
            } else {
                $locked->update([
                    'status' => 'Selesai',
                    'inspection_note' => $validated['inspection_note'] ?? null,
                ]);
            }

            // Notifikasi ikut transaksi: rollback membatalkan status sekaligus notifikasi.
            NotificationService::notifyProofReviewed($locked);
        });

        return back()->with('success', 'Hasil pemeriksaan bukti berhasil disimpan.');
    }

    /**
     * @param  array<int, string>  $storedPaths
     */
    private function storeInitialProofs(Booking $booking, Request $request, array &$storedPaths): void
    {
        $files = [];

        foreach (self::CATEGORIES as $category) {
            $file = $request->file($category);

            if (! $file) {
                throw ValidationException::withMessages([
                    $category => 'Dua foto bukti wajib diunggah.',
                ]);
            }

            $files[$category] = $file;
        }

        foreach ($files as $category => $file) {
            $path = $this->putProofFile($booking, $file, $storedPaths);

            $booking->proofs()->updateOrCreate(
                ['category' => $category],
                $this->proofAttributes($file, $path),
            );
        }

        $booking->update([
            'status' => 'Periksa Bukti',
            'inspection_note' => null,
        ]);
    }

    /**
     * @param  array<int, string>  $storedPaths
     * @return string Path file lama yang boleh dihapus setelah commit.
     */
    private function replaceRequestedProof(Booking $booking, Request $request, array &$storedPaths): string
    {
        $category = $request->input('category');
        $file = in_array($category, self::CATEGORIES, true) ? $request->file($category) : null;

        if (! $file) {
            throw ValidationException::withMessages([
                'category' => 'Pilih kategori foto yang diminta untuk diperbaiki.',
            ]);
        }

        // Baca ulang bukti di bawah penguncian booking; objek dari luar transaksi bisa usang.
        $proof = $booking->proofs()
            ->where('category', $category)
            ->lockForUpdate()
            ->first();

        if (! $proof || ! $proof->requires_replacement) {
            throw ValidationException::withMessages([
                'category' => 'Foto ini tidak diminta untuk diperbaiki.',
            ]);
        }

        $oldPath = $proof->path;
        $path = $this->putProofFile($booking, $file, $storedPaths);

        $proof->update($this->proofAttributes($file, $path));

        $booking->update([
            'status' => 'Periksa Bukti',
            'inspection_note' => null,
        ]);

        return $oldPath;
    }

    /**
     * @param  array<int, string>  $storedPaths
     */
    private function putProofFile(Booking $booking, UploadedFile $file, array &$storedPaths): string
    {
        $path = Storage::disk('local')->putFile("booking-proofs/{$booking->id}", $file);

        if (! $path) {
            throw ValidationException::withMessages([
                'file' => 'Foto gagal disimpan. Silakan coba lagi.',
            ]);
        }

        // Dicatat segera supaya kegagalan berikutnya ikut menghapus file ini.
        $storedPaths[] = $path;

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function proofAttributes(UploadedFile $file, string $path): array
    {
        return [
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            // Waktu server menerima file, bukan waktu foto diambil.
            'uploaded_at' => now(),
            'requires_replacement' => false,
        ];
    }

    private function ensureOwner(Request $request, Booking $booking): void
    {
        abort_unless(
            $request->user() !== null && (int) $request->user()->id === (int) $booking->user_id,
            403,
        );
    }

    private function ensureOwnerOrAdmin(Request $request, Booking $booking): void
    {
        abort_unless(
            $request->user()?->isAdmin() || (int) $request->user()?->id === (int) $booking->user_id,
            403,
        );
    }
}
