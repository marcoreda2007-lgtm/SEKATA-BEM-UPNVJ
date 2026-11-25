<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingDetailController extends Controller
{
    public function show(Request $request, int $id): View
    {
        $booking = Booking::with(['user', 'room', 'decider', 'proofs'])->findOrFail($id);

        $currentUser = $request->user();

        if (! $currentUser->isAdmin() && (int) $booking->user_id !== (int) $currentUser->id) {
            abort(403, 'Anda tidak memiliki izin untuk melihat detail peminjaman unit lain.');
        }

        // Cek apakah data keputusan SPPI benar-benar tersimpan di database
        $hasDecisionData = $booking->decided_at !== null
            || $booking->decided_by !== null
            || filled($booking->decision_reason);

        return view('peminjaman.show', [
            'booking' => $booking,
            'hasDecisionData' => $hasDecisionData,
            'createdAtWib' => $this->formatWib($booking->created_at),
            'decidedAtWib' => $this->formatWib($booking->decided_at),
            'updatedAtWib' => $this->formatWib($booking->updated_at),
        ]);
    }

    // Konversi salinan timestamp pencatatan (UTC) ke zona waktu Asia/Jakarta untuk tampilan
    private function formatWib(?CarbonInterface $timestamp): ?string
    {
        if (! $timestamp) {
            return null;
        }

        return $timestamp->copy()
            ->timezone('Asia/Jakarta')
            ->format('d M Y, H:i') . ' WIB';
    }
}