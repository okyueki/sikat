<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Models\Agenda;
use App\Models\AgendaToken;
use App\Models\AbsensiAgenda;
use App\Models\Pegawai;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AbsensiAgendaController extends Controller
{
    // Method to display agenda data for DataTables
    public function index(Request $request)
{
    // Ambil NIK pengguna yang sedang login
    $userNik = Auth::user()->username;

    if ($request->ajax()) {
        // Filter agenda yang belum berakhir dan di mana pengguna ada dalam daftar undangan
        $agendas = Agenda::where('akhir', '>=', Carbon::now())
             ->where(function ($query) use ($userNik) {
                $query->whereJsonContains('yang_terundang', 'all') // semua pegawai
                      ->orWhereJsonContains('yang_terundang', $userNik); // khusus undangan
            })
            
            ->select(['id', 'judul', 'deskripsi', 'mulai', 'akhir', 'tempat', 'pimpinan_rapat', 'yang_terundang']);
                
        return DataTables::of($agendas)
            ->addColumn('action', function ($agenda) {
                return '<a href="' .route('absensi.scan', ['agendaId' => $agenda->id]). '" class="btn btn-success" title="Scan QR Code untuk Absensi"><i class="fa-solid fa-qrcode me-1"></i> Scan</a>';
            })
            ->editColumn('mulai', function ($agenda) {
                return Carbon::parse($agenda->mulai)->format('d M Y H:i');
            })
            ->editColumn('akhir', function ($agenda) {
                return Carbon::parse($agenda->akhir)->format('d M Y H:i');
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    return view('absensi_agenda.index');
}
// --- View Scanner ---
    public function scanBarcode($agendaId = null)
    {
        $agenda = null;
        
        if ($agendaId) {
            $agenda = Agenda::with(['pimpinan', 'notulenPegawai'])->find($agendaId);
            
            if (!$agenda) {
                return redirect()->route('absensi_agenda.index')
                    ->with('error', 'Agenda tidak ditemukan.');
            }
            
            // Validasi user terundang
            $userNik = Auth::user()->username;
            $terundang = is_array($agenda->yang_terundang) 
                ? $agenda->yang_terundang 
                : (json_decode($agenda->yang_terundang, true) ?? []);
            
            // Cek apakah semua pegawai aktif terundang
            $semuaNikAktif = Pegawai::where('stts_aktif', 'AKTIF')->pluck('nik')->toArray();
            $isAll = false;
            
            if (is_array($terundang)) {
                $intersect = array_intersect($semuaNikAktif, $terundang);
                $isAll = count($intersect) === count($semuaNikAktif) && count($terundang) === count($semuaNikAktif);
            }
            
            if (!in_array('all', $terundang) && !$isAll && !in_array($userNik, $terundang)) {
                return redirect()->route('absensi_agenda.index')
                    ->with('error', 'Anda tidak diundang dalam agenda ini.');
            }
            
            // Validasi waktu agenda
            $now = Carbon::now();
            $mulai = Carbon::parse($agenda->mulai);
            $akhir = $agenda->akhir ? Carbon::parse($agenda->akhir) : null;
            
            // Cek apakah agenda sudah dimulai (boleh scan 15 menit sebelum mulai)
            if ($now->lt($mulai->subMinutes(15))) {
                return redirect()->route('absensi_agenda.index')
                    ->with('error', 'Agenda belum dimulai. Waktu mulai: ' . Carbon::parse($agenda->mulai)->format('d M Y H:i'));
            }
            
            // Cek apakah agenda sudah berakhir (boleh scan sampai 1 jam setelah akhir)
            if ($akhir && $now->gt($akhir->addHour())) {
                return redirect()->route('absensi_agenda.index')
                    ->with('error', 'Agenda sudah berakhir. Waktu akhir: ' . Carbon::parse($agenda->akhir)->format('d M Y H:i'));
            }
        }
        
        return view('absensi_agenda.scan', compact('agenda'));
    }

    
    // Method to show the QR scanner page
    public function showScanQRCodePage()
    {
        // Return the view where QR code scanner is implemented
        return view('absensi_agenda.scan_qr');
    }

    // Method to scan attendance (QR code scan)
    public function scanAttendance(Request $request)
    {
        // Validasi input
        $validated = $request->validate([
            'agenda_id' => 'required|integer|exists:agendas,id',
            'token' => 'required|string',
        ]);
    
        $agendaId = $validated['agenda_id'];
        $token = $validated['token'];
    
        // 🔒 Validasi token: harus ada & belum expired
        $agendaToken = AgendaToken::where('agenda_id', $agendaId)
            ->where('token', $token)
            ->where('expiry', '>', now())
            ->first();
    
        if (!$agendaToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau telah kedaluwarsa.'
            ], 400);
        }
    
        // Pastikan user login
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi.'
            ], 401);
        }
    
        $nik = Auth::user()->username;
    
        // 🔒 Cek apakah user diundang
        $agenda = Agenda::findOrFail($agendaId);
        $terundang = is_array($agenda->yang_terundang)
            ? $agenda->yang_terundang
            : (json_decode($agenda->yang_terundang, true) ?? []);
        
        // Cek apakah semua pegawai aktif terundang
        $semuaNikAktif = Pegawai::where('stts_aktif', 'AKTIF')->pluck('nik')->toArray();
        $isAll = false;
        
        if (is_array($terundang)) {
            $intersect = array_intersect($semuaNikAktif, $terundang);
            $isAll = count($intersect) === count($semuaNikAktif) && count($terundang) === count($semuaNikAktif);
        }
        
        if (!in_array('all', $terundang) && !$isAll && !in_array($nik, $terundang)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak diundang dalam agenda ini.'
            ], 403);
        }
        
        // 🔒 Validasi waktu agenda
        $now = Carbon::now();
        $mulai = Carbon::parse($agenda->mulai);
        $akhir = $agenda->akhir ? Carbon::parse($agenda->akhir) : null;
        
        // Cek apakah agenda sudah dimulai (boleh scan 15 menit sebelum mulai)
        if ($now->lt($mulai->copy()->subMinutes(15))) {
            return response()->json([
                'success' => false,
                'message' => 'Agenda belum dimulai. Waktu mulai: ' . $mulai->format('d M Y H:i')
            ], 400);
        }
        
        // Cek apakah agenda sudah berakhir (boleh scan sampai 1 jam setelah akhir)
        if ($akhir && $now->gt($akhir->copy()->addHour())) {
            return response()->json([
                'success' => false,
                'message' => 'Agenda sudah berakhir. Waktu akhir: ' . Carbon::parse($agenda->akhir)->format('d M Y H:i')
            ], 400);
        }
    
        // 🔒 Cek absen ganda
        if (AbsensiAgenda::where('nik', $nik)->where('agenda_id', $agendaId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah melakukan absensi untuk agenda ini.'
            ], 409);
        }
    
        // ✅ Simpan absensi
        AbsensiAgenda::create([
            'nik' => $nik,
            'agenda_id' => $agendaId,
            'waktu_kehadiran' => now(),
            'token' => $token, // simpan token asli
        ]);
    
        // ❌ JANGAN HAPUS TOKEN! Biarkan expired otomatis.
        // $agendaToken->delete(); ← HAPUS BARIS INI!
    
        return response()->json([
            'success' => true,
            'message' => 'Kehadiran berhasil dicatat.',
        ]);
    }

public function rekapAbsensi(Request $request)
{
    $agendaId = $request->get('agenda_id');

    $query = AbsensiAgenda::with(['pegawai', 'agenda'])
        ->when($agendaId, function ($q) use ($agendaId) {
            $q->where('agenda_id', $agendaId);
        });

    // Jika request dari DataTables (AJAX) - untuk daftar lengkap yang terundang
    if ($request->ajax() && $request->get('type') === 'terundang') {
        if ($agendaId) {
            $agenda = Agenda::find($agendaId);
            
            if ($agenda) {
                $terundang = $agenda->yang_terundang ?? [];
                
                // Handle jika yang_terundang adalah string JSON
                if (is_string($terundang)) {
                    $terundang = json_decode($terundang, true) ?? [];
                }
                
                // Cek apakah semua pegawai aktif terundang
                $semuaNikAktif = Pegawai::where('stts_aktif', 'AKTIF')->pluck('nik')->toArray();
                $isAll = false;
                
                if (is_array($terundang)) {
                    // Cek apakah semua NIK aktif ada di yang terundang dan jumlahnya sama
                    $intersect = array_intersect($semuaNikAktif, $terundang);
                    $isAll = count($intersect) === count($semuaNikAktif) && count($terundang) === count($semuaNikAktif);
                }
                
                // 🔹 Kalau "all" atau semua pegawai aktif terundang, ambil semua pegawai aktif
                if ($isAll || (is_array($terundang) && in_array("all", $terundang))) {
                    $pegawaiIds = $semuaNikAktif;
                } else {
                    $pegawaiIds = is_array($terundang) ? $terundang : [];
                }
                
                // Ambil semua pegawai yang terundang
                $pegawaiList = Pegawai::whereIn('nik', $pegawaiIds)->get();
                
                // Ambil yang sudah absen
                $sudahAbsen = AbsensiAgenda::where('agenda_id', $agendaId)
                    ->whereIn('nik', $pegawaiIds)
                    ->with('pegawai')
                    ->get()
                    ->keyBy('nik');
                
                // Format data untuk DataTables
                $data = $pegawaiList->map(function($pegawai) use ($sudahAbsen) {
                    $absensi = $sudahAbsen->get($pegawai->nik);
                    return [
                        'nik' => $pegawai->nik,
                        'nama' => $pegawai->nama,
                        'jabatan' => $pegawai->jbtn ?? '-',
                        'departemen' => $pegawai->departemen ?? '-',
                        'status' => $absensi ? 'hadir' : 'tidak_hadir',
                        'waktu_kehadiran' => $absensi ? Carbon::parse($absensi->waktu_kehadiran)->format('d M Y H:i') : '-',
                    ];
                });
                
                return response()->json([
                    'data' => $data->values(),
                    'recordsTotal' => $data->count(),
                    'recordsFiltered' => $data->count(),
                ]);
            }
        }
        
        return response()->json(['data' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0]);
    }
    
    // Jika request dari DataTables (AJAX) - untuk yang sudah absen saja (backward compatibility)
    if ($request->ajax()) {
        $rekap = null;

        if ($agendaId) {
            $agenda = Agenda::find($agendaId);

            if ($agenda) {
                $terundang = $agenda->yang_terundang ?? [];

                // 🔹 Kalau "all" maka semua pegawai
                if (is_array($terundang) && in_array("all", $terundang)) {
                    $pegawaiIds = Pegawai::where('stts_aktif', 'AKTIF')->pluck('nik')->toArray();
                } else {
                    $pegawaiIds = is_array($terundang) ? $terundang : [];
                }

                $jumlahUndangan = count($pegawaiIds);

                // Hitung hadir
                $jumlahHadir = AbsensiAgenda::where('agenda_id', $agendaId)
                    ->whereIn('nik', $pegawaiIds)
                    ->whereNotNull('waktu_kehadiran')
                    ->count();

                $jumlahTidakHadir = $jumlahUndangan - $jumlahHadir;

                $rekap = [
                    'judul'              => $agenda->judul,
                    'jumlah_undangan'    => $jumlahUndangan,
                    'jumlah_hadir'       => $jumlahHadir,
                    'jumlah_tidak_hadir' => $jumlahTidakHadir,
                ];
            }
        }

        return DataTables::of($query)
            ->addColumn('no', function () {
                return ''; // diisi otomatis oleh DataTables
            })
            ->addColumn('pegawai', function ($absensi) {
                return $absensi->pegawai ? $absensi->pegawai->nama : 'Tidak ditemukan';
            })
            ->addColumn('jabatan', function ($absensi) {
                return $absensi->pegawai ? $absensi->pegawai->jbtn : '-';
            })
            ->addColumn('departemen', function ($absensi) {
                return $absensi->pegawai ? $absensi->pegawai->departemen : '-';
            })
            ->addColumn('agenda', function ($absensi) {
                return $absensi->agenda ? $absensi->agenda->judul : 'Tidak ditemukan';
            })
            ->editColumn('waktu_kehadiran', function ($absensi) {
                return $absensi->waktu_kehadiran 
                    ? Carbon::parse($absensi->waktu_kehadiran)->format('d M Y H:i') 
                    : '-';
            })
            ->rawColumns(['no', 'pegawai', 'jabatan', 'departemen', 'agenda'])
            ->with([
                'rekap' => $rekap // ✅ kirim sebagai array
            ])
            ->make(true);
    }

    $agendas = Agenda::with(['pimpinan'])->get(['id', 'judul', 'mulai', 'akhir', 'pimpinan_rapat']);
    return view('absensi_agenda.rekap', compact('agendas'));
}


}
