<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengajuanLibur;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PersetujuanLiburController extends Controller
{
    /**
     * GET /api/persetujuan-libur
     * Daftar pengajuan cuti/ijin bawahan. Query: per_page, page, status, tahun, jenis.
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 20), 50);
        $perPage = $perPage >= 1 ? $perPage : 20;

        $nikAtasan = Auth::user()->username;
        $query = PengajuanLibur::with(['pegawai', 'pegawai2'])
            ->where('nik_atasan_langsung', $nikAtasan);

        if ($request->filled('status')) {
            $status = $request->query('status');
            if (in_array($status, ['Dikirim', 'Disetujui', 'Ditolak'], true)) {
                $query->where('status', $status);
            }
        }
        if ($request->filled('tahun')) {
            $query->whereYear('tanggal_awal', (int) $request->query('tahun'));
        }
        if ($request->filled('jenis')) {
            $jenis = $request->query('jenis');
            if ($jenis === 'Ijin') {
                $query->where('jenis_pengajuan_libur', 'Ijin');
            } elseif ($jenis === 'Cuti') {
                $query->whereIn('jenis_pengajuan_libur', ['Tahunan', 'Melahirkan', 'Ambil Libur', 'Menikah']);
            }
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
     * GET /api/persetujuan-libur/{id}
     */
    public function show($id)
    {
        $nikAtasan = Auth::user()->username;
        $row = PengajuanLibur::with(['pegawai', 'pegawai2'])
            ->where('nik_atasan_langsung', $nikAtasan)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatRow($row),
        ]);
    }

    /**
     * PUT /api/persetujuan-libur/{id}
     * Validasi pengajuan bawahan. Body: status (Disetujui|Ditolak), catatan.
     * Sama dengan web: VerifikasiPengajuanLiburController@update
     */
    public function update(Request $request, $id)
    {
        $nikAtasan = Auth::user()->username;
        $row = PengajuanLibur::where('nik_atasan_langsung', $nikAtasan)->findOrFail($id);

        if ($row->status !== 'Dikirim') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pengajuan dengan status Dikirim yang dapat diverifikasi.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Disetujui,Ditolak',
            'catatan' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $row->status = $request->input('status');
        $row->catatan = $request->input('catatan');
        $row->tanggal_verifikasi = Carbon::now();
        $row->save();

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil diverifikasi.',
            'data' => $this->formatRow($row->fresh(['pegawai', 'pegawai2'])),
        ]);
    }

    private function formatRow($row): array
    {
        $fotoUrl = null;
        if (!empty($row->foto)) {
            $fotoUrl = asset('storage/' . $row->foto);
        }

        $tanggalVerifikasi = $row->tanggal_verifikasi ?? $row->tanggal_diverifikasi ?? null;

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
            'foto' => $row->foto,
            'foto_url' => $fotoUrl,
            'tanggal_dibuat' => $row->tanggal_dibuat ? Carbon::parse($row->tanggal_dibuat)->toIso8601String() : null,
            'tanggal_diverifikasi' => $tanggalVerifikasi
                ? Carbon::parse($tanggalVerifikasi)->toIso8601String()
                : null,
            'dapat_diverifikasi' => $row->status === 'Dikirim',
        ];
    }
}
