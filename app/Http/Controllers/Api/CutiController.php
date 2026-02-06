<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengajuanLibur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CutiController extends Controller
{
    /** Jenis cuti (bukan Ijin) */
    private const JENIS_CUTI = ['Tahunan', 'Melahirkan', 'Ambil Libur', 'Menikah'];

    /**
     * GET /api/cuti
     * Daftar pengajuan cuti user yang login.
     */
    public function index(Request $request)
    {
        $nik = Auth::user()->username;
        $data = PengajuanLibur::with(['pegawai', 'pegawai2'])
            ->where('nik', $nik)
            ->whereIn('jenis_pengajuan_libur', self::JENIS_CUTI)
            ->orderBy('tanggal_dibuat', 'desc')
            ->get()
            ->map(fn ($row) => $this->formatRow($row));

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * GET /api/cuti/{id}
     */
    public function show($id)
    {
        $nik = Auth::user()->username;
        $row = PengajuanLibur::with(['pegawai', 'pegawai2'])
            ->where('nik', $nik)
            ->whereIn('jenis_pengajuan_libur', self::JENIS_CUTI)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatRow($row),
        ]);
    }

    /**
     * POST /api/cuti
     * Body: jenis_pengajuan_libur, tanggal_awal, tanggal_akhir, jumlah_hari, nik_atasan_langsung, keterangan, alamat (optional).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis_pengajuan_libur' => 'required|in:' . implode(',', self::JENIS_CUTI),
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
            'jumlah_hari' => 'required|integer|min:1',
            'nik_atasan_langsung' => 'required|string',
            'keterangan' => 'required|string|max:500',
            'alamat' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $nik = Auth::user()->username;
        $payload = $request->only([
            'jenis_pengajuan_libur', 'tanggal_awal', 'tanggal_akhir', 'jumlah_hari',
            'nik_atasan_langsung', 'keterangan', 'alamat',
        ]);
        $payload['nik'] = $nik;
        $payload['tanggal_dibuat'] = Carbon::now();

        $row = PengajuanLibur::create($payload);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan cuti berhasil dibuat.',
            'data' => $this->formatRow($row->load(['pegawai', 'pegawai2'])),
        ], 201);
    }

    /**
     * PUT /api/cuti/{id}
     * Hanya bisa edit jika status = Dikirim.
     */
    public function update(Request $request, $id)
    {
        $nik = Auth::user()->username;
        $row = PengajuanLibur::where('nik', $nik)
            ->whereIn('jenis_pengajuan_libur', self::JENIS_CUTI)
            ->findOrFail($id);

        if ($row->status !== 'Dikirim') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pengajuan dengan status Dikirim yang dapat diedit.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'jenis_pengajuan_libur' => 'required|in:' . implode(',', self::JENIS_CUTI),
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
            'jumlah_hari' => 'required|integer|min:1',
            'nik_atasan_langsung' => 'required|string',
            'keterangan' => 'required|string|max:500',
            'alamat' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $row->update($request->only([
            'jenis_pengajuan_libur', 'tanggal_awal', 'tanggal_akhir', 'jumlah_hari',
            'nik_atasan_langsung', 'keterangan', 'alamat',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan cuti berhasil diupdate.',
            'data' => $this->formatRow($row->fresh(['pegawai', 'pegawai2'])),
        ]);
    }

    /**
     * DELETE /api/cuti/{id}
     * Hanya bisa hapus jika status = Dikirim.
     */
    public function destroy($id)
    {
        $nik = Auth::user()->username;
        $row = PengajuanLibur::where('nik', $nik)
            ->whereIn('jenis_pengajuan_libur', self::JENIS_CUTI)
            ->findOrFail($id);

        if ($row->status !== 'Dikirim') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pengajuan dengan status Dikirim yang dapat dihapus.',
            ], 400);
        }

        $row->delete();
        return response()->json([
            'success' => true,
            'message' => 'Pengajuan cuti berhasil dihapus.',
        ]);
    }

    private function formatRow($row)
    {
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
            'alamat' => $row->alamat,
            'status' => $row->status,
            'catatan' => $row->catatan,
            'tanggal_dibuat' => $row->tanggal_dibuat ? Carbon::parse($row->tanggal_dibuat)->toIso8601String() : null,
            'tanggal_diverifikasi' => $row->tanggal_diverifikasi ?? null,
        ];
    }
}
