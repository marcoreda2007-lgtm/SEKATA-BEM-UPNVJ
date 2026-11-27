<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FacilityController extends Controller
{
    public function index(): View
    {
        $facilities = Facility::withCount('bookings')
            ->orderBy('name')
            ->get();

        return view('admin.fasilitas.index', [
            'facilities' => $facilities,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('facilities', 'name'),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Nama fasilitas wajib diisi.',
            'name.unique' => 'Nama fasilitas sudah terdaftar.',
        ]);

        Facility::create($validated);

        return redirect()
            ->route('admin.fasilitas.index')
            ->with('success', 'Fasilitas baru berhasil ditambahkan.');
    }

    public function edit(Facility $facility): View
    {
        return view('admin.fasilitas.edit', [
            'facility' => $facility,
        ]);
    }

    public function update(Request $request, Facility $facility): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('facilities', 'name')->ignore($facility->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ], [
            'name.required' => 'Nama fasilitas wajib diisi.',
            'name.unique' => 'Nama fasilitas sudah digunakan oleh fasilitas lain.',
        ]);

        $facility->update($validated);

        return redirect()
            ->route('admin.fasilitas.index')
            ->with('success', 'Data fasilitas berhasil diperbarui.');
    }
}