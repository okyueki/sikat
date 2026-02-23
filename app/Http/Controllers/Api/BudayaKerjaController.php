<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BudayaKerja;
use App\Models\Pegawai;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BudayaKerjaController extends Controller
{
    /**
     * GET /api/budayakerja/pegawai
     * Daftar pegawai AKTIF yang belum diinput budaya_kerja di tanggal tertentu (default: hari ini).
     */
    public function pegawaiBelumMengisi(Request $request)
    {
        $tanggal = $request->query('tanggal') ?: Carbon::today()->format('Y-m-d');

        $nikSudahMengisi = BudayaKerja::whereDate('tanggal', $tanggal)->pluck('nik_pegawai');

        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')
            ->whereNotIn('nik', $nikSudahMengisi)
            ->orderBy('nama')
            ->get(['nik', 'nama', 'departemen', 'jbtn']);

        return response()->json([
            'success' => true,
            'data' => $pegawai,
            'meta' => [
                'tanggal' => $tanggal,
            ],
        ]);
    }

    /**
     * GET /api/budayakerja/shift
     * Daftar opsi shift yang valid (pagi, sore, malam - huruf kecil).
     */
    public function shiftOptions()
    {
        return response()->json([
            'success' => true,
            'data' => ['pagi', 'sore', 'malam'],
        ]);
    }

    /**
     * POST /api/budayakerja
     * Simpan penilaian budaya kerja (atribut sepatu/sabuk/... total_nilai dihitung server-side).
     */
    public function store(Request $request)
    {
        // Normalisasi shift ke huruf kecil (pagi, sore, malam) sebelum validasi
        $shiftInput = $request->input('shift');
        if ($shiftInput !== null) {
            $request->merge(['shift' => strtolower((string) $shiftInput)]);
        }

        $validator = Validator::make($request->all(), [
            'nik_pegawai' => 'required|string|exists:server_74.pegawai,nik',
            'tanggal' => 'nullable|date',
            'jam' => 'nullable|date_format:H:i:s',
            'shift' => 'nullable|string|in:pagi,sore,malam',

            // item penilaian budaya kerja (0/1)
            'sepatu' => 'nullable|in:0,1',
            'sabuk' => 'nullable|in:0,1',
            'make_up' => 'nullable|in:0,1',
            'minyak_wangi' => 'nullable|in:0,1',
            'jilbab' => 'nullable|in:0,1',
            'kuku' => 'nullable|in:0,1',
            'baju' => 'nullable|in:0,1',
            'celana' => 'nullable|in:0,1',
            'name_tag' => 'nullable|in:0,1',
            'perhiasan' => 'nullable|in:0,1',
            'kaos_kaki' => 'nullable|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $nikPegawai = (string) $request->input('nik_pegawai');
        $tanggal = $request->input('tanggal') ?: Carbon::today()->format('Y-m-d');
        $jam = $request->input('jam') ?: Carbon::now()->format('H:i:s');

        // Cegah input ganda (tanggal + nik)
        $exists = BudayaKerja::whereDate('tanggal', $tanggal)->where('nik_pegawai', $nikPegawai)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai sudah dinilai budaya kerja pada tanggal tersebut.',
            ], 409);
        }

        $pegawai = Pegawai::where('nik', $nikPegawai)->first();
        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai tidak ditemukan.',
            ], 404);
        }

        $petugas = (string) Auth::user()->username;

        $items = ['sepatu', 'sabuk', 'make_up', 'minyak_wangi', 'jilbab', 'kuku', 'baju', 'celana', 'name_tag', 'perhiasan', 'kaos_kaki'];
        $totalNilai = 0;
        $payload = [
            'tanggal' => $tanggal,
            'jam' => $jam,
            'petugas' => $petugas,
            'nik_pegawai' => $nikPegawai,
            'nama_pegawai' => $pegawai->nama,
            'departemen' => $pegawai->departemen,
            'jabatan' => $pegawai->jbtn,
            'shift' => strtolower((string) ($request->input('shift') ?? 'pagi')), // pagi, sore, malam (huruf kecil)
        ];

        foreach ($items as $item) {
            $val = (int) $request->input($item, 0);
            $payload[$item] = $val;
            $totalNilai += $val;
        }
        $payload['total_nilai'] = $totalNilai;

        $row = BudayaKerja::create($payload);

        return response()->json([
            'success' => true,
            'message' => 'Data budaya kerja berhasil disimpan.',
            'data' => [
                'id' => $row->id,
                'tanggal' => $row->tanggal,
                'jam' => $row->jam,
                'petugas' => $row->petugas,
                'nik_pegawai' => $row->nik_pegawai,
                'nama_pegawai' => $row->nama_pegawai,
                'departemen' => $row->departemen,
                'jabatan' => $row->jabatan,
                'shift' => $row->shift,
                'total_nilai' => $row->total_nilai,
            ],
        ], 201);
    }

    /**
     * GET /api/budayakerja/{id}
     * Detail 1 penilaian budaya kerja (setara halaman budayakerja/show).
     */
    public function show(Request $request, $id)
    {
        $row = BudayaKerja::findOrFail($id);

        $items = ['sepatu', 'sabuk', 'make_up', 'minyak_wangi', 'jilbab', 'kuku', 'baju', 'celana', 'name_tag', 'perhiasan', 'kaos_kaki'];
        $itemDetails = collect($items)->map(function ($k) use ($row) {
            $label = ucfirst(str_replace('_', ' ', $k));
            $val = (int) ($row->$k ?? 0);
            return [
                'key' => $k,
                'label' => $label,
                'nilai' => $val,
                'status' => $val === 1 ? 'Sesuai' : 'Tidak Sesuai',
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $row->id,
                'tanggal' => $row->tanggal,
                'jam' => $row->jam,
                'petugas' => $row->petugas,
                'nik_pegawai' => $row->nik_pegawai,
                'nama_pegawai' => $row->nama_pegawai,
                'departemen' => $row->departemen,
                'jabatan' => $row->jabatan,
                'shift' => $row->shift,
                'total_nilai' => $row->total_nilai,
                'items' => $itemDetails,
            ],
        ]);
    }
}

