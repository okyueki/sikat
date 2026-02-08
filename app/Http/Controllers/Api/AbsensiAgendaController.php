<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\AbsensiAgenda;
use App\Services\AbsensiAgendaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * API Absensi Agenda (scan barcode/QR).
 * User scan QR berisi { agenda_id, token } → kirim ke API → validasi → simpan kehadiran.
 */
class AbsensiAgendaController extends Controller
{
    /**
     * GET /api/absensi-agenda/agenda
     * Daftar agenda yang belum berakhir dan user terundang (untuk ditampilkan di app sebelum scan).
     * Query: hari (default 30) = jangkauan hari ke depan dari hari ini.
     */
    public function agenda(Request $request)
    {
        $nik = Auth::user()->username;
        $hari = min(max((int) $request->query('hari', 30), 1), 90); // 1–90 hari ke depan
        $batasAkhir = Carbon::now()->addDays($hari);

        $agendas = Agenda::where('akhir', '>=', Carbon::now())
            ->where('mulai', '<=', $batasAkhir)
            ->where(function ($query) use ($nik) {
                $query->whereJsonContains('yang_terundang', 'all')
                    ->orWhereJsonContains('yang_terundang', $nik);
            })
            ->select(['id', 'judul', 'deskripsi', 'mulai', 'akhir', 'tempat', 'pimpinan_rapat'])
            ->orderBy('mulai')
            ->limit(50)
            ->get();

        $agendaIds = $agendas->pluck('id')->toArray();
        $absensiByAgenda = AbsensiAgenda::whereIn('agenda_id', $agendaIds)
            ->where('nik', $nik)
            ->get()
            ->keyBy('agenda_id');

        $data = $agendas->map(function ($agenda) use ($absensiByAgenda) {
            $absensi = $absensiByAgenda->get($agenda->id);
            $mulai = Carbon::parse($agenda->mulai);
            $akhir = $agenda->akhir ? Carbon::parse($agenda->akhir) : null;
            $now = Carbon::now();
            $status = 'akan_datang';
            if ($akhir && $now->gt($akhir)) {
                $status = 'selesai';
            } elseif ($now->gte($mulai->copy()->subMinutes(15))) {
                $status = ($akhir && $now->gt($akhir)) ? 'selesai' : 'bisa_scan';
            }
            return [
                'id' => $agenda->id,
                'judul' => $agenda->judul,
                'deskripsi' => $agenda->deskripsi ?? null,
                'mulai' => $agenda->mulai,
                'akhir' => $agenda->akhir ?? null,
                'tempat' => $agenda->tempat ?? null,
                'pimpinan_rapat' => $agenda->pimpinan_rapat,
                'status' => $status,
                'sudah_absen' => $absensi && $absensi->status_kehadiran === 'hadir',
                'status_kehadiran' => $absensi ? $absensi->status_kehadiran : null,
                'mulai_format' => $mulai->format('d M Y H:i'),
                'akhir_format' => $akhir ? $akhir->format('d M Y H:i') : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * GET /api/absensi-agenda/agenda/{id}
     * Detail satu agenda. Hanya boleh akses jika user terundang. Untuk halaman "Detail agenda" di app sebelum scan.
     */
    public function agendaDetail(Request $request, $id)
    {
        $nik = Auth::user()->username;

        $agenda = Agenda::with(['pimpinan', 'notulenPegawai'])
            ->where('id', $id)
            ->firstOrFail();

        if (!$agenda->userTerundang($nik)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak diundang dalam agenda ini.',
            ], 403);
        }

        $absensi = AbsensiAgenda::where('agenda_id', $agenda->id)->where('nik', $nik)->first();
        $mulai = Carbon::parse($agenda->mulai);
        $akhir = $agenda->akhir ? Carbon::parse($agenda->akhir) : null;
        $now = Carbon::now();
        $status = 'akan_datang';
        if ($akhir && $now->gt($akhir)) {
            $status = 'selesai';
        } elseif ($now->gte($mulai->copy()->subMinutes(15))) {
            $status = ($akhir && $now->gt($akhir)) ? 'selesai' : 'bisa_scan';
        }

        $data = [
            'id' => $agenda->id,
            'judul' => $agenda->judul,
            'deskripsi' => $agenda->deskripsi ?? null,
            'mulai' => $agenda->mulai,
            'akhir' => $agenda->akhir ?? null,
            'tempat' => $agenda->tempat ?? null,
            'pimpinan_rapat' => $agenda->pimpinan_rapat,
            'pimpinan_nama' => $agenda->pimpinan ? $agenda->pimpinan->nama : null,
            'notulen' => $agenda->notulen,
            'notulen_nama' => $agenda->notulenPegawai ? $agenda->notulenPegawai->nama : null,
            'status' => $status,
            'sudah_absen' => $absensi && $absensi->status_kehadiran === 'hadir',
            'status_kehadiran' => $absensi ? $absensi->status_kehadiran : null,
            'waktu_kehadiran' => $absensi && $absensi->waktu_kehadiran
                ? $absensi->waktu_kehadiran->toIso8601String()
                : null,
            'mulai_format' => $mulai->format('d M Y H:i'),
            'akhir_format' => $akhir ? $akhir->format('d M Y H:i') : null,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * GET /api/absensi-agenda/riwayat
     * Daftar agenda yang user sudah absen (riwayat kehadiran rapat). Query: page, per_page, tahun, bulan.
     */
    public function riwayat(Request $request)
    {
        $nik = Auth::user()->username;
        $perPage = min(max((int) $request->query('per_page', 20), 1), 50);
        $tahun = $request->query('tahun');
        $bulan = $request->query('bulan');

        $query = AbsensiAgenda::with('agenda')
            ->where('nik', $nik)
            ->whereHas('agenda');

        if ($tahun) {
            $query->whereYear('waktu_kehadiran', $tahun);
        }
        if ($bulan) {
            $query->whereMonth('waktu_kehadiran', $bulan);
        }

        $paginator = $query->orderByDesc('waktu_kehadiran')->paginate($perPage);
        $items = $paginator->getCollection()->map(function ($row) {
            $agenda = $row->agenda;
            return [
                'id_absensi_agenda' => $row->id_absensi_agenda,
                'agenda_id' => $row->agenda_id,
                'judul_agenda' => $agenda ? $agenda->judul : null,
                'tempat' => $agenda ? $agenda->tempat : null,
                'waktu_kehadiran' => $row->waktu_kehadiran ? $row->waktu_kehadiran->toIso8601String() : null,
                'status_kehadiran' => $row->status_kehadiran,
                'mulai_agenda' => $agenda && $agenda->mulai ? Carbon::parse($agenda->mulai)->format('d M Y H:i') : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $items,
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
     * GET /api/absensi-agenda/agenda/{id}/rekap
     * Daftar hadir agenda (nik, nama, waktu_kehadiran, status_kehadiran). Hanya untuk pimpinan_rapat atau notulen agenda.
     */
    public function rekap(Request $request, $id)
    {
        $nik = Auth::user()->username;

        $agenda = Agenda::where('id', $id)->firstOrFail();

        $isPimpinan = $nik === $agenda->pimpinan_rapat;
        $isNotulen = $nik === $agenda->notulen;
        if (!$isPimpinan && !$isNotulen) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pimpinan rapat atau notulen yang dapat melihat rekap absensi agenda ini.',
            ], 403);
        }

        $list = AbsensiAgenda::with('pegawai')
            ->where('agenda_id', $agenda->id)
            ->orderBy('waktu_kehadiran')
            ->get()
            ->map(function ($row) {
                return [
                    'nik' => $row->nik,
                    'nama' => $row->pegawai ? $row->pegawai->nama : null,
                    'waktu_kehadiran' => $row->waktu_kehadiran ? $row->waktu_kehadiran->toIso8601String() : null,
                    'status_kehadiran' => $row->status_kehadiran,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'agenda_id' => $agenda->id,
                'judul_agenda' => $agenda->judul,
                'mulai' => $agenda->mulai,
                'akhir' => $agenda->akhir,
                'daftar_hadir' => $list,
            ],
        ]);
    }

    /**
     * POST /api/absensi-agenda/scan
     * Submit hasil scan barcode/QR. Body: agenda_id, token (dari isi QR).
     * Validasi: token valid & belum expired, user terundang, waktu agenda, belum pernah absen.
     * Simpan kehadiran ke tabel absensi_agenda.
     */
    public function scan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agenda_id' => 'required|integer|exists:agendas,id',
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $agendaId = (int) $request->agenda_id;
        $token = $request->token;
        $nik = Auth::user()->username;
        $agenda = Agenda::findOrFail($agendaId);

        $error = AbsensiAgendaService::validateScan($agenda, $nik, $token);
        if ($error) {
            return response()->json([
                'success' => false,
                'message' => $error['message'],
            ], $error['http']);
        }

        // Simpan kehadiran
        $absensi = AbsensiAgenda::create([
            'nik' => $nik,
            'agenda_id' => $agendaId,
            'waktu_kehadiran' => now(),
            'status_kehadiran' => 'hadir',
            'token' => $token,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kehadiran berhasil dicatat.',
            'data' => [
                'id_absensi_agenda' => $absensi->id_absensi_agenda,
                'agenda_id' => $agendaId,
                'judul_agenda' => $agenda->judul,
                'waktu_kehadiran' => $absensi->waktu_kehadiran->toIso8601String(),
            ],
        ], 201);
    }
}
