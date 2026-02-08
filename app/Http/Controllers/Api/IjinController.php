<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengajuanLibur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class IjinController extends Controller
{
    /**
     * GET /api/ijin
     * Daftar pengajuan ijin. Query: per_page, page, status (Dikirim|Disetujui|Ditolak), tahun.
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 20), 50);
        $perPage = $perPage >= 1 ? $perPage : 20;

        $nik = Auth::user()->username;
        $query = PengajuanLibur::with(['pegawai', 'pegawai2'])
            ->where('nik', $nik)
            ->where('jenis_pengajuan_libur', 'Ijin');

        if ($request->filled('status')) {
            $status = $request->query('status');
            if (in_array($status, ['Dikirim', 'Disetujui', 'Ditolak'], true)) {
                $query->where('status', $status);
            }
        }
        if ($request->filled('tahun')) {
            $tahun = (int) $request->query('tahun');
            $query->whereYear('tanggal_awal', $tahun);
        }

        $query->orderBy('tanggal_dibuat', 'desc');
        $paginator = $query->paginate($perPage);

        $data = $paginator->getCollection()->map(fn ($row) => $this->formatRow($row))->values()->all();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    /**
     * GET /api/ijin/{id}
     */
    public function show($id)
    {
        $nik = Auth::user()->username;
        $row = PengajuanLibur::with(['pegawai', 'pegawai2'])
            ->where('nik', $nik)
            ->where('jenis_pengajuan_libur', 'Ijin')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatRow($row),
        ]);
    }

    /**
     * POST /api/ijin
     * Body (multipart): tanggal_awal, tanggal_akhir, jumlah_hari, nik_atasan_langsung, keterangan, file (optional, image/pdf).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
            'jumlah_hari' => 'required|integer|min:1',
            'nik_atasan_langsung' => 'required|string',
            'keterangan' => 'required|string|max:500',
            'file' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $filePath = $file->storeAs('uploads/ijin_files', $fileName, 'public');
        }

        $nik = Auth::user()->username;
        $payload = [
            'jenis_pengajuan_libur' => 'Ijin',
            'nik' => $nik,
            'tanggal_awal' => $request->tanggal_awal,
            'tanggal_akhir' => $request->tanggal_akhir,
            'jumlah_hari' => (int) $request->jumlah_hari,
            'nik_atasan_langsung' => $request->nik_atasan_langsung,
            'keterangan' => $request->keterangan,
            'foto' => $filePath,
            'tanggal_dibuat' => Carbon::now(),
        ];

        $row = PengajuanLibur::create($payload);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan ijin berhasil dibuat.',
            'data' => $this->formatRow($row->load(['pegawai', 'pegawai2'])),
        ], 201);
    }

    /**
     * PUT /api/ijin/{id}
     * Hanya bisa edit jika status = Dikirim.
     * Untuk ganti file: kirim field "file" (multipart). Tanpa file = tetap pakai file lama.
     */
    public function update(Request $request, $id)
    {
        $nik = Auth::user()->username;
        $row = PengajuanLibur::where('nik', $nik)
            ->where('jenis_pengajuan_libur', 'Ijin')
            ->findOrFail($id);

        if ($row->status !== 'Dikirim') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pengajuan dengan status Dikirim yang dapat diedit.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
            'jumlah_hari' => 'required|integer|min:1',
            'nik_atasan_langsung' => 'required|string',
            'keterangan' => 'required|string|max:500',
            'file' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $filePath = $row->foto;
        if ($request->hasFile('file')) {
            if ($row->foto && Storage::disk('public')->exists($row->foto)) {
                Storage::disk('public')->delete($row->foto);
            }
            $file = $request->file('file');
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $filePath = $file->storeAs('uploads/ijin_files', $fileName, 'public');
        }

        $row->update([
            'tanggal_awal' => $request->tanggal_awal,
            'tanggal_akhir' => $request->tanggal_akhir,
            'jumlah_hari' => (int) $request->jumlah_hari,
            'nik_atasan_langsung' => $request->nik_atasan_langsung,
            'keterangan' => $request->keterangan,
            'foto' => $filePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan ijin berhasil diupdate.',
            'data' => $this->formatRow($row->fresh(['pegawai', 'pegawai2'])),
        ]);
    }

    /**
     * DELETE /api/ijin/{id}
     * Hanya bisa hapus jika status = Dikirim.
     */
    public function destroy($id)
    {
        $nik = Auth::user()->username;
        $row = PengajuanLibur::where('nik', $nik)
            ->where('jenis_pengajuan_libur', 'Ijin')
            ->findOrFail($id);

        if ($row->status !== 'Dikirim') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pengajuan dengan status Dikirim yang dapat dihapus.',
            ], 400);
        }

        if ($row->foto && Storage::disk('public')->exists($row->foto)) {
            Storage::disk('public')->delete($row->foto);
        }
        $row->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan ijin berhasil dihapus.',
        ]);
    }

    private function formatRow($row)
    {
        $fotoUrl = null;
        if (!empty($row->foto)) {
            $fotoUrl = asset('storage/' . $row->foto);
        }
        return [
            'id_pengajuan_libur' => $row->id_pengajuan_libur,
            'kode_pengajuan_libur' => $row->kode_pengajuan_libur,
            'jenis_pengajuan_libur' => $row->jenis_pengajuan_libur,
            'nik' => $row->nik,
            'nama_pegawai' => $row->pegawai ? $row->pegawai->nama : null,
            'tanggal_awal' => $row->tanggal_awal,
            'tanggal_akhir' => $row->tanggal_akhir,
            'jumlah_hari' => $row->jumlah_hari,
            'nik_atasan_langsung' => $row->nik_atasan_langsung,
            'nama_atasan' => $row->pegawai2 ? $row->pegawai2->nama : null,
            'keterangan' => $row->keterangan,
            'status' => $row->status,
            'catatan' => $row->catatan,
            'foto' => $row->foto,
            'foto_url' => $fotoUrl,
            'tanggal_dibuat' => $row->tanggal_dibuat ? Carbon::parse($row->tanggal_dibuat)->toIso8601String() : null,
            'tanggal_diverifikasi' => $row->tanggal_diverifikasi ?? null,
        ];
    }
}
