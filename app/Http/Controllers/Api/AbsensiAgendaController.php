<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\AgendaToken;
use App\Models\AbsensiAgenda;
use App\Models\Pegawai;
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
     */
    public function agenda(Request $request)
    {
        $nik = Auth::user()->username;

        $agendas = Agenda::where('akhir', '>=', Carbon::now())
            ->where(function ($query) use ($nik) {
                $query->whereJsonContains('yang_terundang', 'all')
                    ->orWhereJsonContains('yang_terundang', $nik);
            })
            ->select(['id', 'judul', 'deskripsi', 'mulai', 'akhir', 'tempat', 'pimpinan_rapat'])
            ->orderBy('mulai')
            ->get()
            ->map(function ($agenda) {
                $mulai = Carbon::parse($agenda->mulai);
                $akhir = $agenda->akhir ? Carbon::parse($agenda->akhir) : null;
                $now = Carbon::now();
                $status = 'akan_datang';
                if ($akhir && $now->gt($akhir)) {
                    $status = 'selesai';
                } elseif ($now->gte($mulai->copy()->subMinutes(15))) {
                    $status = $akhir && $now->gt($akhir) ? 'selesai' : 'bisa_scan';
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
                    'mulai_format' => $mulai->format('d M Y H:i'),
                    'akhir_format' => $akhir ? $akhir->format('d M Y H:i') : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $agendas,
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

        // Token harus ada & belum expired
        $agendaToken = AgendaToken::where('agenda_id', $agendaId)
            ->where('token', $token)
            ->where('expiry', '>', now())
            ->first();

        if (!$agendaToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau telah kedaluwarsa.',
            ], 400);
        }

        $nik = Auth::user()->username;
        $agenda = Agenda::findOrFail($agendaId);

        // Cek apakah user diundang
        $terundang = is_array($agenda->yang_terundang)
            ? $agenda->yang_terundang
            : (json_decode($agenda->yang_terundang, true) ?? []);

        $semuaNikAktif = Pegawai::where('stts_aktif', 'AKTIF')->pluck('nik')->toArray();
        $isAll = false;
        if (is_array($terundang)) {
            $intersect = array_intersect($semuaNikAktif, $terundang);
            $isAll = count($intersect) === count($semuaNikAktif) && count($terundang) === count($semuaNikAktif);
        }

        if (!in_array('all', $terundang) && !$isAll && !in_array($nik, $terundang)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak diundang dalam agenda ini.',
            ], 403);
        }

        // Validasi waktu: boleh scan 15 menit sebelum mulai sampai 1 jam setelah akhir
        $now = Carbon::now();
        $mulai = Carbon::parse($agenda->mulai);
        $akhir = $agenda->akhir ? Carbon::parse($agenda->akhir) : null;

        if ($now->lt($mulai->copy()->subMinutes(15))) {
            return response()->json([
                'success' => false,
                'message' => 'Agenda belum dimulai. Waktu mulai: ' . $mulai->format('d M Y H:i'),
            ], 400);
        }

        if ($akhir && $now->gt($akhir->copy()->addHour())) {
            return response()->json([
                'success' => false,
                'message' => 'Agenda sudah berakhir. Waktu akhir: ' . Carbon::parse($agenda->akhir)->format('d M Y H:i'),
            ], 400);
        }

        // Cek absen ganda
        if (AbsensiAgenda::where('nik', $nik)->where('agenda_id', $agendaId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan absensi untuk agenda ini.',
            ], 409);
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
