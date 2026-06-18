<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JadwalPegawai;
use App\Models\Pegawai;
use App\Models\JamMasuk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * API Jadwal Pegawai — CRUD jadwal presensi per bulan/tahun untuk pegawai yang login.
 * Nyambung dengan API Absensi: jadwal ini dipakai untuk menentukan shift (jam_masuk, jam_pulang) hari ini.
 */
class JadwalPegawaiController extends Controller
{
    /**
     * GET /api/jadwal-pegawai?bulan=1&tahun=2026
     * List jadwal pegawai yang login untuk bulan & tahun. Satu record per bulan-tahun.
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

        // Format bulan 2 digit ("01".."12") agar sama dengan web & AbsensiController (jadwal-hari-ini)
        $bulanInput = $request->query('bulan', date('n'));
        $bulan = str_pad((string) (int) $bulanInput, 2, '0', STR_PAD_LEFT);
        $tahun = (int) $request->query('tahun', date('Y'));

        $jadwal = JadwalPegawai::with('pegawai')
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
     * GET /api/jadwal-pegawai/data?bulan=1&tahun=2026
     * Data lengkap untuk edit: jadwal + per hari (h1-h31) + daftar shift. Sama seperti index, alias untuk konsistensi dengan web.
     */
    public function data(Request $request)
    {
        return $this->index($request);
    }

    /**
     * PUT /api/jadwal-pegawai
     * Update (atau create) jadwal pegawai untuk bulan & tahun. Body: bulan, tahun, h1..h31 (nullable string).
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

        // Format bulan 2 digit ("01".."12") agar sama dengan web & AbsensiController
        $bulan = str_pad((string) (int) $request->bulan, 2, '0', STR_PAD_LEFT);
        $tahun = (int) $request->tahun;

        $jadwal = JadwalPegawai::where('id', $pegawai->id)
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
            // PK tabel = (id, tahun, bulan). Jangan pakai $jadwal->update() — hanya WHERE id.
            $shiftData = [];
            for ($i = 1; $i <= 31; $i++) {
                $field = 'h' . $i;
                $shiftData[$field] = $payload[$field];
            }
            JadwalPegawai::where('id', $pegawai->id)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->update($shiftData);
        } else {
            JadwalPegawai::create($payload);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil disimpan.',
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
