<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingFacilityController extends Controller
{
    public function store(Request $request, Booking $booking): RedirectResponse
    {
        $this->authorizeBookingAccess($request, $booking);

        $validated = $request->validate([
            'facility_id' => [
                'required',
                'integer',
                'exists:facilities,id',
                Rule::unique('booking_facility', 'facility_id')->where(
                    fn ($query) => $query->where('booking_id', $booking->id)
                ),
            ],
        ], [
            'facility_id.required' => 'Pilih fasilitas yang ingin ditambahkan.',
            'facility_id.integer' => 'ID fasilitas tidak valid.',
            'facility_id.exists' => 'Fasilitas yang dipilih tidak ditemukan.',
            'facility_id.unique' => 'Fasilitas tersebut sudah terpasang pada peminjaman ini.',
        ]);

        $booking->facilities()->attach((int) $validated['facility_id']);

        return redirect()
            ->route('peminjaman.show', $booking->id)
            ->with('success', 'Fasilitas berhasil ditambahkan ke peminjaman.');
    }

    public function destroy(Request $request, Booking $booking, Facility $facility): RedirectResponse
    {
        $this->authorizeBookingAccess($request, $booking);

        $booking->facilities()->detach($facility->id);

        return redirect()
            ->route('peminjaman.show', $booking->id)
            ->with('success', 'Fasilitas berhasil dilepas dari peminjaman.');
    }

    private function authorizeBookingAccess(Request $request, Booking $booking): void
    {
        $currentUser = $request->user();

        if (! $currentUser->isAdmin() && (int) $booking->user_id !== (int) $currentUser->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah fasilitas peminjaman unit lain.');
        }
    }
}