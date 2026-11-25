<?php

namespace App\Http\Controllers;

use App\Models\ItemDeposit;
use App\Models\ItemDepositPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ItemDepositController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemDeposit::with(['user', 'photos']);

        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id)->whereNull('archived_at');
        }

        return view('penitipan.index', [
            'deposits' => $query->orderByDesc('created_at')->orderByDesc('id')->paginate(10),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isUnit(), 403);

        $data = $request->validate([
            'depositor_name' => ['required', 'string', 'max:100'],
            'item_name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'deposited_on' => ['required', 'date_format:Y-m-d'],
            'photos' => ['required', 'array', 'min:1', 'max:5'],
            'photos.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $paths = [];

        try {
            DB::transaction(function () use ($request, $data, &$paths) {
                $deposit = $request->user()->itemDeposits()->create([
                    'depositor_name' => $data['depositor_name'],
                    'item_name' => $data['item_name'],
                    'description' => $data['description'],
                    'deposited_on' => $data['deposited_on'],
                ]);

                foreach ($request->file('photos') as $file) {
                    $path = Storage::disk('local')->putFile("item-deposits/{$deposit->id}", $file);

                    if (! $path) {
                        throw ValidationException::withMessages([
                            'photos' => 'Foto gagal disimpan. Silakan kirim ulang.',
                        ]);
                    }

                    $paths[] = $path;
                    $deposit->photos()->create([
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            });
        } catch (Throwable $exception) {
            try {
                Storage::disk('local')->delete($paths);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }

        return redirect()->route('penitipan.index')->with('success', 'Penitipan barang berhasil dicatat.');
    }

    public function collect(Request $request, ItemDeposit $deposit)
    {
        abort_unless(
            $request->user()->isUnit() && (int) $deposit->user_id === (int) $request->user()->id,
            403,
        );

        $request->validate(['confirmed' => ['required', 'accepted']]);

        DB::transaction(function () use ($deposit) {
            $locked = ItemDeposit::whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'Dititipkan') {
                throw ValidationException::withMessages([
                    'collection' => 'Barang ini sudah ditandai diambil. Muat ulang halaman.',
                ]);
            }

            $locked->status = 'Diambil';
            $locked->collected_at = now();
            $locked->save();
        });

        return redirect()->route('penitipan.index')->with('success', 'Pengambilan barang berhasil dicatat.');
    }

    public function archive(Request $request, ItemDeposit $deposit)
    {
        abort_unless(
            $request->user()->isUnit() && (int) $deposit->user_id === (int) $request->user()->id,
            403,
        );

        $request->validate(['confirmed' => ['required', 'accepted']]);

        DB::transaction(function () use ($deposit) {
            $locked = ItemDeposit::whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'Diambil') {
                throw ValidationException::withMessages([
                    'archive' => 'Hanya barang yang sudah diambil yang dapat dihapus dari daftar.',
                ]);
            }

            if ($locked->archived_at === null) {
                $locked->archived_at = now();
                $locked->save();
            }
        });

        return redirect()->route('penitipan.index')
            ->with('success', 'Penitipan dihapus dari daftar Anda. Catatan tetap tersedia untuk SPPI.');
    }

    public function photo(Request $request, ItemDepositPhoto $photo)
    {
        abort_unless(
            $request->user()->isAdmin() || (int) $photo->itemDeposit->user_id === (int) $request->user()->id,
            403,
        );

        abort_unless(Storage::disk('local')->exists($photo->path), 404);

        return Storage::disk('local')->response($photo->path, $photo->original_name, [
            'Content-Type' => $photo->mime_type,
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
