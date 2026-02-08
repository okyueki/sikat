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

    /** Kuota: cuti Tahunan 12x per tahun, 1x maksimal 2 hari. Ambil Libur 1x maksimal 2 hari. */
    private const KUOTA_TAHUNAN_PER_TAHUN = 12;
    private const MAX_HARI_TAHUNAN_PER_PENGAJUAN = 2;
    private const MAX_HARI_AMBIL_LIBUR = 2;

    /**
     * GET /api/cuti
     * Daftar pengajuan cuti. Query: per_page, page, status (Dikirim|Disetujui|Ditolak), tahun.
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->query('per_page', 20), 50);
        $perPage = $perPage >= 1 ? $perPage : 20;

        $nik = Auth::user()->username;
        $query = PengajuanLibur::with(['pegawai', 'pegawai2'])
            ->where('nik', $nik)
            ->whereIn('jenis_pengajuan_libur', self::JENIS_CUTI);

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

        $tahunKuota = $request->filled('tahun') ? (int) $request->query('tahun') : (int) date('Y');
        $kuota = $this->hitungKuotaCuti($nik, $tahunKuota);

        $data = $paginator->getCollection()->map(fn ($row) => $this->formatRow($row))->values()->all();

        return response()->json([
            'success' => true,
            'data' => $data,
            'kuota' => $kuota,
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
        $jenis = $request->jenis_pengajuan_libur;
        $jumlahHari = (int) $request->jumlah_hari;
        $tahunCuti = (int) Carbon::parse($request->tanggal_awal)->format('Y');

        if ($jenis === 'Tahunan') {
            if ($jumlahHari > self::MAX_HARI_TAHUNAN_PER_PENGAJUAN) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cuti Tahunan maksimal ' . self::MAX_HARI_TAHUNAN_PER_PENGAJUAN . ' hari per pengajuan.',
                ], 422);
            }
            $sudahDipakai = PengajuanLibur::where('nik', $nik)
                ->where('jenis_pengajuan_libur', 'Tahunan')
                ->where('status', 'Disetujui')
                ->whereYear('tanggal_awal', $tahunCuti)
                ->count();
            if ($sudahDipakai >= self::KUOTA_TAHUNAN_PER_TAHUN) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kuota cuti Tahunan tahun ' . $tahunCuti . ' sudah habis (maksimal ' . self::KUOTA_TAHUNAN_PER_TAHUN . 'x per tahun).',
                ], 422);
            }
        }
        if ($jenis === 'Ambil Libur') {
            if ($jumlahHari > self::MAX_HARI_AMBIL_LIBUR) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ambil Libur maksimal ' . self::MAX_HARI_AMBIL_LIBUR . ' hari per pengajuan.',
                ], 422);
            }
        }

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

    private function hitungKuotaCuti(string $nik, int $tahun): array
    {
        $sudahTahunan = PengajuanLibur::where('nik', $nik)
            ->where('jenis_pengajuan_libur', 'Tahunan')
            ->where('status', 'Disetujui')
            ->whereYear('tanggal_awal', $tahun)
            ->count();
        $sisaTahunan = max(0, self::KUOTA_TAHUNAN_PER_TAHUN - $sudahTahunan);
        return [
            'tahun' => $tahun,
            'cuti_tahunan' => [
                'kuota_per_tahun' => self::KUOTA_TAHUNAN_PER_TAHUN,
                'sudah_dipakai' => $sudahTahunan,
                'sisa' => $sisaTahunan,
                'maks_hari_per_pengajuan' => self::MAX_HARI_TAHUNAN_PER_PENGAJUAN,
            ],
            'ambil_libur_maks_hari_per_pengajuan' => self::MAX_HARI_AMBIL_LIBUR,
        ];
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
