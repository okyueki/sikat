<?php

namespace App\Http\Controllers;

use App\Models\MasterStempel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MasterStempelController extends Controller
{
    /**
     * Form upload/edit stempel perusahaan (satu record).
     */
    public function edit()
    {
        $stempel = MasterStempel::getPerusahaan();

        return view('master_stempel.edit', compact('stempel'));
    }

    /**
     * Simpan/update stempel: nama, file PNG, keterangan.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'nullable|string|max:255',
            'file_stempel' => 'nullable|file|mimes:png|max:2048',
            'keterangan' => 'nullable|string|max:500',
        ], [], [
            'nama' => 'Nama stempel',
            'file_stempel' => 'File stempel (PNG)',
            'keterangan' => 'Keterangan',
        ]);

        $stempel = MasterStempel::getPerusahaan();
        if (! $stempel) {
            $stempel = new MasterStempel;
        }

        $stempel->nama = $validated['nama'] ?? null;
        $stempel->keterangan = $validated['keterangan'] ?? null;

        if ($request->hasFile('file_stempel')) {
            if ($stempel->file_path && Storage::disk('public')->exists($stempel->file_path)) {
                Storage::disk('public')->delete($stempel->file_path);
            }
            $stempel->file_path = $request->file('file_stempel')->store('stempel', 'public');
        }

        $stempel->save();

        return redirect()->route('master_stempel.edit')->with('success', 'Stempel perusahaan berhasil disimpan.');
    }
}
