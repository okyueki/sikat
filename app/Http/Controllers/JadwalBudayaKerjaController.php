<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\JadwalBudayaKerja;
use App\Models\JadwalGeneratorMember;
use App\Models\Petugas;
use App\Models\Dokter;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Services\WhatsAppService;

class JadwalBudayaKerjaController extends Controller
{
    protected WhatsAppService $waService;

    /**
     * Template ID untuk fitur Jadwal Budaya Kerja
     * Ganti dengan UUID template yang sudah di-approve di Qontak
     */
    protected string $templateIdJadwal = 'bbbcc349-de87-4b48-8e30-ebf389d329a6';

    public function __construct(WhatsAppService $waService)
    {
        $this->waService = $waService;
    }

    /**
     * Menampilkan data Jadwal Budaya Kerja dengan filter dan summary.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = JadwalBudayaKerja::with('petugas');
            
            // Apply filters
            if ($request->filled('bulan')) {
                $query->whereMonth('tanggal_bertugas', $request->bulan);
            }
            if ($request->filled('tahun')) {
                $query->whereYear('tanggal_bertugas', $request->tahun);
            }
            if ($request->filled('shift')) {
                $query->where('shift', $request->shift);
            }
            
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('select', function($row) {
                    return '<input type="checkbox" class="row-select form-check-input" value="' . e($row->id_jadwal_budaya_kerja) . '">';
                })
                ->addColumn('nama', function($row) {
                    return $row->petugas ? $row->petugas->nama : '-';
                })
                ->addColumn('no_telp', function($row) {
                    return $row->petugas ? $row->petugas->no_telp : '-';
                })
                ->addColumn('hari', function($row) {
                    return Carbon::parse($row->tanggal_bertugas)->translatedFormat('l');
                })
                ->addColumn('whatsapp_status', function($row) {
                    // Check WhatsApp log status
                    $log = \App\Models\WhatsappLog::where('nik', $row->nik)
                        ->whereDate('sent_at', $row->tanggal_bertugas)
                        ->latest()
                        ->first();
                    
                    if ($log) {
                        if ($log->delivery_status === 'success' || $log->delivery_status === 'read') {
                            return '<span class="badge bg-success">Terkirim</span>';
                        } elseif ($log->delivery_status === 'failed') {
                            return '<span class="badge bg-danger">Gagal</span>';
                        } else {
                            return '<span class="badge bg-warning">Pending</span>';
                        }
                    }
                    return '<span class="badge bg-secondary">-</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<a href="'.route('jadwalbudayakerja.edit', $row->id_jadwal_budaya_kerja).'" class="btn btn-sm btn-success">Edit</a>
                            <a href="'.route('jadwalbudayakerja.destroy', $row->id_jadwal_budaya_kerja).'" class="btn btn-sm btn-danger delete-btn">Delete</a>';
                })
                ->rawColumns(['select', 'action', 'whatsapp_status'])
                ->make(true);
        }
        
        // Summary statistics
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        $summary = [
            'total' => JadwalBudayaKerja::count(),
            'total_bulan_ini' => JadwalBudayaKerja::whereMonth('tanggal_bertugas', $currentMonth)
                ->whereYear('tanggal_bertugas', $currentYear)
                ->count(),
            'pagi' => JadwalBudayaKerja::where('shift', 'Pagi')->count(),
            'sore' => JadwalBudayaKerja::where('shift', 'Sore')->count(),
            'terkirim' => \App\Models\WhatsappLog::whereMonth('sent_at', $currentMonth)
                ->whereYear('sent_at', $currentYear)
                ->whereIn('delivery_status', ['success', 'read'])
                ->count(),
        ];
        
        return view('jadwal_budaya_kerja.index', compact('summary', 'currentMonth', 'currentYear'));
    }

    public function create(Request $request)
    {
        $petugas = Petugas::with(['pegawai.departemen_unit'])
            ->where('status', '1')
            ->get();
        $dokter = Dokter::with(['pegawai.departemen_unit'])
            ->where('status', '1')
            ->get(); // ambil dokter aktif juga
        $tanggal_bertugas = $request->query('tanggal', ''); 

        $pegawaiMeta = $petugas->mapWithKeys(function ($p) {
            return [
                $p->nip => [
                    'dept' => $p->pegawai?->departemen_unit?->nama ?? $p->pegawai?->departemen ?? '-',
                    'hp' => $p->no_telp ?? '-',
                ],
            ];
        })->merge(
            $dokter->mapWithKeys(function ($d) {
                return [
                    $d->kd_dokter => [
                        'dept' => $d->pegawai?->departemen_unit?->nama ?? $d->pegawai?->departemen ?? '-',
                        'hp' => $d->no_telp ?? '-',
                    ],
                ];
            })
        )->toArray();
    
        return view('jadwal_budaya_kerja.create', compact('petugas', 'dokter', 'tanggal_bertugas', 'pegawaiMeta'));
    }


    
    public function store(Request $request)
    {
        $request->validate([
            'nik' => 'required|max:20',
            'tanggal_bertugas' => 'required|date',
            'shift' => 'required',
        ]);

        JadwalBudayaKerja::create($request->all());
        return redirect()->route('jadwalbudayakerja.index')->with('success', 'Data Jadwal Budaya Kerja berhasil ditambahkan');
    }

    public function edit($id)
    {
        $jadwal = JadwalBudayaKerja::findOrFail($id);
        $petugas = Petugas::with(['pegawai.departemen_unit'])
            ->where(function ($q) use ($jadwal) {
                $q->where('status', '1')
                    ->orWhere('nip', $jadwal->nik);
            })
            ->get();
        $dokter = Dokter::with(['pegawai.departemen_unit'])
            ->where(function ($q) use ($jadwal) {
                $q->where('status', '1')
                    ->orWhere('kd_dokter', $jadwal->nik);
            })
            ->get();

        $pegawaiMeta = $petugas->mapWithKeys(function ($p) {
            return [
                $p->nip => [
                    'dept' => $p->pegawai?->departemen_unit?->nama ?? $p->pegawai?->departemen ?? '-',
                    'hp' => $p->no_telp ?? '-',
                ],
            ];
        })->merge(
            $dokter->mapWithKeys(function ($d) {
                return [
                    $d->kd_dokter => [
                        'dept' => $d->pegawai?->departemen_unit?->nama ?? $d->pegawai?->departemen ?? '-',
                        'hp' => $d->no_telp ?? '-',
                    ],
                ];
            })
        )->toArray();

        return view('jadwal_budaya_kerja.edit', compact('jadwal', 'petugas', 'dokter', 'pegawaiMeta'));
    }

    /**
     * Memperbarui data jadwal budaya kerja.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nik' => 'required|max:20',
            'tanggal_bertugas' => 'required|date',
            'shift' => 'required',
        ]);

        $jadwal = JadwalBudayaKerja::findOrFail($id);
        $jadwal->update($request->all());

        return redirect()->route('jadwalbudayakerja.index')->with('success', 'Data Jadwal Budaya Kerja berhasil diperbarui');
    }

    /**
     * Menghapus data jadwal budaya kerja.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        JadwalBudayaKerja::destroy($id);
        return response()->json(['success' => 'Data berhasil dihapus']);

    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'Tidak ada data yang dipilih.'], 422);
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, static fn ($id) => $id > 0);

        if (empty($ids)) {
            return response()->json(['message' => 'Data pilihan tidak valid.'], 422);
        }

        $deleted = JadwalBudayaKerja::whereIn('id_jadwal_budaya_kerja', $ids)->delete();

        return response()->json([
            'message' => $deleted > 0
                ? $deleted . ' data berhasil dihapus.'
                : 'Tidak ada data yang dihapus.',
            'deleted' => $deleted,
        ]);
    }
    
    /**
     * Kirim notifikasi otomatis dengan logika:
     * - Shift Pagi: Dikirim jam 21:30 di hari sebelumnya
     * - Shift Sore: Dikirim jam 09:00 di hari yang sama
     */
    public function kirimOtomatis()
    {
        $now = Carbon::now();
        $currentHour = (int) $now->format('H');
        $results = [];

        // Tentukan shift yang perlu dikirim berdasarkan waktu sekarang
        if ($currentHour >= 21 && $currentHour < 22) {
            // Jam 21:00-22:00: Kirim notif untuk shift PAGI besok
            $results = $this->kirimNotifShiftPagiBesok();
        } elseif ($currentHour >= 9 && $currentHour < 10) {
            // Jam 09:00-10:00: Kirim notif untuk shift SORE hari ini
            $results = $this->kirimNotifShiftSoreHariIni();
        } else {
            return response()->json([
                'message' => 'Bukan waktu pengiriman yang tepat',
                'waktu_sekarang' => $now->format('H:i'),
                'info' => 'Pengiriman: Shift Pagi jam 21:30, Shift Sore jam 09:00'
            ]);
        }

        $successCount = collect($results)->where('status', true)->count();
        $failedCount = collect($results)->where('status', false)->count();

        return response()->json([
            'waktu_kirim' => $now->format('Y-m-d H:i:s'),
            'total' => count($results),
            'berhasil' => $successCount,
            'gagal' => $failedCount,
            'detail' => $results
        ]);
    }

    /**
     * Kirim notifikasi untuk shift PAGI (besok)
     */
    protected function kirimNotifShiftPagiBesok(): array
    {
        $tanggalBesok = Carbon::tomorrow()->toDateString();
        $hariBesok = Carbon::tomorrow()->isoFormat('dddd, D MMMM Y');

        $jadwal = JadwalBudayaKerja::with('petugas')
            ->where('tanggal_bertugas', $tanggalBesok)
            ->whereRaw('LOWER(TRIM(shift)) = ?', ['pagi'])
            ->get();

        return $this->prosesPengiriman($jadwal, $hariBesok, $tanggalBesok);
    }

    /**
     * Kirim notifikasi untuk shift SORE (hari ini)
     */
    protected function kirimNotifShiftSoreHariIni(): array
    {
        $tanggalHariIni = Carbon::today()->toDateString();
        $hariHariIni = Carbon::today()->isoFormat('dddd, D MMMM Y');

        $jadwal = JadwalBudayaKerja::with('petugas')
            ->where('tanggal_bertugas', $tanggalHariIni)
            ->whereRaw('LOWER(TRIM(shift)) = ?', ['sore'])
            ->get();

        return $this->prosesPengiriman($jadwal, $hariHariIni, $tanggalHariIni);
    }

    /**
     * Proses pengiriman pesan ke semua penerima
     */
    protected function prosesPengiriman($jadwal, string $hari, string $tanggal): array
    {
        $results = [];

        foreach ($jadwal as $j) {
            if (!$j->petugas || empty($j->petugas->no_telp)) {
                Log::warning("Skip pengiriman: Petugas {$j->nik} tidak memiliki nomor telepon");
                continue;
            }

            $shift = strtolower(trim($j->shift));
            $jam_shift = $shift === 'pagi' ? '06:30' : ($shift === 'sore' ? '13:30' : '-');

            // Kirim dengan 4 variables: {{1}}, {{2}}, {{3}}, {{4}}
            $result = $this->waService->sendMessageWithVariables([
                'nik' => $j->nik,
                'nama' => $j->petugas->nama,
                'nomor' => $j->petugas->no_telp
            ], [
                $j->petugas->nama,           // {{1}} - Nama
                $hari . ', ' . $tanggal,     // {{2}} - Tanggal
                $j->shift,                   // {{3}} - Shift
                $jam_shift                   // {{4}} - Jam
            ], $this->templateIdJadwal);

            $results[] = $result;

            // Rate limiting: tunggu 1 detik antar pengiriman
            sleep(1);
        }

        return $results;
    }

    /**
     * Manual trigger untuk testing (bypass waktu)
     */
    public function kirimManual(string $shift = null)
    {
        $shift = strtolower($shift ?? 'pagi');
        
        if ($shift === 'pagi') {
            return response()->json($this->kirimNotifShiftPagiBesok());
        } else {
            return response()->json($this->kirimNotifShiftSoreHariIni());
        }
    }

    /**
     * Check delivery status of sent WhatsApp messages
     */
    public function cekStatusWhatsapp(int $logId = null)
    {
        if ($logId) {
            // Check specific message
            $log = \App\Models\WhatsappLog::find($logId);
            if (!$log) {
                return response()->json(['error' => 'Log not found'], 404);
            }

            $this->waService->updateDeliveryStatus($log);
            $log->refresh();

            return response()->json([
                'log_id' => $log->id,
                'qontak_message_id' => $log->qontak_message_id,
                'phone' => $log->phone,
                'nama' => $log->nama,
                'delivery_status' => $log->delivery_status,
                'sent_at' => $log->sent_at,
                'delivered_at' => $log->delivered_at,
                'read_at' => $log->read_at,
            ]);
        }

        // Sync all pending statuses
        $results = $this->waService->syncAllPendingStatuses();

        return response()->json([
            'message' => 'Status sync completed',
            'results' => $results
        ]);
    }

    /**
     * Get WhatsApp logs with delivery status
     */
    public function logWhatsapp()
    {
        $logs = \App\Models\WhatsappLog::latest()
            ->paginate(50);

        return response()->json($logs);
    }
    

    public function getEvents()
    {
        $events = JadwalBudayaKerja::with('petugas')->get()->map(function ($jadwal) {
            return [
                'title' => $jadwal->petugas ? $jadwal->petugas->nama : 'Tidak Diketahui',
                'start' => $jadwal->tanggal_bertugas,
                'description' => "Shift: " . $jadwal->shift . "<br>No Telp: " . ($jadwal->petugas ? $jadwal->petugas->no_telp : '-'),
                'color' => $jadwal->shift == 'Pagi' ? '#007bff' : '#dc3545', // Biru untuk Pagi, Merah untuk Sore
            ];
        });
    
        return response()->json($events);
    }
    
    
    public function kalender()
    {
        return view('jadwal_budaya_kerja.kalender');
    }

    public function generatorForm(Request $request)
    {
        $petugas = Petugas::with(['pegawai.departemen_unit'])
            ->where('status', '1')
            ->orderBy('nama')
            ->get(['nip', 'nama', 'no_telp']);
        $dokter = Dokter::with(['pegawai.departemen_unit'])
            ->where('status', '1')
            ->orderBy('nm_dokter')
            ->get(['kd_dokter', 'nm_dokter', 'no_telp']);

        $selectedPetugas = JadwalGeneratorMember::where('source_type', 'petugas')
            ->where('is_active', true)
            ->pluck('source_id')
            ->toArray();
        $selectedDokter = JadwalGeneratorMember::where('source_type', 'dokter')
            ->where('is_active', true)
            ->pluck('source_id')
            ->toArray();

        $activeMembers = JadwalGeneratorMember::where('is_active', true)
            ->orderByRaw('COALESCE(sort_order, 999999)')
            ->orderBy('id')
            ->get(['id', 'source_type', 'source_id', 'sort_order']);

        $petugasNameMap = $petugas->pluck('nama', 'nip');
        $dokterNameMap = $dokter->pluck('nm_dokter', 'kd_dokter');
        $petugasPhoneMap = $petugas->mapWithKeys(function ($p) {
            return [
                $p->nip => ($p->no_telp ?? '-'),
            ];
        });
        $dokterPhoneMap = $dokter->mapWithKeys(function ($d) {
            return [
                $d->kd_dokter => ($d->no_telp ?? '-'),
            ];
        });
        $petugasDeptMap = $petugas->mapWithKeys(function ($p) {
            return [
                $p->nip => ($p->pegawai?->departemen_unit?->nama ?? $p->pegawai?->departemen ?? '-'),
            ];
        });
        $dokterDeptMap = $dokter->mapWithKeys(function ($d) {
            return [
                $d->kd_dokter => ($d->pegawai?->departemen_unit?->nama ?? $d->pegawai?->departemen ?? '-'),
            ];
        });

        $masterParticipants = $activeMembers->map(function ($member) use ($petugasNameMap, $dokterNameMap, $petugasDeptMap, $dokterDeptMap, $petugasPhoneMap, $dokterPhoneMap) {
            $nama = $member->source_type === 'petugas'
                ? ($petugasNameMap[$member->source_id] ?? '(tidak ditemukan)')
                : ($dokterNameMap[$member->source_id] ?? '(tidak ditemukan)');
            $departemen = $member->source_type === 'petugas'
                ? ($petugasDeptMap[$member->source_id] ?? '-')
                : ($dokterDeptMap[$member->source_id] ?? '-');
            $noTelp = $member->source_type === 'petugas'
                ? ($petugasPhoneMap[$member->source_id] ?? '-')
                : ($dokterPhoneMap[$member->source_id] ?? '-');

            return [
                'member_id' => $member->id,
                'tipe' => ucfirst($member->source_type),
                'id' => $member->source_id,
                'nama' => $nama,
                'departemen' => $departemen,
                'no_telp' => $noTelp,
                'urutan' => $member->sort_order,
            ];
        })->values();

        $rekapBulan = (int) $request->query('rekap_bulan', now()->month);
        $rekapTahun = (int) $request->query('rekap_tahun', now()->year);

        if ($rekapBulan < 1 || $rekapBulan > 12) {
            $rekapBulan = now()->month;
        }
        if ($rekapTahun < 2024 || $rekapTahun > 2100) {
            $rekapTahun = now()->year;
        }

        $rekapRaw = JadwalBudayaKerja::query()
            ->selectRaw("nik, COUNT(*) as total_jadwal, SUM(CASE WHEN LOWER(TRIM(shift)) = 'pagi' THEN 1 ELSE 0 END) as total_pagi, SUM(CASE WHEN LOWER(TRIM(shift)) = 'sore' THEN 1 ELSE 0 END) as total_sore")
            ->whereMonth('tanggal_bertugas', $rekapBulan)
            ->whereYear('tanggal_bertugas', $rekapTahun)
            ->groupBy('nik')
            ->get();

        $nikList = $rekapRaw->pluck('nik')->filter()->values();
        $petugasRekap = Petugas::with(['pegawai.departemen_unit'])
            ->whereIn('nip', $nikList)
            ->get(['nip', 'nama', 'no_telp']);
        $dokterRekap = Dokter::with(['pegawai.departemen_unit'])
            ->whereIn('kd_dokter', $nikList)
            ->get(['kd_dokter', 'nm_dokter', 'no_telp']);

        $petugasRekapMap = $petugasRekap->keyBy('nip');
        $dokterRekapMap = $dokterRekap->keyBy('kd_dokter');

        $rekapRows = $rekapRaw->map(function ($row) use ($petugasRekapMap, $dokterRekapMap) {
            $petugas = $petugasRekapMap->get($row->nik);
            $dokter = $dokterRekapMap->get($row->nik);

            if ($petugas) {
                return [
                    'nik' => $row->nik,
                    'tipe' => 'Petugas',
                    'nama' => $petugas->nama,
                    'departemen' => $petugas->pegawai?->departemen_unit?->nama ?? $petugas->pegawai?->departemen ?? '-',
                    'no_telp' => $petugas->no_telp ?? '-',
                    'total_jadwal' => (int) $row->total_jadwal,
                    'total_pagi' => (int) $row->total_pagi,
                    'total_sore' => (int) $row->total_sore,
                ];
            }

            if ($dokter) {
                return [
                    'nik' => $row->nik,
                    'tipe' => 'Dokter',
                    'nama' => $dokter->nm_dokter,
                    'departemen' => $dokter->pegawai?->departemen_unit?->nama ?? $dokter->pegawai?->departemen ?? '-',
                    'no_telp' => $dokter->no_telp ?? '-',
                    'total_jadwal' => (int) $row->total_jadwal,
                    'total_pagi' => (int) $row->total_pagi,
                    'total_sore' => (int) $row->total_sore,
                ];
            }

            return [
                'nik' => $row->nik,
                'tipe' => '-',
                'nama' => '(Tidak ditemukan)',
                'departemen' => '-',
                'no_telp' => '-',
                'total_jadwal' => (int) $row->total_jadwal,
                'total_pagi' => (int) $row->total_pagi,
                'total_sore' => (int) $row->total_sore,
            ];
        })->sortBy([
            ['total_jadwal', 'asc'],
            ['nama', 'asc'],
        ])->values();

        $minTotal = $rekapRows->isNotEmpty() ? (int) $rekapRows->min('total_jadwal') : 0;
        $maxTotal = $rekapRows->isNotEmpty() ? (int) $rekapRows->max('total_jadwal') : 0;
        $selisihKeadilan = $maxTotal - $minTotal;

        return view('jadwal_budaya_kerja.generator', compact(
            'petugas',
            'dokter',
            'selectedPetugas',
            'selectedDokter',
            'masterParticipants',
            'rekapRows',
            'rekapBulan',
            'rekapTahun',
            'minTotal',
            'maxTotal',
            'selisihKeadilan'
        ));
    }

    public function simpanMasterGenerator(Request $request)
    {
        $petugasIds = collect($request->input('petugas_ids', []))
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->values();
        $dokterIds = collect($request->input('dokter_ids', []))
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->values();

        $totalDipilih = $petugasIds->count() + $dokterIds->count();
        if ($totalDipilih < 1) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Pilih minimal 1 peserta untuk ditambahkan ke master.');
        }

        DB::transaction(function () use ($petugasIds, $dokterIds) {
            $nextOrder = (int) JadwalGeneratorMember::max('sort_order') + 1;
            $now = now();

            foreach ($petugasIds as $id) {
                $existing = JadwalGeneratorMember::where('source_type', 'petugas')
                    ->where('source_id', $id)
                    ->first();

                if ($existing) {
                    $existing->is_active = true;
                    if (empty($existing->sort_order)) {
                        $existing->sort_order = $nextOrder++;
                    }
                    $existing->updated_at = $now;
                    $existing->save();
                    continue;
                }

                JadwalGeneratorMember::create([
                    'source_type' => 'petugas',
                    'source_id' => $id,
                    'is_active' => true,
                    'sort_order' => $nextOrder++,
                ]);
            }

            foreach ($dokterIds as $id) {
                $existing = JadwalGeneratorMember::where('source_type', 'dokter')
                    ->where('source_id', $id)
                    ->first();

                if ($existing) {
                    $existing->is_active = true;
                    if (empty($existing->sort_order)) {
                        $existing->sort_order = $nextOrder++;
                    }
                    $existing->updated_at = $now;
                    $existing->save();
                    continue;
                }

                JadwalGeneratorMember::create([
                    'source_type' => 'dokter',
                    'source_id' => $id,
                    'is_active' => true,
                    'sort_order' => $nextOrder++,
                ]);
            }
        });

        return redirect()
            ->route('jadwalbudayakerja.generator')
            ->with('success', 'Master peserta berhasil diperbarui tanpa reset data lama.');
    }

    public function hapusMasterGenerator(int $memberId)
    {
        $member = JadwalGeneratorMember::findOrFail($memberId);
        $member->delete();

        return redirect()
            ->route('jadwalbudayakerja.generator')
            ->with('success', 'Peserta berhasil dihapus dari master generator.');
    }

    public function generateBulanan(Request $request)
    {
        $validated = $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2024|max:2100',
        ]);

        $members = JadwalGeneratorMember::where('is_active', true)
            ->orderByRaw('COALESCE(sort_order, 999999)')
            ->orderBy('id')
            ->get();

        if ($members->count() < 4) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Master peserta aktif minimal 4 orang.');
        }

        $pool = $members->pluck('source_id')->values()->all();
        $poolCount = count($pool);
        $startDate = Carbon::createFromDate((int) $validated['tahun'], (int) $validated['bulan'], 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $pointer = 0;
        $insertRows = [];
        $hariKerja = 0;

        for ($date = $startDate->copy(); $date->lessThanOrEqualTo($endDate); $date->addDay()) {
            $dayOfWeekIso = $date->dayOfWeekIso;

            if ($dayOfWeekIso === 7) {
                continue; // Minggu libur
            }

            $hariKerja++;
            $assignedToday = [];

            $quota = $dayOfWeekIso === 6
                ? ['Pagi' => 2] // Sabtu
                : ['Pagi' => 2, 'Sore' => 2]; // Senin-Jumat

            foreach ($quota as $shift => $jumlah) {
                if ($poolCount < $jumlah) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', "Jumlah peserta aktif tidak cukup untuk shift {$shift}.");
                }

                $pickedNik = [];
                $attempts = 0;
                $maxAttempts = $poolCount * 3;

                while (count($pickedNik) < $jumlah && $attempts < $maxAttempts) {
                    $nik = $pool[$pointer % $poolCount];
                    $pointer++;
                    $attempts++;

                    if (isset($assignedToday[$nik])) {
                        continue;
                    }

                    $assignedToday[$nik] = true;
                    $pickedNik[] = $nik;
                }

                if (count($pickedNik) < $jumlah) {
                    return redirect()
                        ->back()
                        ->withInput()
                        ->with('error', 'Pool peserta terlalu kecil: masih ada orang yang terpilih 2 kali dalam hari yang sama.');
                }

                foreach ($pickedNik as $nik) {
                    $insertRows[] = [
                        'nik' => $nik,
                        'tanggal_bertugas' => $date->toDateString(),
                        'shift' => $shift,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        DB::transaction(function () use ($validated, $insertRows) {
            JadwalBudayaKerja::whereMonth('tanggal_bertugas', $validated['bulan'])
                ->whereYear('tanggal_bertugas', $validated['tahun'])
                ->delete();

            JadwalBudayaKerja::insert($insertRows);
        });

        return redirect()
            ->route('jadwalbudayakerja.index')
            ->with(
                'success',
                'Generate jadwal bulan ' . $validated['bulan'] . '/' . $validated['tahun'] .
                " berhasil. Hari kerja: {$hariKerja}, total jadwal: " . count($insertRows) . '.'
            );
    }
}
