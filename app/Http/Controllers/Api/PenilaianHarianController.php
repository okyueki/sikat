<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailPenilaian;
use App\Models\ItemPenilaian;
use App\Models\Pegawai;
use App\Models\PenilaianHarian;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PenilaianHarianController extends Controller
{
    /**
     * GET /api/penilaian-harian/items
     * List item penilaian (nama_item, bobot_nilai).
     */
    public function items(Request $request)
    {
        $items = ItemPenilaian::orderBy('id')->get(['id', 'nama_item', 'bobot_nilai']);
        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * GET /api/penilaian-harian/search-pegawai?query=...
     * Cari pegawai AKTIF untuk dropdown.
     */
    public function searchPegawai(Request $request)
    {
        $query = trim((string) $request->query('query', ''));
        if (mb_strlen($query) < 3) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')
            ->where('nama', 'LIKE', "%{$query}%")
            ->orderBy('nama')
            ->limit(20)
            ->get(['id', 'nik', 'nama', 'departemen', 'jbtn']);

        return response()->json([
            'success' => true,
            'data' => $pegawai,
        ]);
    }

    /**
     * POST /api/penilaian-harian
     * Simpan penilaian harian + detail item.
     *
     * Body:
     * - pegawai_id (int)
     * - tanggal_penilaian (Y-m-d)
     * - waktu_penilaian (pagi|sore)
     * - item_penilaian: { "<item_id>": 0|1, ... }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pegawai_id' => 'required|integer|exists:server_74.pegawai,id',
            'tanggal_penilaian' => 'required|date',
            'waktu_penilaian' => 'required|in:pagi,sore',
            'item_penilaian' => 'required|array',
            'item_penilaian.*' => 'in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $pegawaiId = (int) $request->input('pegawai_id');
        $tanggal = Carbon::parse($request->input('tanggal_penilaian'))->format('Y-m-d');
        $waktu = (string) $request->input('waktu_penilaian');
        $itemsInput = (array) $request->input('item_penilaian', []);

        $exists = PenilaianHarian::where('pegawai_id', $pegawaiId)
            ->whereDate('tanggal_penilaian', $tanggal)
            ->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai sudah dinilai pada tanggal tersebut.',
            ], 409);
        }

        $itemsDb = ItemPenilaian::whereIn('id', array_keys($itemsInput))->get(['id', 'bobot_nilai'])->keyBy('id');

        DB::beginTransaction();
        try {
            $penilaian = PenilaianHarian::create([
                'pegawai_id' => $pegawaiId,
                'tanggal_penilaian' => $tanggal,
                'waktu_penilaian' => $waktu,
                'total_nilai' => 0,
            ]);

            $totalNilai = 0;
            $detailOut = [];

            foreach ($itemsInput as $itemId => $nilai) {
                $itemIdInt = (int) $itemId;
                $nilaiInt = (int) $nilai;
                $bobot = (int) ($itemsDb[$itemIdInt]->bobot_nilai ?? 0);
                $totalNilai += $nilaiInt * $bobot;

                DetailPenilaian::create([
                    'penilaian_harian_id' => $penilaian->id,
                    'item_penilaian_id' => $itemIdInt,
                    'nilai' => $nilaiInt,
                ]);

                $detailOut[] = [
                    'item_penilaian_id' => $itemIdInt,
                    'nilai' => $nilaiInt,
                    'bobot_nilai' => $bobot,
                ];
            }

            $penilaian->update(['total_nilai' => $totalNilai]);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Penilaian berhasil disimpan.',
                'data' => [
                    'id' => $penilaian->id,
                    'pegawai_id' => $pegawaiId,
                    'tanggal_penilaian' => $tanggal,
                    'waktu_penilaian' => $waktu,
                    'total_nilai' => $totalNilai,
                    'detail' => $detailOut,
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan penilaian.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

