<?php

namespace App\Http\Controllers;

use App\Models\MasterTandaTangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MasterTandaTanganController extends Controller
{
    /**
     * Simpan master tanda tangan untuk user saat ini (dari halaman tanda tangani).
     * Mendukung tipe text (font cursive) dan image (upload PNG).
     */
    public function store(Request $request)
    {
        $type = $request->input('type', 'text');

        $rules = [
            'type' => 'nullable|in:text,image',
            'nama_lengkap' => 'required|string|max:255',
            'inisial' => 'nullable|string|max:20',
            'font_style' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_default' => 'nullable|boolean',
        ];

        // Jika type = image, file_ttd wajib
        if ($type === 'image') {
            $rules['file_ttd'] = 'required|image|mimes:png|max:2048';
        }

        $validated = $request->validate($rules);

        $user = auth()->user();
        $isDefault = (bool) ($validated['is_default'] ?? true);

        if ($isDefault) {
            $user->masterTandaTangan()->update(['is_default' => false]);
        }

        $filePath = null;
        if ($type === 'image' && $request->hasFile('file_ttd')) {
            $filePath = $request->file('file_ttd')->store('tanda_tangan', 'public');
        }

        $master = $user->masterTandaTangan()->create([
            'type' => $type,
            'nama_lengkap' => $validated['nama_lengkap'],
            'inisial' => $validated['inisial'] ?? null,
            'font_style' => $validated['font_style'] ?? '1',
            'color' => $validated['color'] ?? '#000000',
            'file_path' => $filePath,
            'is_default' => $isDefault,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Master tanda tangan disimpan.',
            'master' => [
                'id' => $master->id,
                'type' => $master->type,
                'nama_lengkap' => $master->nama_lengkap,
                'inisial' => $master->inisial,
                'font_style' => $master->font_style,
                'color' => $master->color,
                'file_path' => $master->file_path,
                'url' => $master->url,
                'is_default' => $master->is_default,
            ],
        ]);
    }

    /**
     * Hapus master tanda tangan.
     */
    public function destroy(MasterTandaTangan $master_tanda_tangan)
    {
        $user = auth()->user();

        // Pastikan milik user yang login
        if ($master_tanda_tangan->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Tidak diizinkan.'], 403);
        }

        // Hapus file jika ada
        if ($master_tanda_tangan->file_path) {
            Storage::disk('public')->delete($master_tanda_tangan->file_path);
        }

        $master_tanda_tangan->delete();

        return response()->json(['success' => true, 'message' => 'Master tanda tangan dihapus.']);
    }
}
