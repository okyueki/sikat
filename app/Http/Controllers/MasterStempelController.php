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
     * Supports both file upload and base64 cropped image.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'nullable|string|max:255',
            'file_stempel' => 'nullable|file|mimes:png|max:2048',
            'cropped_image' => 'nullable|string',
            'keterangan' => 'nullable|string|max:500',
            'qr_position' => 'nullable|string|in:bottom_right,bottom_left,top_right,top_left',
        ], [], [
            'nama' => 'Nama stempel',
            'file_stempel' => 'File stempel (PNG)',
            'keterangan' => 'Keterangan',
            'qr_position' => 'Posisi QR verifikasi',
        ]);

        $stempel = MasterStempel::getPerusahaan();
        if (! $stempel) {
            $stempel = new MasterStempel;
        }

        $stempel->nama = $validated['nama'] ?? null;
        $stempel->keterangan = $validated['keterangan'] ?? null;
        $stempel->qr_position = $validated['qr_position'] ?? 'bottom_right';

        // Handle cropped image (base64) from JavaScript cropper
        if (!empty($validated['cropped_image']) && str_starts_with($validated['cropped_image'], 'data:image')) {
            // Delete old file if exists
            if ($stempel->file_path && Storage::disk('public')->exists($stempel->file_path)) {
                Storage::disk('public')->delete($stempel->file_path);
            }
            
            // Decode base64 and save
            $imageData = explode(',', $validated['cropped_image']);
            if (count($imageData) === 2) {
                $decoded = base64_decode($imageData[1]);
                $fileName = 'stempel_' . time() . '.png';
                $stempel->file_path = 'stempel/' . $fileName;
                Storage::disk('public')->put($stempel->file_path, $decoded);
            }
        }
        // Handle regular file upload
        elseif ($request->hasFile('file_stempel')) {
            if ($stempel->file_path && Storage::disk('public')->exists($stempel->file_path)) {
                Storage::disk('public')->delete($stempel->file_path);
            }
            $stempel->file_path = $request->file('file_stempel')->store('stempel', 'public');
        }

        $stempel->save();

        return redirect()->route('master_stempel.edit')->with('success', 'Stempel perusahaan berhasil disimpan.');
    }
}
