<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JadwalTambahan;
use App\Models\Pegawai;
use App\Models\JamMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * API Jadwal Tambahan — CRUD jadwal tambahan presensi per bulan/tahun untuk pegawai yang login.
 * Dipakai untuk presensi kedua (setelah sudah lengkap di rekap) jika ada shift di jadwal tambahan hari itu.
 */
class JadwalTambahanController extends Controller
{
    /**
     * GET /api/jadwal-tambahan?bulan=1&tahun=2026
     * List jadwal tambahan pegawai yang login untuk bulan & tahun.
     */
    public function index(Request $request)
    {
        $pegawai = $this->pegawaiFromAuth();
        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Data pegawai tidak ditemukan.',
            ], 404);
        }

        $bulanInput = $request->query('bulan', date('n'));
        $bulan = str_pad((string) (int) $bulanInput, 2, '0', STR_PAD_LEFT);
        $tahun = (int) $request->query('tahun', date('Y'));

        $jadwal = JadwalTambahan::with('pegawai')
            ->where('id', $pegawai->id)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        $jamMasukList = Cache::remember('api.jam_masuk_list', 3600, fn () => JamMasuk::all());
        $shifts = $jamMasukList->map(fn ($s) => [
            'shift' => $s->shift,
            'jam_masuk' => $s->jam_masuk,
            'jam_pulang' => $s->jam_pulang,
        ])->values()->all();

        $jadwalHari = [];
        if ($jadwal) {
            for ($i = 1; $i <= 31; $i++) {
                $field = 'h' . $i;
                $jadwalHari[$i] = $jadwal->$field ?? '';
            }
        } else {
            for ($i = 1; $i <= 31; $i++) {
                $jadwalHari[$i] = '';
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'pegawai' => [
                    'id' => $pegawai->id,
                    'nik' => $pegawai->nik,
                    'nama' => $pegawai->nama,
                ],
                'bulan' => (int) $bulan,
                'tahun' => $tahun,
                'jadwal' => $jadwal ? [
                    'id' => $jadwal->id,
                    'tahun' => $jadwal->tahun,
                    'bulan' => $jadwal->bulan,
                ] : null,
                'jadwal_hari' => $jadwalHari,
                'shifts' => $shifts,
            ],
        ]);
    }

    /**
     * GET /api/jadwal-tambahan/data?bulan=1&tahun=2026
     * Data lengkap untuk edit. Sama seperti index.
     */
    public function data(Request $request)
    {
        return $this->index($request);
    }

    /**
     * PUT /api/jadwal-tambahan
     * Update (atau create) jadwal tambahan pegawai untuk bulan & tahun. Body: bulan, tahun, h1..h31 (nullable string).
     */
    public function update(Request $request)
    {
        $pegawai = $this->pegawaiFromAuth();
        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Data pegawai tidak ditemukan.',
            ], 404);
        }

        $rules = [
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|min:2020|max:2100',
        ];
        for ($i = 1; $i <= 31; $i++) {
            $rules['h' . $i] = 'nullable|string|max:50';
        }
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $bulan = str_pad((string) (int) $request->bulan, 2, '0', STR_PAD_LEFT);
        $tahun = (int) $request->tahun;

        $jadwal = JadwalTambahan::where('id', $pegawai->id)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        $payload = [
            'id' => $pegawai->id,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ];
        for ($i = 1; $i <= 31; $i++) {
            $field = 'h' . $i;
            $payload[$field] = $request->has($field) ? (string) $request->$field : '';
        }

        if ($jadwal) {
            $jadwal->update($payload);
        } else {
            JadwalTambahan::create($payload);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jadwal tambahan berhasil disimpan.',
            'data' => [
                'bulan' => (int) $bulan,
                'tahun' => $tahun,
            ],
        ]);
    }

    private function pegawaiFromAuth(): ?Pegawai
    {
        $nik = Auth::user()->username;
        return Pegawai::where('nik', $nik)->first();
    }
}
