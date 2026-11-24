<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingProof;
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

        if ($booking->status === 'Disetujui') {
            $validated = $request->validate([
                'ruangan' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
                'kunci' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            ]);

            $this->storeInitialProofs($booking, $validated);

            return back()->with('success', 'Bukti pengembalian berhasil dikirim untuk diperiksa SPPI.');
        }

        if ($booking->status === 'Perlu Perbaikan') {
            $category = $request->validate([
                'category' => ['required', 'in:ruangan,kunci'],
            ])['category'];

            $proof = $booking->proofs()
                ->where('category', $category)
                ->where('requires_replacement', true)
                ->first();

            if (! $proof) {
                throw ValidationException::withMessages([
                    'category' => 'Foto ini tidak diminta untuk diperbaiki.',
                ]);
            }

            $validated = $request->validate([
                $category => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            ]);

            $this->replaceProof($booking, $proof, $validated[$category]);

            return back()->with('success', 'Foto pengganti berhasil dikirim untuk diperiksa kembali.');
        }

        throw ValidationException::withMessages([
            'booking' => 'Bukti hanya dapat dikirim saat peminjaman berstatus Disetujui atau Perlu Perbaikan.',
        ]);
    }

    public function show(Request $request, Booking $booking, string $category): StreamedResponse
    {
        $this->ensureOwnerOrAdmin($request, $booking);

        abort_unless(in_array($category, self::CATEGORIES, true), 404);

        $proof = $booking->proofs()->where('category', $category)->firstOrFail();

        return Storage::disk('local')->response(
            $proof->path,
            $proof->original_name,
            ['Content-Type' => $proof->mime_type],
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

                return;
            }

            $locked->update([
                'status' => 'Selesai',
                'inspection_note' => $validated['inspection_note'] ?? null,
            ]);
        });

        return back()->with('success', 'Hasil pemeriksaan bukti berhasil disimpan.');
    }

    /**
     * @param array{ruangan: UploadedFile, kunci: UploadedFile} $files
     */
    private function storeInitialProofs(Booking $booking, array $files): void
    {
        $storedPaths = [];

        try {
            DB::transaction(function () use ($booking, $files, &$storedPaths): void {
                foreach ($files as $category => $file) {
                    $storedPaths[] = $this->storeProof($booking, $category, $file);
                }

                $booking->update([
                    'status' => 'Periksa Bukti',
                    'inspection_note' => null,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);

            throw $exception;
        }
    }

    private function replaceProof(Booking $booking, BookingProof $proof, UploadedFile $file): void
    {
        $oldPath = $proof->path;
        $newPath = null;

        try {
            DB::transaction(function () use ($booking, $proof, $file, &$newPath): void {
                $newPath = Storage::disk('local')->putFile(
                    "booking-proofs/{$proof->booking_id}",
                    $file,
                );

                $proof->update([
                    'path' => $newPath,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                    'size' => $file->getSize(),
                    'uploaded_at' => now(),
                    'requires_replacement' => false,
                ]);

                $booking->update([
                    'status' => 'Periksa Bukti',
                    'inspection_note' => null,
                ]);
            });
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }

            throw $exception;
        }

        Storage::disk('local')->delete($oldPath);
    }

    private function storeProof(Booking $booking, string $category, UploadedFile $file): string
    {
        $path = Storage::disk('local')->putFile("booking-proofs/{$booking->id}", $file);

        $booking->proofs()->updateOrCreate(
            ['category' => $category],
            [
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
                'uploaded_at' => now(),
                'requires_replacement' => false,
            ],
        );

        return $path;
    }

    private function ensureOwner(Request $request, Booking $booking): void
    {
        abort_unless($request->user()?->id === $booking->user_id, 403);
    }

    private function ensureOwnerOrAdmin(Request $request, Booking $booking): void
    {
        abort_unless(
            $request->user()?->isAdmin() || $request->user()?->id === $booking->user_id,
            403,
        );
    }
}
