<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Models\Agenda;
use App\Models\AgendaToken;
use App\Models\AbsensiAgenda;
use App\Models\Pegawai;
use App\Services\AbsensiAgendaService;
use App\Services\AbsensiAgendaAuditService;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

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
            
            $userNik = Auth::user()->username;
            if (!$agenda->userTerundang($userNik)) {
                return redirect()->route('absensi_agenda.index')
                    ->with('error', 'Anda tidak diundang dalam agenda ini.');
            }

            // Validasi waktu agenda
            $now = Carbon::now();
            $mulai = Carbon::parse($agenda->mulai);
            $akhir = $agenda->akhir ? Carbon::parse($agenda->akhir) : null;
            
            // Cek apakah agenda sudah dimulai (boleh scan 15 menit sebelum mulai)
            if ($now->lt($mulai->copy()->subMinutes(15))) {
                return redirect()->route('absensi_agenda.index')
                    ->with('error', 'Agenda belum dimulai. Waktu mulai: ' . $mulai->format('d M Y H:i'));
            }
            
            // Cek apakah agenda sudah berakhir (boleh scan sampai 1 jam setelah akhir)
            if ($akhir && $now->gt($akhir->copy()->addHour())) {
                return redirect()->route('absensi_agenda.index')
                    ->with('error', 'Agenda sudah berakhir. Waktu akhir: ' . $akhir->format('d M Y H:i'));
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

        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Pengguna tidak terautentikasi.',
            ], 401);
        }

        $nik = Auth::user()->username;
        $agenda = Agenda::findOrFail($agendaId);

        $error = AbsensiAgendaService::validateScan($agenda, $nik, $token);
        if ($error) {
            return response()->json([
                'success' => false,
                'message' => $error['message'],
            ], $error['http']);
        }

        $absensi = AbsensiAgenda::create([
            'nik' => $nik,
            'agenda_id' => $agendaId,
            'waktu_kehadiran' => now(),
            'status_kehadiran' => 'hadir',
            'token' => $token,
            'ip_address' => $request->ip(),
            'device_token' => $request->input('device_token'),
        ]);

        // Log audit
        AbsensiAgendaAuditService::logCreate(
            $absensi->id_absensi_agenda,
            $agendaId,
            $nik,
            'hadir',
            $nik,
            $request,
            $request->input('device_token')
        );

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

public function rekapAbsensi(Request $request)
{
    $agendaId = $request->get('agenda_id');
    $userNik = Auth::user()->username;

    // Validasi akses: jika ada agenda_id, cek apakah user adalah pimpinan rapat atau notulen
    if ($agendaId) {
        $agenda = Agenda::find($agendaId);
        
        if (!$agenda) {
            abort(404, 'Agenda tidak ditemukan.');
        }

        // Cek apakah user adalah pimpinan rapat atau notulen
        $isPimpinan = ($userNik === $agenda->pimpinan_rapat);
        $isNotulen = ($userNik === $agenda->notulen);
        
        // Cek apakah user memiliki akses rekap.view (untuk admin/staff yang berwenang)
        $hasRekapAccess = Auth::user()->canAccess('rekap.view');

        // Jika bukan pimpinan, bukan notulen, dan tidak punya akses rekap.view, tolak akses
        if (!$isPimpinan && !$isNotulen && !$hasRekapAccess) {
            abort(403, 'Akses ditolak. Hanya pimpinan rapat, notulen, atau user dengan akses rekap yang dapat melihat rekap absensi agenda ini.');
        }
    } else {
        // Jika tidak ada agenda_id (melihat semua agenda), cek akses rekap.view
        $hasRekapAccess = Auth::user()->canAccess('rekap.view');

        // Jika tidak punya akses rekap.view, hanya tampilkan agenda di mana user adalah pimpinan atau notulen
        if (!$hasRekapAccess) {
            // Filter agenda di mana user adalah pimpinan atau notulen
            // Ini akan dilakukan di view dengan filter
        }
    }

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
                
                // Auto-detect status dari pengajuan libur untuk peserta yang belum absen
                AbsensiAgendaService::syncAbsensiFromPengajuanLibur($agendaId);
                
                // Ambil semua pegawai yang terundang
                $pegawaiList = Pegawai::whereIn('nik', $pegawaiIds)->get();
                
                // Ambil semua absensi (termasuk yang auto-detect)
                $sudahAbsen = AbsensiAgenda::where('agenda_id', $agendaId)
                    ->whereIn('nik', $pegawaiIds)
                    ->with('pegawai')
                    ->get()
                    ->keyBy('nik');
                
                // Cek apakah user adalah pimpinan rapat atau notulen
                $userNik = Auth::user()->username;
                $canEdit = ($userNik === $agenda->pimpinan_rapat || $userNik === $agenda->notulen);
                
                // Format data untuk DataTables
                $data = $pegawaiList->map(function($pegawai) use ($sudahAbsen, $canEdit) {
                    $absensi = $sudahAbsen->get($pegawai->nik);
                    $status = $absensi ? ($absensi->status_kehadiran ?? 'hadir') : 'tidak_hadir';
                    
                    return [
                        'nik' => $pegawai->nik,
                        'nama' => $pegawai->nama,
                        'jabatan' => $pegawai->jbtn ?? '-',
                        'departemen' => $pegawai->departemen ?? '-',
                        'status' => $status,
                        'status_kehadiran' => $status, // untuk kompatibilitas
                        'alasan' => $absensi ? $absensi->alasan : null,
                        'waktu_kehadiran' => $absensi && $absensi->waktu_kehadiran 
                            ? Carbon::parse($absensi->waktu_kehadiran)->format('d M Y H:i') 
                            : '-',
                        'id_absensi' => $absensi ? $absensi->id_absensi_agenda : null,
                        'can_edit' => $canEdit,
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

                // Hitung berdasarkan status
                $jumlahHadir = AbsensiAgenda::where('agenda_id', $agendaId)
                    ->whereIn('nik', $pegawaiIds)
                    ->where('status_kehadiran', 'hadir')
                    ->count();
                
                $jumlahIjin = AbsensiAgenda::where('agenda_id', $agendaId)
                    ->whereIn('nik', $pegawaiIds)
                    ->where('status_kehadiran', 'ijin')
                    ->count();
                
                $jumlahCuti = AbsensiAgenda::where('agenda_id', $agendaId)
                    ->whereIn('nik', $pegawaiIds)
                    ->where('status_kehadiran', 'cuti')
                    ->count();
                
                $jumlahSakit = AbsensiAgenda::where('agenda_id', $agendaId)
                    ->whereIn('nik', $pegawaiIds)
                    ->where('status_kehadiran', 'sakit')
                    ->count();
                
                $jumlahBerhalangan = AbsensiAgenda::where('agenda_id', $agendaId)
                    ->whereIn('nik', $pegawaiIds)
                    ->where('status_kehadiran', 'berhalangan')
                    ->count();

                $jumlahTidakHadir = AbsensiAgenda::where('agenda_id', $agendaId)
                    ->whereIn('nik', $pegawaiIds)
                    ->where('status_kehadiran', 'tidak_hadir')
                    ->count();

                $rekap = [
                    'judul'              => $agenda->judul,
                    'jumlah_undangan'    => $jumlahUndangan,
                    'jumlah_hadir'       => $jumlahHadir,
                    'jumlah_ijin'        => $jumlahIjin,
                    'jumlah_cuti'       => $jumlahCuti,
                    'jumlah_sakit'       => $jumlahSakit,
                    'jumlah_berhalangan' => $jumlahBerhalangan,
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

    // Filter agenda berdasarkan akses user
    $hasRekapAccess = Auth::user()->canAccess('rekap.view');

    if ($hasRekapAccess) {
        // Jika punya akses rekap.view, tampilkan semua agenda
        $agendas = Agenda::with(['pimpinan'])->get(['id', 'judul', 'mulai', 'akhir', 'pimpinan_rapat', 'notulen']);
    } else {
        // Jika tidak punya akses, hanya tampilkan agenda di mana user adalah pimpinan atau notulen
        $agendas = Agenda::with(['pimpinan'])
            ->where(function($query) use ($userNik) {
                $query->where('pimpinan_rapat', $userNik)
                      ->orWhere('notulen', $userNik);
            })
            ->get(['id', 'judul', 'mulai', 'akhir', 'pimpinan_rapat', 'notulen']);
    }
    
    return view('absensi_agenda.rekap', compact('agendas'));
}

    /**
     * Update status kehadiran absensi agenda
     * Hanya bisa diakses oleh pimpinan rapat atau notulen
     */
    public function updateStatusKehadiran(Request $request)
    {
        $validated = $request->validate([
            'id_absensi' => 'required|integer|exists:absensi_agenda,id_absensi_agenda',
            'status_kehadiran' => 'required|in:hadir,ijin,cuti,sakit,berhalangan,tidak_hadir',
            'alasan' => 'nullable|string|max:500',
        ]);

        $absensi = AbsensiAgenda::with('agenda')->findOrFail($validated['id_absensi']);
        $agenda = $absensi->agenda;

        // Validasi: hanya pimpinan rapat atau notulen yang bisa edit
        $userNik = Auth::user()->username;
        if ($userNik !== $agenda->pimpinan_rapat && $userNik !== $agenda->notulen) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk mengubah status absensi. Hanya pimpinan rapat atau notulen yang dapat mengubah status.'
            ], 403);
        }

        // Simpan status lama untuk audit
        $statusLama = $absensi->status_kehadiran;

        // Update status
        $absensi->status_kehadiran = $validated['status_kehadiran'];
        $absensi->alasan = $validated['alasan'] ?? null;

        // Jika status diubah menjadi 'hadir' dan belum ada waktu_kehadiran, set waktu sekarang
        if ($validated['status_kehadiran'] === 'hadir' && !$absensi->waktu_kehadiran) {
            $absensi->waktu_kehadiran = now();
        }
        // Jika status bukan 'hadir', set waktu_kehadiran menjadi null
        elseif ($validated['status_kehadiran'] !== 'hadir') {
            $absensi->waktu_kehadiran = null;
        }

        $absensi->ip_address = $request->ip();
        $absensi->device_token = $request->input('device_token');
        $absensi->save();

        // Log audit perubahan status
        AbsensiAgendaAuditService::logUpdateStatus(
            $absensi->id_absensi_agenda,
            $agenda->id,
            $absensi->nik,
            $statusLama,
            $validated['status_kehadiran'],
            $validated['alasan'] ?? null,
            $userNik,
            $request,
            $request->input('device_token')
        );

        return response()->json([
            'success' => true,
            'message' => 'Status kehadiran berhasil diupdate.',
            'data' => [
                'status' => $absensi->status_kehadiran,
                'alasan' => $absensi->alasan,
            ]
        ]);
    }

    /**
     * Create atau update absensi untuk peserta yang belum ada
     * Digunakan untuk input manual status ketidakhadiran
     */
    public function createOrUpdateAbsensi(Request $request)
    {
        $validated = $request->validate([
            'agenda_id' => 'required|integer|exists:agendas,id',
            'nik' => 'required|string',
            'status_kehadiran' => 'required|in:ijin,cuti,sakit,berhalangan,tidak_hadir',
            'alasan' => 'nullable|string|max:500',
        ]);

        $agenda = Agenda::findOrFail($validated['agenda_id']);

        // Validasi: hanya pimpinan rapat atau notulen yang bisa edit
        $userNik = Auth::user()->username;
        if ($userNik !== $agenda->pimpinan_rapat && $userNik !== $agenda->notulen) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk mengubah status absensi.'
            ], 403);
        }

        // Cek apakah sudah ada absensi
        $absensi = AbsensiAgenda::where('agenda_id', $validated['agenda_id'])
            ->where('nik', $validated['nik'])
            ->first();

        if ($absensi) {
            // Update existing - simpan status lama
            $statusLama = $absensi->status_kehadiran;
            $absensi->status_kehadiran = $validated['status_kehadiran'];
            $absensi->alasan = $validated['alasan'] ?? null;
            $absensi->waktu_kehadiran = null; // Bukan hadir, jadi waktu null
            $absensi->ip_address = $request->ip();
            $absensi->device_token = $request->input('device_token');
            $absensi->save();

            // Log audit
            AbsensiAgendaAuditService::logUpdateStatus(
                $absensi->id_absensi_agenda,
                $validated['agenda_id'],
                $validated['nik'],
                $statusLama,
                $validated['status_kehadiran'],
                $validated['alasan'] ?? null,
                $userNik,
                $request,
                $request->input('device_token')
            );
        } else {
            // Create new
            $newAbsensi = AbsensiAgenda::create([
                'nik' => $validated['nik'],
                'agenda_id' => $validated['agenda_id'],
                'status_kehadiran' => $validated['status_kehadiran'],
                'alasan' => $validated['alasan'] ?? null,
                'waktu_kehadiran' => null,
                'token' => 'MANUAL-' . $validated['agenda_id'] . '-' . $validated['nik'] . '-' . time(),
                'ip_address' => $request->ip(),
                'device_token' => $request->input('device_token'),
            ]);

            // Log audit manual create
            AbsensiAgendaAuditService::logManualCreate(
                $newAbsensi->id_absensi_agenda,
                $validated['agenda_id'],
                $validated['nik'],
                $validated['status_kehadiran'],
                $validated['alasan'] ?? null,
                $userNik,
                $request,
                $request->input('device_token')
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Status kehadiran berhasil disimpan.',
        ]);
    }

    /**
     * Export rekap absensi ke PDF
     */
    public function exportPDF(Request $request)
    {
        $agendaId = $request->get('agenda_id');
        
        if (!$agendaId) {
            return redirect()->route('rekap-absensi')
                ->with('error', 'Agenda tidak ditemukan.');
        }

        $agenda = Agenda::with(['pimpinan', 'notulenPegawai'])->findOrFail($agendaId);
        
        // Auto-detect status dari pengajuan libur
        AbsensiAgendaService::syncAbsensiFromPengajuanLibur($agendaId);
        
        // Ambil data peserta yang terundang
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
        
        // Tentukan daftar NIK yang terundang
        if ($isAll || (is_array($terundang) && in_array("all", $terundang))) {
            $pegawaiIds = $semuaNikAktif;
        } else {
            $pegawaiIds = is_array($terundang) ? $terundang : [];
        }
        
        // Ambil semua pegawai yang terundang
        $pegawaiList = Pegawai::whereIn('nik', $pegawaiIds)->orderBy('nama')->get();
        
        // Ambil semua absensi
        $absensiData = AbsensiAgenda::where('agenda_id', $agendaId)
            ->whereIn('nik', $pegawaiIds)
            ->with('pegawai')
            ->get()
            ->keyBy('nik');
        
        // Format data untuk PDF
        $dataPresensi = $pegawaiList->map(function($pegawai) use ($absensiData) {
            $absensi = $absensiData->get($pegawai->nik);
            $status = $absensi ? ($absensi->status_kehadiran ?? 'tidak_hadir') : 'tidak_hadir';
            
            // Format status untuk display
            $statusLabel = [
                'hadir' => 'Hadir',
                'ijin' => 'Ijin',
                'cuti' => 'Cuti',
                'sakit' => 'Sakit',
                'berhalangan' => 'Berhalangan',
                'tidak_hadir' => 'Tidak Hadir'
            ];
            
            return [
                'nik' => $pegawai->nik,
                'nama' => $pegawai->nama,
                'jabatan' => $pegawai->jbtn ?? '-',
                'departemen' => $pegawai->departemen ?? '-',
                'status' => $statusLabel[$status] ?? 'Tidak Hadir',
                'status_key' => $status,
                'waktu_kehadiran' => $absensi && $absensi->waktu_kehadiran 
                    ? Carbon::parse($absensi->waktu_kehadiran)->format('d M Y H:i') 
                    : '-',
                'alasan' => $absensi ? $absensi->alasan : null,
            ];
        });
        
        // Hitung statistik
        $statistik = [
            'total' => $dataPresensi->count(),
            'hadir' => $dataPresensi->where('status_key', 'hadir')->count(),
            'ijin' => $dataPresensi->where('status_key', 'ijin')->count(),
            'cuti' => $dataPresensi->where('status_key', 'cuti')->count(),
            'sakit' => $dataPresensi->where('status_key', 'sakit')->count(),
            'berhalangan' => $dataPresensi->where('status_key', 'berhalangan')->count(),
            'tidak_hadir' => $dataPresensi->where('status_key', 'tidak_hadir')->count(),
        ];
        
        // Encode logo ke base64
        $path = public_path('assets/images/logo_hitam.png');
        $base64 = null;
        if (file_exists($path)) {
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
        
        // Format tanggal
        Carbon::setLocale('id');
        $tanggalAgenda = Carbon::parse($agenda->mulai)->translatedFormat('d F Y');
        $waktuAgenda = Carbon::parse($agenda->mulai)->translatedFormat('H:i');
        
        $data = [
            'agenda' => $agenda,
            'dataPresensi' => $dataPresensi,
            'statistik' => $statistik,
            'logo' => $base64,
            'tanggalAgenda' => $tanggalAgenda,
            'waktuAgenda' => $waktuAgenda,
        ];
        
        $pdf = Pdf::loadView('absensi_agenda.export_pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('enable-javascript', true)
            ->setOption('encoding', 'utf-8')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);
        
        $filename = 'Daftar_Presensi_' . str_replace(' ', '_', $agenda->judul) . '_' . Carbon::parse($agenda->mulai)->format('Y-m-d') . '.pdf';
        
        // Return PDF untuk preview di browser (bukan langsung download)
        return $pdf->stream($filename);
    }

}
