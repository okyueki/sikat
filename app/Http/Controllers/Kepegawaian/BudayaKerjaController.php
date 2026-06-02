<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\AbsensiAgenda;
use App\Models\AbsensiSholat;
use App\Models\BudayaKerja;
use App\Models\JadwalBudayaKerja;
use App\Models\JadwalPegawai;
use App\Models\Pegawai;
use App\Models\PegawaiDataCache;
use App\Models\PengajuanLibur;
use App\Models\RekapPresensi;
use App\Services\PegawaiDataAggregatorService;
use App\Services\PegawaiAnalyticsService;
use App\Services\IndicatorCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\DataTables;

class BudayaKerjaController extends Controller
{
    /** Maksimal pelanggaran (poin hilang) dalam periode agar masih status Warning; lebih dari ini = Tidak tertib. */
    private const MAX_PELANGGARAN_WARNING = 3;

    /**
     * Hitung total pelanggaran dan status: Tertib (0), Warning (1-3), Tidak tertib (>3), Belum dinilai.
     * Rumus: total_pelanggaran = (jumlah_penilaian * 11) - total_nilai (setiap item tidak sesuai = 1 poin hilang).
     */
    private static function hitungStatusTertib(int $totalPenilaian, float $totalNilai): array
    {
        if ($totalPenilaian <= 0) {
            return ['total_pelanggaran' => 0, 'status_tertib' => 'Belum dinilai'];
        }
        $totalPelanggaran = (int) (($totalPenilaian * 11) - $totalNilai);
        if ($totalPelanggaran <= 0) {
            $status = 'Tertib';
        } elseif ($totalPelanggaran <= self::MAX_PELANGGARAN_WARNING) {
            $status = 'Warning';
        } else {
            $status = 'Tidak tertib';
        }
        return ['total_pelanggaran' => $totalPelanggaran, 'status_tertib' => $status];
    }

    public function create()
    {
        $user = Auth::user();
        $petugas = $user->username; // NIK user yang login
        $today = now()->toDateString(); // Ambil tanggal hari ini
    
        // Ambil NIK pegawai yang sudah memiliki data pada hari ini
        $nikSudahMengisi = BudayaKerja::whereDate('tanggal', $today)
            ->pluck('nik_pegawai'); // Ambil hanya NIK pegawai
    
        // Ambil pegawai yang AKTIF dan belum mengisi data hari ini
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')
            ->whereNotIn('nik', $nikSudahMengisi) // Filter pegawai yang belum mengisi
            ->get();
    
        return view('budayakerja.tambah', compact('pegawai', 'petugas'));
    }

    public function store(Request $request)
    {
        // Ambil data dari request
        $data = $request->all();

        $user = Auth::user();
        $petugas = $user->username; 
        $data['petugas'] = $petugas; 
    
        // Hitung total nilai
        $items = ['sepatu', 'sabuk', 'make_up', 'minyak_wangi', 'jilbab', 'kuku', 'baju', 'celana', 'name_tag', 'perhiasan', 'kaos_kaki'];
        $totalNilai = 0;
    
        foreach ($items as $item) {
            $totalNilai += $request->input($item, 0); // Default ke 0 jika tidak ada
        }
    
        $data['total_nilai'] = $totalNilai; // Menyimpan total nilai ke data
    
        try {
            BudayaKerja::create($data); // Simpan data ke database
            return redirect()->route('budayakerja.index')->with('success', 'Data berhasil disimpan!'); // Redirect ke index dengan pesan sukses
        } catch (\Exception $e) {
            // Tampilkan pesan error jika terjadi masalah
            dd($e->getMessage());
        }
    }

     public function index()
    {
        return view('budayakerja.index');
    }

   public function getData(Request $request)
{
    $query = BudayaKerja::query();

    // Filter berdasarkan rentang tanggal jika disediakan
    if ($request->has('start_date') && $request->has('end_date')) {
        $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
    }

    // Daftar atribut dan labelnya
    $attributeLabels = [
        'sabuk' => 'Sabuk',
        'make_up' => 'Make Up',
        'minyak_wangi' => 'Minyak Wangi',
        'jilbab' => 'Jilbab',
        'kuku' => 'Kuku',
        'baju' => 'Baju',
        'celana' => 'Celana',
        'name_tag' => 'Name Tag',
        'perhiasan' => 'Perhiasan',
        'kaos_kaki' => 'Kaos Kaki'
    ];

    return DataTables::of($query)
        ->addColumn('select', function ($item) {
            return '<input type="checkbox" class="row-select form-check-input" value="' . e($item->id) . '">';
        })
        ->addColumn('keterangan', function ($item) use ($attributeLabels) {
            $itemsWithZero = [];

            // Cek atribut yang bernilai 0 dan gunakan label
            foreach ($attributeLabels as $attr => $label) {
                if ($item->$attr == 0) {
                    $itemsWithZero[] = $label;
                }
            }

            // Gabungkan menjadi string atau "-" jika kosong
            return empty($itemsWithZero) ? '-' : implode(', ', $itemsWithZero);
        })
        ->addColumn('action', function ($item) {
            $viewUrl = route('budayakerja.show', $item->id);
            $editUrl = route('budayakerja.edit', $item->id);
            $deleteUrl = route('budayakerja.destroy', $item->id);

            return '
                <a href="' . $viewUrl . '" class="btn btn-info btn-sm">Detail</a>
                <a href="' . $editUrl . '" class="btn btn-warning btn-sm">Edit</a>
                <form action="' . $deleteUrl . '" method="POST" style="display:inline;">
                    ' . csrf_field() . '
                    ' . method_field('DELETE') . '
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Apakah Anda yakin ingin menghapus?\')">Hapus</button>
                </form>
            ';
        })
        ->rawColumns(['select', 'action'])
        ->make(true);
}
    
    public function destroy($id)
    {
        try {
            // Temukan data berdasarkan ID
            $budayaKerja = BudayaKerja::findOrFail($id);
            
            // Hapus data
            $budayaKerja->delete();
    
            // Redirect ke index dengan pesan sukses
            return redirect()->route('budayakerja.index')->with('success', 'Data berhasil dihapus!');
        } catch (\Exception $e) {
            // Tampilkan pesan error jika terjadi masalah
            return redirect()->route('budayakerja.index')->with('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (! is_array($ids) || empty($ids)) {
            return redirect()->route('budayakerja.index')
                ->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, static fn ($id) => $id > 0);

        if (empty($ids)) {
            return redirect()->route('budayakerja.index')
                ->with('error', 'Data pilihan tidak valid.');
        }

        $deletedCount = BudayaKerja::whereIn('id', $ids)->delete();

        if ($deletedCount > 0) {
            return redirect()->route('budayakerja.index')
                ->with('success', $deletedCount . ' data berhasil dihapus.');
        }

        return redirect()->route('budayakerja.index')
            ->with('error', 'Tidak ada data yang berhasil dihapus.');
    }

    public function show($id)
    {
        // Temukan data berdasarkan ID
        $budayaKerja = BudayaKerja::findOrFail($id);
    
        return view('budayakerja.show', compact('budayaKerja'));
    }

    /**
     * Rekapan nilai budaya kerja per bulan dengan analisa mendalam
     */
    public function rekapan(Request $request)
    {
        // Default bulan dan tahun (bulan ini)
        $bulan = $request->input('bulan', Carbon::now()->format('m'));
        $tahun = $request->input('tahun', Carbon::now()->format('Y'));
        
        // Hitung tanggal awal dan akhir bulan
        $tanggalAwal = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $tanggalAkhir = Carbon::create($tahun, $bulan, 1)->endOfMonth();
        
        // Ambil semua data budaya kerja dalam bulan tersebut
        $dataBudayaKerja = BudayaKerja::whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])->get();
        
        // Daftar item penilaian
        $items = ['sepatu', 'sabuk', 'make_up', 'minyak_wangi', 'jilbab', 'kuku', 'baju', 'celana', 'name_tag', 'perhiasan', 'kaos_kaki'];
        $itemLabels = [
            'sepatu' => 'Sepatu',
            'sabuk' => 'Sabuk',
            'make_up' => 'Make Up',
            'minyak_wangi' => 'Minyak Wangi',
            'jilbab' => 'Jilbab',
            'kuku' => 'Kuku',
            'baju' => 'Baju',
            'celana' => 'Celana',
            'name_tag' => 'Name Tag',
            'perhiasan' => 'Perhiasan',
            'kaos_kaki' => 'Kaos Kaki'
        ];
        
        // Ambil semua pegawai aktif untuk memastikan yang belum dinilai juga muncul
        // Kecualikan pegawai dengan status MIT atau MITRA
        $semuaPegawaiAktif = Pegawai::where('stts_aktif', 'AKTIF')
            ->where(function($query) {
                $query->where('stts_kerja', '!=', 'MIT')
                      ->where('stts_kerja', '!=', 'MITRA');
            })
            ->select('nik', 'nama', 'departemen', 'jbtn as jabatan', 'stts_kerja')
            ->get()
            ->keyBy('nik');
        
        // Hitung rekapan per pegawai
        $rekapanPegawai = [];
        
        // Inisialisasi semua pegawai aktif
        foreach ($semuaPegawaiAktif as $pegawai) {
            $rekapanPegawai[$pegawai->nik] = [
                'nik' => $pegawai->nik,
                'nama' => $pegawai->nama,
                'departemen' => $pegawai->departemen,
                'jabatan' => $pegawai->jabatan,
                'total_penilaian' => 0, // Jumlah kali dinilai dalam 1 bulan
                'total_nilai' => 0, // Total nilai dari semua penilaian
                'rata_rata_nilai' => 0,
                'nilai_tertinggi' => 0,
                'nilai_terendah' => 11,
                'item_tidak_sesuai' => array_fill_keys($items, 0), // Hitung item yang tidak sesuai
            ];
        }
        
        // Proses data penilaian yang ada
        foreach ($dataBudayaKerja as $data) {
            $nik = $data->nik_pegawai;
            
            // Jika NIK tidak ada di daftar pegawai aktif, skip
            if (!isset($rekapanPegawai[$nik])) {
                continue;
            }
            
            $rekapanPegawai[$nik]['total_penilaian']++; // Jumlah kali dinilai
            $rekapanPegawai[$nik]['total_nilai'] += $data->total_nilai; // Total nilai
            
            // Update nilai tertinggi dan terendah
            if ($data->total_nilai > $rekapanPegawai[$nik]['nilai_tertinggi']) {
                $rekapanPegawai[$nik]['nilai_tertinggi'] = $data->total_nilai;
            }
            if ($data->total_nilai < $rekapanPegawai[$nik]['nilai_terendah']) {
                $rekapanPegawai[$nik]['nilai_terendah'] = $data->total_nilai;
            }
            
            // Hitung item yang tidak sesuai (nilai 0)
            foreach ($items as $item) {
                if ($data->$item == 0) {
                    $rekapanPegawai[$nik]['item_tidak_sesuai'][$item]++;
                }
            }
        }
        
        // Hitung rata-rata dan format data
        foreach ($rekapanPegawai as $nik => &$data) {
            $data['rata_rata_nilai'] = $data['total_penilaian'] > 0 
                ? round($data['total_nilai'] / $data['total_penilaian'], 2) 
                : 0;
            
            // Hitung persentase item tidak sesuai
            $data['persentase_item_tidak_sesuai'] = [];
            foreach ($items as $item) {
                $persentase = $data['total_penilaian'] > 0 
                    ? round(($data['item_tidak_sesuai'][$item] / $data['total_penilaian']) * 100, 2)
                    : 0;
                $data['persentase_item_tidak_sesuai'][$item] = $persentase;
            }
        }
        
        // Ranking nilai tertinggi (berdasarkan akumulasi total nilai) - hanya yang pernah dinilai dan rata-rata = 11/11 (perfect)
        $rankingTertinggi = collect($rekapanPegawai)
            ->filter(function($item) {
                return $item['total_penilaian'] > 0 && $item['rata_rata_nilai'] == 11; // Hanya yang pernah dinilai dan rata-rata perfect 11/11
            })
            ->sortByDesc('total_nilai') // Sort berdasarkan akumulasi total nilai tertinggi
            ->take(20)
            ->values();
        
        // Ranking nilai terendah (berdasarkan akumulasi total nilai) - hanya yang pernah dinilai
        $rankingTerendah = collect($rekapanPegawai)
            ->filter(function($item) {
                return $item['total_penilaian'] > 0; // Hanya yang pernah dinilai
            })
            ->sortBy('total_nilai') // Sort berdasarkan akumulasi total nilai terendah
            ->take(20)
            ->values();
        
        // Pegawai yang belum pernah dinilai (total_penilaian = 0)
        $pegawaiBelumDinilai = collect($rekapanPegawai)
            ->filter(function($item) {
                return $item['total_penilaian'] == 0; // Belum pernah dinilai
            })
            ->sortBy('nama')
            ->values();
        
        // Statistik per item (item yang paling sering tidak sesuai)
        $statistikItem = [];
        foreach ($items as $item) {
            $totalTidakSesuai = 0;
            $totalPenilaian = $dataBudayaKerja->count();
            
            foreach ($dataBudayaKerja as $data) {
                if ($data->$item == 0) {
                    $totalTidakSesuai++;
                }
            }
            
            $statistikItem[$item] = [
                'label' => $itemLabels[$item],
                'total_tidak_sesuai' => $totalTidakSesuai,
                'total_penilaian' => $totalPenilaian,
                'persentase_tidak_sesuai' => $totalPenilaian > 0 
                    ? round(($totalTidakSesuai / $totalPenilaian) * 100, 2)
                    : 0,
            ];
        }
        
        // Urutkan statistik item berdasarkan persentase tidak sesuai tertinggi
        $statistikItem = collect($statistikItem)
            ->sortByDesc('persentase_tidak_sesuai')
            ->values();
        
        // Statistik per departemen
        $statistikDepartemen = [];
        foreach ($rekapanPegawai as $data) {
            $dept = $data['departemen'] ?? 'Tidak Diketahui';
            
            if (!isset($statistikDepartemen[$dept])) {
                $statistikDepartemen[$dept] = [
                    'departemen' => $dept,
                    'jumlah_pegawai' => 0,
                    'total_penilaian' => 0,
                    'total_nilai' => 0,
                    'rata_rata_nilai' => 0,
                ];
            }
            
            $statistikDepartemen[$dept]['jumlah_pegawai']++;
            $statistikDepartemen[$dept]['total_penilaian'] += $data['total_penilaian'];
            $statistikDepartemen[$dept]['total_nilai'] += $data['total_nilai'];
        }
        
        // Hitung rata-rata per departemen
        foreach ($statistikDepartemen as &$dept) {
            $dept['rata_rata_nilai'] = $dept['total_penilaian'] > 0
                ? round($dept['total_nilai'] / $dept['total_penilaian'], 2)
                : 0;
        }
        
        $statistikDepartemen = collect($statistikDepartemen)
            ->sortByDesc('rata_rata_nilai')
            ->values();
        
        // Data untuk grafik trend (jika ada data beberapa bulan)
        $trendData = [];
        for ($i = 0; $i < 6; $i++) {
            $bulanTrend = Carbon::create($tahun, $bulan, 1)->subMonths($i);
            $tanggalAwalTrend = $bulanTrend->copy()->startOfMonth();
            $tanggalAkhirTrend = $bulanTrend->copy()->endOfMonth();
            
            $dataTrend = BudayaKerja::whereBetween('tanggal', [$tanggalAwalTrend, $tanggalAkhirTrend])->get();
            $rataRataTrend = $dataTrend->count() > 0 
                ? round($dataTrend->avg('total_nilai'), 2)
                : 0;
            
            $trendData[] = [
                'bulan' => $bulanTrend->format('M Y'),
                'rata_rata' => $rataRataTrend,
                'total_penilaian' => $dataTrend->count(),
            ];
        }
        
        $trendData = array_reverse($trendData); // Urutkan dari bulan tertua ke terbaru

        // Rekapan semua pegawai (untuk tabel lengkap di halaman ini), urut rata-rata descending
        $rekapanPegawai = collect($rekapanPegawai)
            ->sortByDesc('rata_rata_nilai')
            ->values();
        
        return view('budayakerja.rekapan', compact(
            'bulan', 'tahun', 'rankingTertinggi', 'rankingTerendah', 
            'statistikItem', 'statistikDepartemen', 'trendData', 'itemLabels',
            'pegawaiBelumDinilai', 'rekapanPegawai'
        ));
    }

    /**
     * Rekap semua pegawai dengan filter rentang tanggal (bukan per bulan).
     * Menunjukkan:
     * - berapa pegawai sudah dinilai / belum dinilai
     * - berapa pegawai tertib / tidak tertib (berdasarkan rata-rata nilai)
     */
    public function rekapSemuaPegawai(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $departemen = $request->input('departemen');

        if (! $startDate || ! $endDate) {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $startDate = $start->toDateString();
            $endDate = $end->toDateString();
        } else {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $startDate = $start->toDateString();
            $endDate = $end->toDateString();
        }

        // Gunakan service untuk agregasi data
        $aggregatedData = PegawaiDataAggregatorService::aggregate($startDate, $endDate, null, $departemen);
        $dataWithScores = IndicatorCalculatorService::calculateScoresFromAggregated($aggregatedData);

        // Statistik ringkas
        $jumlahPegawaiTotal = $dataWithScores->count();
        $jumlahPegawaiDinilai = $dataWithScores->filter(fn($row) => ($row['budaya']['total_penilaian'] ?? 0) > 0)->count();
        $jumlahPegawaiBelumDinilai = $jumlahPegawaiTotal - $jumlahPegawaiDinilai;
        $jumlahPegawaiTertib = $dataWithScores->where('status_keterlibatan', 'aktif')->count();
        $jumlahPegawaiWarning = $dataWithScores->where('status_keterlibatan', 'warning')->count();
        $jumlahPegawaiTidakTertib = $dataWithScores->where('status_keterlibatan', 'tidak_aktif')->count();

        // Daftar departemen untuk dropdown
        $departemenList = $dataWithScores->pluck('departemen')->unique()->filter()->sort()->values();

        // Sort by nama
        $dataWithScores = $dataWithScores->sortBy('nama')->values();

        return view('budayakerja.rekap_semua_pegawai', compact(
            'startDate',
            'endDate',
            'departemen',
            'departemenList',
            'dataWithScores',
            'jumlahPegawaiTotal',
            'jumlahPegawaiDinilai',
            'jumlahPegawaiBelumDinilai',
            'jumlahPegawaiTertib',
            'jumlahPegawaiWarning',
            'jumlahPegawaiTidakTertib'
        ));
    }

    /**
     * Rekap Terpadu - Menggabungkan semua data kehadiran:
     * - Presensi Harian
     * - Absensi Sholat
     * - Budaya Kerja
     * - Absensi Agenda
     *
     * Menggunakan PegawaiDataAggregatorService untuk agregasi data.
     */
    public function rekapTerpadu(Request $request)
    {
        $bulan = $request->input('bulan', Carbon::now()->month);
        $tahun = $request->input('tahun', Carbon::now()->year);
        $departemen = $request->input('departemen');

        // Hitung periode
        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();

        // Agregasi data dari semua sumber
        $aggregatedData = PegawaiDataAggregatorService::aggregate(
            $startDate,
            $endDate,
            null,
            $departemen
        );

        // Hitung skor untuk setiap pegawai
        $dataWithScores = IndicatorCalculatorService::calculateScoresFromAggregated($aggregatedData);

        // Statistik summary
        $summary = [
            'total_pegawai' => $dataWithScores->count(),
            'avg_presensi_rate' => round($dataWithScores->avg('presensi.rate_kehadiran'), 1),
            'avg_sholat_rate' => round($dataWithScores->avg('sholat.rate'), 1),
            'avg_budaya_rate' => round($dataWithScores->avg('budaya.rate'), 1),
            'avg_agenda_rate' => round($dataWithScores->avg('agenda.rate_kehadiran'), 1),
            'avg_overall_score' => round($dataWithScores->avg('overall_score'), 2),
            'aktif_count' => $dataWithScores->where('status_keterlibatan', 'aktif')->count(),
            'warning_count' => $dataWithScores->where('status_keterlibatan', 'warning')->count(),
            'tidak_aktif_count' => $dataWithScores->where('status_keterlibatan', 'tidak_aktif')->count(),
        ];

        // Top & Bottom performers
        $topPerformers = $dataWithScores->sortByDesc('overall_score')->take(5)->values();
        $bottomPerformers = $dataWithScores->sortBy('overall_score')->take(5)->values();

        // Statistik sholat per jenis
        $sholatStats = [
            'subuh' => round($dataWithScores->avg('sholat.by_jenis.subuh.persen'), 1),
            'dzuhur' => round($dataWithScores->avg('sholat.by_jenis.dzuhur.persen'), 1),
            'ashar' => round($dataWithScores->avg('sholat.by_jenis.ashar.persen'), 1),
            'maghrib' => round($dataWithScores->avg('sholat.by_jenis.maghrib.persen'), 1),
            'isya' => round($dataWithScores->avg('sholat.by_jenis.isya.persen'), 1),
        ];

        // Daftar departemen untuk filter
        $departemenList = $dataWithScores->pluck('departemen')->unique()->filter()->sort()->values();

        // Sort by nama
        $dataWithScores = $dataWithScores->sortBy('nama')->values();

        return view('budayakerja.rekap_terpadu', compact(
            'bulan',
            'tahun',
            'startDate',
            'endDate',
            'departemen',
            'departemenList',
            'dataWithScores',
            'summary',
            'topPerformers',
            'bottomPerformers',
            'sholatStats'
        ));
    }

    /**
     * Detail Rekap Terpadu untuk satu pegawai
     */
    public function rekapTerpaduDetail(Request $request, string $nik)
    {
        $bulan = $request->input('bulan', Carbon::now()->month);
        $tahun = $request->input('tahun', Carbon::now()->year);

        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();

        // Ambil data single pegawai
        $pegawaiData = PegawaiDataAggregatorService::aggregateSingle($nik, $startDate, $endDate);

        if (!$pegawaiData) {
            abort(404, 'Pegawai tidak ditemukan');
        }

        // Hitung skor
        $scoreData = IndicatorCalculatorService::calculateScore($pegawaiData);

        // Ambil trend jika ada data sebelumnya
        $trend = PegawaiAnalyticsService::getTrend($nik, 6);

        return view('budayakerja.rekap_terpadu_detail', compact(
            'bulan',
            'tahun',
            'startDate',
            'endDate',
            'pegawaiData',
            'scoreData',
            'trend'
        ));
    }

    /**
     * Save rekap terpadu ke cache untuk akses cepat nanti
     */
    public function saveRekapTerpadu(Request $request)
    {
        $bulan = $request->input('bulan', Carbon::now()->month);
        $tahun = $request->input('tahun', Carbon::now()->year);

        $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
        $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();

        // Agregasi data
        $aggregatedData = PegawaiDataAggregatorService::aggregate($startDate, $endDate);
        $dataWithScores = IndicatorCalculatorService::calculateScoresFromAggregated($aggregatedData);

        // Prepare for cache
        $cacheData = IndicatorCalculatorService::prepareCacheData($dataWithScores, Auth::id());

        // Delete old data for this period
        PegawaiDataCache::where('periode_type', 'bulanan')
            ->where('periode_start', $startDate)
            ->delete();

        // Insert new data
        foreach ($cacheData as $data) {
            PegawaiDataCache::create($data);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data rekap berhasil disimpan ke cache',
            'data' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'total_pegawai' => count($cacheData),
            ],
        ]);
    }

    /**
     * Export rekap semua pegawai ke Excel (CSV) untuk periode yang dipilih.
     */
    public function exportRekapPegawai(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (! $startDate || ! $endDate) {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $startDate = $start->toDateString();
            $endDate = $end->toDateString();
        } else {
            $startDate = Carbon::parse($startDate)->toDateString();
            $endDate = Carbon::parse($endDate)->toDateString();
        }
        $departemen = $request->input('departemen');

        $queryPegawai = Pegawai::where('stts_aktif', 'AKTIF')
            ->where(function ($query) {
                $query->where('stts_kerja', '!=', 'MIT')
                    ->where('stts_kerja', '!=', 'MITRA');
            })
            ->when($departemen, function ($q) use ($departemen) {
                $q->where('departemen', $departemen);
            });
        $semuaPegawaiAktif = (clone $queryPegawai)
            ->select('id', 'nik', 'nama', 'departemen', 'jbtn as jabatan', 'stts_kerja')
            ->get()
            ->keyBy('nik');

        $rekapanPegawai = [];
        foreach ($semuaPegawaiAktif as $pegawai) {
            $rekapanPegawai[$pegawai->nik] = [
                'pegawai_id' => $pegawai->id,
                'nik' => $pegawai->nik,
                'nama' => $pegawai->nama,
                'departemen' => $pegawai->departemen,
                'jabatan' => $pegawai->jabatan,
                'total_penilaian' => 0,
                'total_nilai' => 0,
                'rata_rata_nilai' => 0,
                'nilai_tertinggi' => 0,
                'nilai_terendah' => 11,
                'status_tertib' => 'Belum dinilai',
                'jumlah_hadir' => 0,
                'jumlah_tepat_waktu' => 0,
                'jumlah_terlambat' => 0,
                'jumlah_diundang_agenda' => 0,
                'jumlah_hadir_agenda' => 0,
                'jumlah_ijin_agenda' => 0,
                'jumlah_cuti_agenda' => 0,
                'jumlah_tidak_hadir_agenda' => 0,
            ];
        }

        $dataBudayaKerja = BudayaKerja::whereBetween('tanggal', [$startDate, $endDate])->get();
        foreach ($dataBudayaKerja as $data) {
            $nik = $data->nik_pegawai;
            if (! isset($rekapanPegawai[$nik])) {
                continue;
            }
            $rekapanPegawai[$nik]['total_penilaian']++;
            $rekapanPegawai[$nik]['total_nilai'] += $data->total_nilai;
            if ($data->total_nilai > $rekapanPegawai[$nik]['nilai_tertinggi']) {
                $rekapanPegawai[$nik]['nilai_tertinggi'] = $data->total_nilai;
            }
            if ($data->total_nilai < $rekapanPegawai[$nik]['nilai_terendah']) {
                $rekapanPegawai[$nik]['nilai_terendah'] = $data->total_nilai;
            }
        }

        $pegawaiIds = collect($rekapanPegawai)->pluck('pegawai_id')->unique()->filter()->values()->toArray();
        $presensiData = [];
        if (! empty($pegawaiIds)) {
            $presensiData = RekapPresensi::whereIn('id', $pegawaiIds)
                ->whereDate('jam_datang', '>=', $startDate)
                ->whereDate('jam_datang', '<=', $endDate)
                ->get()
                ->groupBy('id');
        }
        $statusTepatWaktu = ['Tepat Waktu', 'Tepat Waktu & PSW'];
        foreach ($rekapanPegawai as $nik => &$row) {
            $rows = $presensiData->get($row['pegawai_id'] ?? 0, collect());
            $row['jumlah_hadir'] = $rows->count();
            $row['jumlah_tepat_waktu'] = $rows->whereIn('status', $statusTepatWaktu)->count();
            $row['jumlah_terlambat'] = $row['jumlah_hadir'] - $row['jumlah_tepat_waktu'];
        }
        unset($row);

        $listNikExport = array_keys($rekapanPegawai);
        $agendaIdsInRangeExport = Agenda::whereDate('mulai', '>=', $startDate)
            ->whereDate('mulai', '<=', $endDate)
            ->pluck('id')
            ->toArray();
        $diundangPerNikE = array_fill_keys($listNikExport, 0);
        $hadirAgendaPerNikE = array_fill_keys($listNikExport, 0);
        $ijinAgendaPerNikE = array_fill_keys($listNikExport, 0);
        $cutiAgendaPerNikE = array_fill_keys($listNikExport, 0);
        $tidakHadirAgendaPerNikE = array_fill_keys($listNikExport, 0);
        if (! empty($agendaIdsInRangeExport)) {
            $agendasExport = Agenda::whereIn('id', $agendaIdsInRangeExport)->get(['id', 'yang_terundang']);
            foreach ($agendasExport as $agenda) {
                $terundang = $agenda->yang_terundang;
                if (is_string($terundang)) {
                    $terundang = json_decode($terundang, true) ?? [];
                }
                $isAll = is_array($terundang) && in_array('all', $terundang);
                foreach ($listNikExport as $nik) {
                    if ($isAll || (is_array($terundang) && in_array($nik, $terundang))) {
                        $diundangPerNikE[$nik] = ($diundangPerNikE[$nik] ?? 0) + 1;
                    }
                }
            }
            $absensiAgendaRowsExport = AbsensiAgenda::whereIn('agenda_id', $agendaIdsInRangeExport)
                ->whereIn('nik', $listNikExport)
                ->get(['nik', 'status_kehadiran']);
            foreach ($absensiAgendaRowsExport as $r) {
                $nik = $r->nik;
                if (! isset($hadirAgendaPerNikE[$nik])) {
                    continue;
                }
                switch ($r->status_kehadiran) {
                    case 'hadir': $hadirAgendaPerNikE[$nik]++; break;
                    case 'ijin': $ijinAgendaPerNikE[$nik]++; break;
                    case 'cuti': $cutiAgendaPerNikE[$nik]++; break;
                    case 'tidak_hadir': $tidakHadirAgendaPerNikE[$nik]++; break;
                }
            }
        }
        foreach ($rekapanPegawai as $nik => &$row) {
            $row['jumlah_diundang_agenda'] = $diundangPerNikE[$nik] ?? 0;
            $row['jumlah_hadir_agenda'] = $hadirAgendaPerNikE[$nik] ?? 0;
            $row['jumlah_ijin_agenda'] = $ijinAgendaPerNikE[$nik] ?? 0;
            $row['jumlah_cuti_agenda'] = $cutiAgendaPerNikE[$nik] ?? 0;
            $row['jumlah_tidak_hadir_agenda'] = $tidakHadirAgendaPerNikE[$nik] ?? 0;
        }
        unset($row);

        foreach ($rekapanPegawai as $nik => &$row) {
            if ($row['total_penilaian'] > 0) {
                $row['rata_rata_nilai'] = round($row['total_nilai'] / $row['total_penilaian'], 2);
                $hitung = self::hitungStatusTertib($row['total_penilaian'], $row['total_nilai']);
                $row['total_pelanggaran'] = $hitung['total_pelanggaran'];
                $row['status_tertib'] = $hitung['status_tertib'];
            } else {
                $row['rata_rata_nilai'] = 0;
                $row['total_pelanggaran'] = 0;
                $row['status_tertib'] = 'Belum dinilai';
                $row['nilai_tertinggi'] = 0;
                $row['nilai_terendah'] = 0;
            }
        }
        unset($row);

        $rekapanPegawai = collect($rekapanPegawai)->sortBy('nama')->values();

        $filename = 'rekap_budaya_kerja_' . $startDate . '_' . $endDate . '.csv';

        return new StreamedResponse(function () use ($rekapanPegawai) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 agar Excel membuka dengan benar
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'No',
                'NIK',
                'Nama',
                'Departemen',
                'Jabatan',
                'Jumlah Penilaian',
                'Total Nilai',
                'Total Pelanggaran',
                'Rata-rata',
                'Nilai Tertinggi',
                'Nilai Terendah',
                'Status',
                'Hadir',
                'Tepat Waktu',
                'Terlambat',
                'Diundang Agenda',
                'Hadir Agenda',
                'Ijin Agenda',
                'Cuti Agenda',
                'Tidak Hadir Agenda',
            ], ';');

            foreach ($rekapanPegawai as $index => $row) {
                fputcsv($handle, [
                    $index + 1,
                    $row['nik'],
                    $row['nama'],
                    $row['departemen'] ?? '',
                    $row['jabatan'] ?? '',
                    $row['total_penilaian'],
                    $row['total_nilai'],
                    $row['total_pelanggaran'] ?? 0,
                    $row['rata_rata_nilai'],
                    $row['nilai_tertinggi'],
                    $row['nilai_terendah'],
                    $row['status_tertib'],
                    $row['jumlah_hadir'] ?? 0,
                    $row['jumlah_tepat_waktu'] ?? 0,
                    $row['jumlah_terlambat'] ?? 0,
                    $row['jumlah_diundang_agenda'] ?? 0,
                    $row['jumlah_hadir_agenda'] ?? 0,
                    $row['jumlah_ijin_agenda'] ?? 0,
                    $row['jumlah_cuti_agenda'] ?? 0,
                    $row['jumlah_tidak_hadir_agenda'] ?? 0,
                ], ';');
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Breakdown penilaian per pegawai dalam rentang tanggal (dipanggil saat klik nama di rekap semua pegawai).
     */
    public function rekapPegawaiDetail(Request $request, string $nik)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $departemen = $request->input('departemen');

        if (! $startDate || ! $endDate) {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $startDate = $start->toDateString();
            $endDate = $end->toDateString();
        }

        $pegawai = Pegawai::where('nik', $nik)->first();
        if (! $pegawai) {
            abort(404, 'Pegawai tidak ditemukan.');
        }

        $listPenilaian = BudayaKerja::with('atasan')
            ->where('nik_pegawai', $nik)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal')
            ->orderBy('jam')
            ->get();

        $items = ['sepatu', 'sabuk', 'make_up', 'minyak_wangi', 'jilbab', 'kuku', 'baju', 'celana', 'name_tag', 'perhiasan', 'kaos_kaki'];
        $itemLabels = [
            'sepatu' => 'Sepatu',
            'sabuk' => 'Sabuk',
            'make_up' => 'Make Up',
            'minyak_wangi' => 'Minyak Wangi',
            'jilbab' => 'Jilbab',
            'kuku' => 'Kuku',
            'baju' => 'Baju',
            'celana' => 'Celana',
            'name_tag' => 'Name Tag',
            'perhiasan' => 'Perhiasan',
            'kaos_kaki' => 'Kaos Kaki',
        ];

        $totalPenilaian = $listPenilaian->count();
        $totalNilai = $listPenilaian->sum('total_nilai');
        $rataRata = $totalPenilaian > 0 ? round($totalNilai / $totalPenilaian, 2) : 0;
        $nilaiTertinggi = $totalPenilaian > 0 ? $listPenilaian->max('total_nilai') : 0;
        $nilaiTerendah = $totalPenilaian > 0 ? $listPenilaian->min('total_nilai') : 0;
        $hitung = self::hitungStatusTertib($totalPenilaian, $totalNilai);
        $totalPelanggaran = $hitung['total_pelanggaran'];
        $statusTertib = $hitung['status_tertib'];

        // Data kehadiran (presensi) pegawai dalam periode yang sama
        $listPresensi = RekapPresensi::where('id', $pegawai->id)
            ->whereDate('jam_datang', '>=', $startDate)
            ->whereDate('jam_datang', '<=', $endDate)
            ->orderBy('jam_datang')
            ->get();
        $statusTepatWaktu = ['Tepat Waktu', 'Tepat Waktu & PSW'];
        $jumlahHadir = $listPresensi->count();
        $jumlahTepatWaktu = $listPresensi->whereIn('status', $statusTepatWaktu)->count();
        $jumlahTerlambat = $jumlahHadir - $jumlahTepatWaktu;

        // Data jadwal shift dari jadwal_pegawai (h1-h31 untuk setiap tanggal)
        $tahunJadwal = Carbon::parse($startDate)->year;
        $bulanJadwal = Carbon::parse($startDate)->month;
        $jadwalPegawai = JadwalPegawai::where('id', $pegawai->id)
            ->where('tahun', $tahunJadwal)
            ->where('bulan', $bulanJadwal)
            ->first();
        $jadwalShifts = [];
        if ($jadwalPegawai) {
            for ($i = 1; $i <= 31; $i++) {
                $hariKey = 'h' . $i;
                if (isset($jadwalPegawai->$hariKey) && !empty($jadwalPegawai->$hariKey)) {
                    $tanggalStr = sprintf('%04d-%02d-%02d', $tahunJadwal, $bulanJadwal, $i);
                    $jadwalShifts[$tanggalStr] = $jadwalPegawai->$hariKey;
                }
            }
        }

        return view('budayakerja.rekap_pegawai_detail', compact(
            'pegawai',
            'listPenilaian',
            'listPresensi',
            'jumlahHadir',
            'jumlahTepatWaktu',
            'jumlahTerlambat',
            'itemLabels',
            'items',
            'startDate',
            'endDate',
            'departemen',
            'totalPenilaian',
            'totalNilai',
            'totalPelanggaran',
            'rataRata',
            'nilaiTertinggi',
            'nilaiTerendah',
            'statusTertib',
            'jadwalShifts'
        ));
    }

    /**
     * Rekap kinerja petugas penilai:
     * - berapa kali terjadwal
     * - berapa kali benar-benar menilai
     * - detail siapa saja yang dinilai oleh petugas terpilih
     */
    public function rekapPetugas(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $selectedPetugas = (string) $request->input('petugas', '');

        if (! $startDate || ! $endDate) {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $startDate = $start->toDateString();
            $endDate = $end->toDateString();
        } else {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $startDate = $start->toDateString();
            $endDate = $end->toDateString();
        }

        // Daftar petugas yang dijadwalkan pada rentang tanggal.
        $jadwalByPetugas = JadwalBudayaKerja::query()
            ->whereBetween('tanggal_bertugas', [$startDate, $endDate])
            ->select('nik', DB::raw('COUNT(*) as total_jadwal'))
            ->groupBy('nik')
            ->get()
            ->keyBy('nik');

        $petugasNiks = $jadwalByPetugas->keys()->values();
        $pegawaiMap = Pegawai::whereIn('nik', $petugasNiks)
            ->select('nik', 'nama', 'departemen', 'jbtn')
            ->get()
            ->keyBy('nik');

        // Jumlah aktivitas menilai dari tabel budaya_kerja.
        $dinilaiByPetugas = BudayaKerja::query()
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->whereIn('petugas', $petugasNiks)
            ->select('petugas', DB::raw('COUNT(*) as total_menilai'), DB::raw('COUNT(DISTINCT nik_pegawai) as pegawai_unik'))
            ->groupBy('petugas')
            ->get()
            ->keyBy('petugas');

        $rekapPetugas = $petugasNiks->map(function ($nik) use ($jadwalByPetugas, $dinilaiByPetugas, $pegawaiMap) {
            $jadwal = (int) optional($jadwalByPetugas->get($nik))->total_jadwal;
            $menilai = (int) optional($dinilaiByPetugas->get($nik))->total_menilai;
            $unik = (int) optional($dinilaiByPetugas->get($nik))->pegawai_unik;
            $persen = $jadwal > 0 ? round(($menilai / $jadwal) * 100, 1) : 0;
            $pegawai = $pegawaiMap->get($nik);

            return [
                'nik' => $nik,
                'nama' => $pegawai->nama ?? $nik,
                'departemen' => $pegawai->departemen ?? '-',
                'jabatan' => $pegawai->jbtn ?? '-',
                'total_jadwal' => $jadwal,
                'total_menilai' => $menilai,
                'pegawai_unik' => $unik,
                'persentase_realisasi' => $persen,
                'selisih' => $menilai - $jadwal,
            ];
        })->sortByDesc('total_menilai')->values();

        if ($selectedPetugas === '' && $rekapPetugas->isNotEmpty()) {
            $selectedPetugas = (string) $rekapPetugas->first()['nik'];
        }

        $detailPenilaian = collect();
        if ($selectedPetugas !== '') {
            $detailPenilaian = BudayaKerja::query()
                ->whereRaw('TRIM(petugas) = ?', [$selectedPetugas])
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->orderByDesc('tanggal')
                ->orderByDesc('jam')
                ->get([
                    'id',
                    'tanggal',
                    'jam',
                    'nik_pegawai',
                    'nama_pegawai',
                    'departemen',
                    'shift',
                    'total_nilai',
                ]);
        }

        $detailPegawaiDinilai = $detailPenilaian
            ->groupBy('nik_pegawai')
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'nik_pegawai' => $first->nik_pegawai,
                    'nama_pegawai' => $first->nama_pegawai,
                    'departemen' => $first->departemen,
                    'jumlah_dinilai' => $rows->count(),
                ];
            })
            ->values()
            ->sortByDesc('jumlah_dinilai')
            ->values();

        $summary = [
            'total_petugas_terjadwal' => $rekapPetugas->count(),
            'total_jadwal' => $rekapPetugas->sum('total_jadwal'),
            'total_menilai' => $rekapPetugas->sum('total_menilai'),
            'total_pegawai_dinilai' => $detailPenilaian->pluck('nik_pegawai')->unique()->count(),
        ];

        return view('budayakerja.rekap_petugas', compact(
            'startDate',
            'endDate',
            'selectedPetugas',
            'rekapPetugas',
            'detailPenilaian',
            'detailPegawaiDinilai',
            'summary'
        ));
    }

    /**
     * Ringkasan Kehadiran Semua Pegawai
     * Halaman terpisah untuk menampilkan data presensi semua pegawai
     * dengan filter per departemen.
     */
    public function ringkasanKehadiran(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $departemen = $request->input('departemen');

        if (! $startDate || ! $endDate) {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $startDate = $start->toDateString();
            $endDate = $end->toDateString();
        } else {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $startDate = $start->toDateString();
            $endDate = $end->toDateString();
        }

        // Ambil semua pegawai
        $pegawaiQuery = Pegawai::query();
        if ($departemen) {
            $pegawaiQuery->where('departemen', $departemen);
        }
        $pegawaiList = $pegawaiQuery->get()->keyBy('id');

        // Ambil jadwal pegawai untuk setiap bulan dalam range
        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);
        $allJadwalPegawai = [];

        $current = $startCarbon->copy()->startOfMonth();
        while ($current->lte($endCarbon)) {
            $tahun = $current->year;
            $bulan = $current->month;

            $jadwalBulan = JadwalPegawai::whereIn('id', $pegawaiList->keys())
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->get()
                ->keyBy('id');

            foreach ($jadwalBulan as $j) {
                $allJadwalPegawai[$bulan][$j->id] = $j;
            }
            $current->addMonth();
        }

        // Ambil semua presensi dalam periode
        $allPresensi = RekapPresensi::whereIn('id', $pegawaiList->keys())
            ->whereDate('jam_datang', '>=', $startDate)
            ->whereDate('jam_datang', '<=', $endDate)
            ->orderBy('jam_datang')
            ->get();

        // Ambil jam masuk standar per shift (cache untuk performa)
        $jamMasukList = Cache::remember('api.jam_masuk_list', 3600, fn () => \App\Models\JamMasuk::all()->keyBy('shift'));

        // Gabungkan data presensi dengan info pegawai dan jadwal
        $listPresensi = $allPresensi->map(function ($presensi) use ($pegawaiList, $allJadwalPegawai, $jamMasukList) {
            $pegawai = $pegawaiList->get($presensi->id);
            $tanggal = Carbon::parse($presensi->jam_datang);
            $hariKey = 'h' . $tanggal->day;
            $bulan = $tanggal->month;

            // Ambil jadwal untuk bulan yang sesuai
            $jadwalPegawai = $allJadwalPegawai[$bulan][$presensi->id] ?? null;
            $shift = $jadwalPegawai ? ($jadwalPegawai->$hariKey ?? null) : null;

            // Recalculate status berdasarkan jadwal_pegawai + jam_masuk + set_keterlambatan
            $jamMasukStd = null;
            $jamPulangStd = null;
            $statusRecalc = 'Tepat Waktu';
            $keterlambatanRecalc = '00:00:00';

            if ($shift && $presensi->jam_datang) {
                // Normalisasi shift name untuk lookup (Pagi, Pagi2, Siang, Malam, Midle Pagi1, dll)
                $jamMasukData = $jamMasukList->get(ucfirst(strtolower($shift))) ?? $jamMasukList->get($shift);
                if ($jamMasukData && $jamMasukData->jam_masuk) {
                    $jamDatang = Carbon::parse($presensi->jam_datang, 'Asia/Jakarta');
                    // Set jam_masuk dengan tanggal yang SAMA dengan jam_datang
                    $jamMasukStd = $jamDatang->copy()->setTimeFromTimeString($jamMasukData->jam_masuk);
                    $jamPulangStd = $jamMasukData->jam_pulang ? $jamDatang->copy()->setTimeFromTimeString($jamMasukData->jam_pulang) : null;

                    // Perbandingan: >= 1 menit = Terlambat, < 1 menit = Tepat Waktu
                    if ($jamDatang->greaterThan($jamMasukStd)) {
                        $selisihDetik = $jamDatang->diffInSeconds($jamMasukStd);
                        if ($selisihDetik >= 60) {
                            $statusRecalc = 'Terlambat';
                            $keterlambatanRecalc = $jamDatang->diff($jamMasukStd)->format('%H:%I:%S');
                        }
                        // < 1 menit tetap Tepat Waktu, $keterlambatanRecalc tetap 00:00:00
                    }
                }
            }

            return [
                'nama' => $pegawai->nama ?? '-',
                'nik' => $pegawai->nik ?? '-',
                'departemen' => $pegawai->departemen ?? '-',
                'tanggal' => $tanggal->format('d-m-Y'),
                'tanggal_key' => $tanggal->format('Y-m-d'),
                'shift' => $shift,
                'jam_masuk_std' => $jamMasukStd ? $jamMasukStd->format('H:i') : null,
                'jam_pulang_std' => $jamPulangStd ? $jamPulangStd->format('H:i') : null,
                'jam_datang' => $presensi->jam_datang ? $tanggal->format('H:i') : '-',
                'jam_pulang' => $presensi->jam_pulang ? Carbon::parse($presensi->jam_pulang)->format('H:i') : '-',
                // Original (dari DB) vs Recalculated
                'status_original' => $presensi->status ?? '-',
                'status' => $statusRecalc,
                'keterlambatan_original' => $presensi->keterlambatan ?? '-',
                'keterlambatan' => $keterlambatanRecalc,
            ];
        })->values();

        // Statistik ringkas (berdasarkan status recalculated)
        $jumlahPegawaiTotal = $pegawaiList->count();
        $totalHadir = $listPresensi->count();
        $statusTepatWaktu = ['Tepat Waktu', 'Tepat Waktu & PSW'];
        $totalTepatWaktu = $listPresensi->whereIn('status', $statusTepatWaktu)->count();
        $totalTerlambat = $totalHadir - $totalTepatWaktu;

        // Daftar departemen untuk dropdown
        $departemenList = $pegawaiList->pluck('departemen')->unique()->filter()->sort()->values();

        return view('presensi.ringkasan_kehadiran', compact(
            'startDate',
            'endDate',
            'departemen',
            'departemenList',
            'listPresensi',
            'jumlahPegawaiTotal',
            'totalHadir',
            'totalTepatWaktu',
            'totalTerlambat'
        ));
    }

    /**
     * Rekap Keterlambatan Per Pegawai
     * Menampilkan NIK, Nama, Departemen, jumlah terlambat, tepat waktu, total kehadiran, dan durasi keterlambatan
     * OPTIMIZED: menggunakan query langsung tanpa loading semua data ke memory
     */
    public function rekapKeterlambatan(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $departemen = $request->input('departemen');

        if (! $startDate || ! $endDate) {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $startDate = $start->toDateString();
            $endDate = $end->toDateString();
        }

        // Ambil jam masuk standar per shift
        $jamMasukList = \App\Models\JamMasuk::all()->keyBy('shift');
        $jamMasukArray = $jamMasukList->toArray();

        // Ambil pegawai AKTIF saja (hanya kolom yang diperlukan)
        $pegawaiQuery = Pegawai::where('stts_aktif', 'AKTIF')
            ->select('id', 'nik', 'nama', 'departemen');
        if ($departemen) {
            $pegawaiQuery->where('departemen', $departemen);
        }
        $pegawaiList = $pegawaiQuery->get()->keyBy('id');
        $pegawaiIds = $pegawaiList->keys()->toArray();

        // Ambil jadwal pegawai untuk setiap bulan dalam range
        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);
        $allJadwalPegawai = [];

        $current = $startCarbon->copy()->startOfMonth();
        while ($current->lte($endCarbon)) {
            $tahun = $current->year;
            $bulan = $current->month;

            $jadwalBulan = JadwalPegawai::whereIn('id', $pegawaiIds)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->get();

            foreach ($jadwalBulan as $j) {
                $allJadwalPegawai[$j->id][$bulan] = $j;
            }
            $current->addMonth();
        }

        // Ambil data cuti (PengajuanLibur) dalam periode - semua status
        $listNik = $pegawaiList->pluck('nik')->toArray();
        $cutiData = PengajuanLibur::whereIn('nik', $listNik)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('tanggal_awal', [$startDate, $endDate])
                    ->orWhereBetween('tanggal_akhir', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('tanggal_awal', '<=', $startDate)
                          ->where('tanggal_akhir', '>=', $endDate);
                    });
            })
            ->get()
            ->groupBy('nik');

        // Ambil semua presensi dalam periode (chunk untuk hemat memory)
        $rekapPerPegawai = [];
        $nikToIdMap = [];
        foreach ($pegawaiList as $pegawai) {
            $rekapPerPegawai[$pegawai->id] = [
                'nik' => $pegawai->nik,
                'nama' => $pegawai->nama,
                'departemen' => $pegawai->departemen,
                'jml_terlambat' => 0,
                'jml_tepat_waktu' => 0,
                'jumlah_kehadiran' => 0,
                'total_detik_terlambat' => 0,
                'jumlah_cuti' => 0,
                'hari_cuti' => [],
            ];
            $nikToIdMap[$pegawai->nik] = $pegawai->id;
        }

        // Hitung jumlah cuti per pegawai
        foreach ($cutiData as $nik => $cutiList) {
            $id = $nikToIdMap[$nik] ?? null;
            if ($id && isset($rekapPerPegawai[$id])) {
                $rekapPerPegawai[$id]['jumlah_cuti'] = $cutiList->count();
                foreach ($cutiList as $cuti) {
                    $tanggalAwal = Carbon::parse($cuti->tanggal_awal)->copy();
                    $tanggalAkhir = Carbon::parse($cuti->tanggal_akhir);
                    while ($tanggalAwal->lte($tanggalAkhir)) {
                        if ($tanggalAwal->between($startCarbon, $endCarbon)) {
                            $rekapPerPegawai[$id]['hari_cuti'][] = $tanggalAwal->format('Y-m-d');
                        }
                        $tanggalAwal->addDay();
                    }
                }
            }
        }

        // Proses presensi per chunk
        RekapPresensi::whereIn('id', $pegawaiIds)
            ->whereDate('jam_datang', '>=', $startDate)
            ->whereDate('jam_datang', '<=', $endDate)
            ->orderBy('jam_datang')
            ->chunk(500, function ($chunkPresensi) use (&$rekapPerPegawai, $allJadwalPegawai, $jamMasukArray) {
                foreach ($chunkPresensi as $presensi) {
                    if (!isset($rekapPerPegawai[$presensi->id])) continue;
                    if (!$presensi->jam_datang) continue;

                    $tanggal = Carbon::parse($presensi->jam_datang);
                    $hariKey = 'h' . $tanggal->day;
                    $bulan = $tanggal->month;
                    $tahun = $tanggal->year;

                    // Ambil jadwal untuk pegawai ini di bulan tsb
                    $jadwalPegawai = $allJadwalPegawai[$presensi->id][$bulan] ?? null;
                    $shift = $jadwalPegawai ? ($jadwalPegawai->$hariKey ?? null) : null;

                    if (!$shift) continue;

                    // Cek jam_masuk
                    $jamMasukData = $jamMasukArray[$shift] ?? null;
                    if (!$jamMasukData || empty($jamMasukData['jam_masuk'])) continue;

                    $jamDatang = Carbon::parse($presensi->jam_datang, 'Asia/Jakarta');
                    $jamMasukStd = $jamDatang->copy()->setTimeFromTimeString($jamMasukData['jam_masuk']);

                    $rekapPerPegawai[$presensi->id]['jumlah_kehadiran']++;

                    if ($jamDatang->greaterThan($jamMasukStd)) {
                        $rekapPerPegawai[$presensi->id]['jml_terlambat']++;
                        $rekapPerPegawai[$presensi->id]['total_detik_terlambat'] += $jamDatang->diffInSeconds($jamMasukStd);
                    } else {
                        $rekapPerPegawai[$presensi->id]['jml_tepat_waktu']++;
                    }
                }
            });

        // Format durasi
        foreach ($rekapPerPegawai as $id => &$data) {
            $totalMenit = floor($data['total_detik_terlambat'] / 60);
            $totalJam = floor($totalMenit / 60);
            $sisaMenit = $totalMenit % 60;
            $data['durasi_terlambat'] = $data['jml_terlambat'] > 0 ? $totalJam . 'j ' . $sisaMenit . 'm' : '-';
        }
        unset($data);

        // Sort: terlambat desc, nama asc
        $rekapPerPegawai = collect($rekapPerPegawai)
            ->sortByDesc('jml_terlambat')
            ->sortBy('nama')
            ->values();

        // Statistik total
        $totalPegawai = count($rekapPerPegawai);
        $totalTerlambatSemua = collect($rekapPerPegawai)->sum('jml_terlambat');
        $totalTepatWaktuSemua = collect($rekapPerPegawai)->sum('jml_tepat_waktu');
        $totalKehadiranSemua = collect($rekapPerPegawai)->sum('jumlah_kehadiran');
        $totalCutiSemua = collect($rekapPerPegawai)->sum(function ($item) {
            return count($item['hari_cuti'] ?? []);
        });

        // Daftar departemen untuk dropdown
        $departemenList = $pegawaiList->pluck('departemen')->unique()->filter()->sort()->values();

        return view('presensi.rekap_keterlambatan', compact(
            'startDate',
            'endDate',
            'departemen',
            'departemenList',
            'rekapPerPegawai',
            'totalPegawai',
            'totalTerlambatSemua',
            'totalTepatWaktuSemua',
            'totalKehadiranSemua',
            'totalCutiSemua'
        ));
    }

    /**
     * Export Rekap Keterlambatan ke CSV
     */
    public function exportRekapKeterlambatan(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $departemen = $request->input('departemen');

        if (! $startDate || ! $endDate) {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $startDate = $start->toDateString();
            $endDate = $end->toDateString();
        }

        // Ambil data yang sama dengan rekapKeterlambatan
        $jamMasukList = \App\Models\JamMasuk::all()->keyBy('shift');
        $jamMasukArray = $jamMasukList->toArray();

        $pegawaiQuery = Pegawai::where('stts_aktif', 'AKTIF')
            ->select('id', 'nik', 'nama', 'departemen');
        if ($departemen) {
            $pegawaiQuery->where('departemen', $departemen);
        }
        $pegawaiList = $pegawaiQuery->get()->keyBy('id');
        $pegawaiIds = $pegawaiList->keys()->toArray();

        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);
        $allJadwalPegawai = [];

        $current = $startCarbon->copy()->startOfMonth();
        while ($current->lte($endCarbon)) {
            $tahun = $current->year;
            $bulan = $current->month;

            $jadwalBulan = JadwalPegawai::whereIn('id', $pegawaiIds)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->get();

            foreach ($jadwalBulan as $j) {
                $allJadwalPegawai[$bulan][$j->id] = $j;
            }
            $current->addMonth();
        }

        // Ambil data cuti (PengajuanLibur) dalam periode - semua status
        $listNik = $pegawaiList->pluck('nik')->toArray();
        $cutiData = PengajuanLibur::whereIn('nik', $listNik)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('tanggal_awal', [$startDate, $endDate])
                    ->orWhereBetween('tanggal_akhir', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('tanggal_awal', '<=', $startDate)
                          ->where('tanggal_akhir', '>=', $endDate);
                    });
            })
            ->get()
            ->groupBy('nik');

        $rekapPerPegawai = [];
        $nikToIdMap = [];
        foreach ($pegawaiList as $pegawai) {
            $rekapPerPegawai[$pegawai->id] = [
                'nik' => $pegawai->nik,
                'nama' => $pegawai->nama,
                'departemen' => $pegawai->departemen,
                'jml_terlambat' => 0,
                'jml_tepat_waktu' => 0,
                'jumlah_kehadiran' => 0,
                'total_detik_terlambat' => 0,
                'jumlah_cuti' => 0,
                'hari_cuti' => [],
            ];
            $nikToIdMap[$pegawai->nik] = $pegawai->id;
        }

        // Hitung jumlah cuti per pegawai
        foreach ($cutiData as $nik => $cutiList) {
            $id = $nikToIdMap[$nik] ?? null;
            if ($id && isset($rekapPerPegawai[$id])) {
                $rekapPerPegawai[$id]['jumlah_cuti'] = $cutiList->count();
                foreach ($cutiList as $cuti) {
                    $tanggalAwal = Carbon::parse($cuti->tanggal_awal)->copy();
                    $tanggalAkhir = Carbon::parse($cuti->tanggal_akhir);
                    while ($tanggalAwal->lte($tanggalAkhir)) {
                        if ($tanggalAwal->between($startCarbon, $endCarbon)) {
                            $rekapPerPegawai[$id]['hari_cuti'][] = $tanggalAwal->format('Y-m-d');
                        }
                        $tanggalAwal->addDay();
                    }
                }
            }
        }

        RekapPresensi::whereIn('id', $pegawaiIds)
            ->whereDate('jam_datang', '>=', $startDate)
            ->whereDate('jam_datang', '<=', $endDate)
            ->orderBy('jam_datang')
            ->chunk(500, function ($chunkPresensi) use (&$rekapPerPegawai, $allJadwalPegawai, $jamMasukArray) {
                foreach ($chunkPresensi as $presensi) {
                    if (!isset($rekapPerPegawai[$presensi->id])) continue;
                    if (!$presensi->jam_datang) continue;

                    $tanggal = Carbon::parse($presensi->jam_datang);
                    $hariKey = 'h' . $tanggal->day;
                    $bulan = $tanggal->month;

                    $jadwalPegawai = $allJadwalPegawai[$bulan][$presensi->id] ?? null;
                    $shift = $jadwalPegawai ? ($jadwalPegawai->$hariKey ?? null) : null;

                    if (!$shift) continue;

                    $jamMasukData = $jamMasukArray[$shift] ?? null;
                    if (!$jamMasukData || empty($jamMasukData['jam_masuk'])) continue;

                    $jamDatang = Carbon::parse($presensi->jam_datang, 'Asia/Jakarta');
                    $jamMasukStd = $jamDatang->copy()->setTimeFromTimeString($jamMasukData['jam_masuk']);

                    $rekapPerPegawai[$presensi->id]['jumlah_kehadiran']++;

                    if ($jamDatang->greaterThan($jamMasukStd)) {
                        $selisihDetik = $jamDatang->diffInSeconds($jamMasukStd);
                        if ($selisihDetik >= 60) {
                            $rekapPerPegawai[$presensi->id]['jml_terlambat']++;
                            $rekapPerPegawai[$presensi->id]['total_detik_terlambat'] += $selisihDetik;
                        } else {
                            $rekapPerPegawai[$presensi->id]['jml_tepat_waktu']++;
                        }
                    } else {
                        $rekapPerPegawai[$presensi->id]['jml_tepat_waktu']++;
                    }
                }
            });

        foreach ($rekapPerPegawai as $id => &$data) {
            $totalMenit = floor($data['total_detik_terlambat'] / 60);
            $totalJam = floor($totalMenit / 60);
            $sisaMenit = $totalMenit % 60;
            $data['durasi_terlambat'] = $data['jml_terlambat'] > 0 ? $totalJam . 'j ' . $sisaMenit . 'm' : '-';
        }
        unset($data);

        $rekapPerPegawai = collect($rekapPerPegawai)
            ->sortByDesc('jml_terlambat')
            ->sortBy('nama')
            ->values();

        $filename = 'rekap_keterlambatan_' . Carbon::now()->format('Ymd_His') . '.csv';

        return response()->stream(function () use ($rekapPerPegawai) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['No', 'NIK', 'Nama', 'Departemen', 'Jml Terlambat', 'Jml Tepat Waktu', 'Jumlah Kehadiran', 'Jml Cuti', 'Hari Libur', 'Durasi Terlambat']);

            foreach ($rekapPerPegawai as $idx => $row) {
                fputcsv($handle, [
                    $idx + 1,
                    $row['nik'],
                    $row['nama'],
                    $row['departemen'],
                    $row['jml_terlambat'],
                    $row['jml_tepat_waktu'],
                    $row['jumlah_kehadiran'],
                    $row['jumlah_cuti'] ?? 0,
                    count($row['hari_cuti'] ?? []),
                    $row['durasi_terlambat'],
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Detail Keterlambatan Per Pegawai
     * Breakdown per tanggal untuk satu NIK
     */
    public function rekapKeterlambatanDetail(Request $request, string $nik)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $departemen = $request->input('departemen');

        if (! $startDate || ! $endDate) {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $startDate = $start->toDateString();
            $endDate = $end->toDateString();
        }

        // Ambil data pegawai
        $pegawai = Pegawai::where('nik', $nik)->first();
        if (!$pegawai) {
            abort(404, 'Pegawai tidak ditemukan');
        }

        // Ambil jam masuk standar per shift
        $jamMasukList = \App\Models\JamMasuk::all()->keyBy('shift');
        $jamMasukArray = $jamMasukList->toArray();

        // Ambil jadwal pegawai untuk setiap bulan dalam range
        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);
        $allJadwalPegawai = [];

        $current = $startCarbon->copy()->startOfMonth();
        while ($current->lte($endCarbon)) {
            $tahun = $current->year;
            $bulan = $current->month;

            $jadwalBulan = JadwalPegawai::where('id', $pegawai->id)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->first();

            if ($jadwalBulan) {
                $allJadwalPegawai[$bulan] = $jadwalBulan;
            }
            $current->addMonth();
        }

        // Ambil data cuti pegawai dalam periode - semua status
        $cutiList = PengajuanLibur::where('nik', $nik)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('tanggal_awal', [$startDate, $endDate])
                    ->orWhereBetween('tanggal_akhir', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('tanggal_awal', '<=', $startDate)
                          ->where('tanggal_akhir', '>=', $endDate);
                    });
            })
            ->get();

        // Generate daftar tanggal cuti
        $hariCutiMap = [];
        $jumlahCuti = 0;
        $cutiDetailsMap = [];
        foreach ($cutiList as $cuti) {
            $jumlahCuti++;
            $tanggalAwal = Carbon::parse($cuti->tanggal_awal);
            $tanggalAkhir = Carbon::parse($cuti->tanggal_akhir);
            $tanggalAwalStr = $tanggalAwal->format('d-m-Y');
            $tanggalAkhirStr = $tanggalAkhir->format('d-m-Y');
            $periodeCuti = $tanggalAwalStr . ' s.d. ' . $tanggalAkhirStr;
            while ($tanggalAwal->lte($tanggalAkhir)) {
                if ($tanggalAwal->between($startCarbon, $endCarbon)) {
                    $key = $tanggalAwal->format('Y-m-d');
                    $hariCutiMap[$key] = [
                        'jenis' => $cuti->jenis_pengajuan_libur,
                        'status_cuti' => $cuti->status,
                        'jumlah_hari' => $cuti->jumlah_hari,
                        'periode' => $periodeCuti,
                    ];
                }
                $tanggalAwal->addDay();
            }
            // Simpan detail cuti per tanggal (untuk tampilan di Keterangan)
            $cutiDetailsMap[$cuti->tanggal_awal] = [
                'jenis' => $cuti->jenis_pengajuan_libur,
                'status' => $cuti->status,
                'tanggal_awal' => $tanggalAwalStr,
                'tanggal_akhir' => $tanggalAkhirStr,
                'jumlah_hari' => $cuti->jumlah_hari,
            ];
        }

        // Ambil semua presensi pegawai dalam periode
        $listPresensi = [];
        $presensiTanggalMap = [];
        // Parameter tambahan untuk passing ke closure
        $startCarbonLoop = $startCarbon;

        RekapPresensi::where('id', $pegawai->id)
            ->whereDate('jam_datang', '>=', $startDate)
            ->whereDate('jam_datang', '<=', $endDate)
            ->orderBy('jam_datang')
            ->chunk(500, function ($chunkPresensi) use (&$listPresensi, &$presensiTanggalMap, $allJadwalPegawai, $jamMasukArray, $pegawai, $startCarbonLoop, $endCarbon, $cutiDetailsMap) {
                foreach ($chunkPresensi as $presensi) {
                    if (!$presensi->jam_datang) continue;

                    $tanggal = Carbon::parse($presensi->jam_datang);
                    $tanggalKey = $tanggal->format('Y-m-d');
                    $hariKey = 'h' . $tanggal->day;
                    $bulan = $tanggal->month;

                    $jadwalPegawai = $allJadwalPegawai[$bulan] ?? null;
                    $shift = $jadwalPegawai ? ($jadwalPegawai->$hariKey ?? null) : null;

                    $jamMasukStd = null;
                    $statusRecalc = 'Tepat Waktu';
                    $keterlambatan = '-';

                    if ($shift) {
                        $jamMasukData = $jamMasukArray[$shift] ?? null;
                        if ($jamMasukData && !empty($jamMasukData['jam_masuk'])) {
                            $jamDatang = Carbon::parse($presensi->jam_datang, 'Asia/Jakarta');
                            $jamMasukStd = $jamDatang->copy()->setTimeFromTimeString($jamMasukData['jam_masuk']);
                            $jamPulangStd = !empty($jamMasukData['jam_pulang']) ? $jamDatang->copy()->setTimeFromTimeString($jamMasukData['jam_pulang']) : null;

                            if ($jamDatang->greaterThan($jamMasukStd)) {
                                $statusRecalc = 'Terlambat';
                                $keterlambatan = $jamDatang->diff($jamMasukStd)->format('%H:%i:%s');
                            }
                        }
                    }

                    $presensiTanggalMap[$tanggalKey] = true;
                    $listPresensi[] = [
                        'tanggal' => $tanggal->format('d-m-Y'),
                        'tanggal_key' => $tanggalKey,
                        'shift' => $shift,
                        'jam_masuk_std' => $jamMasukStd ? $jamMasukStd->format('H:i') : null,
                        'jam_pulang_std' => isset($jamPulangStd) ? $jamPulangStd->format('H:i') : null,
                        'jam_datang' => $tanggal->format('H:i'),
                        'jam_pulang' => $presensi->jam_pulang ? Carbon::parse($presensi->jam_pulang)->format('H:i') : '-',
                        'status' => $statusRecalc,
                        'keterlambatan' => $keterlambatan,
                        'status_db' => $presensi->status ?? '-',
                        'tipe' => 'presensi',
                    ];
                }
            });

        // Tambahkan tanggal cuti yang tidak ada di presensi
        foreach ($hariCutiMap as $tanggalKey => $cutiInfo) {
            if (!isset($presensiTanggalMap[$tanggalKey])) {
                $tanggal = Carbon::parse($tanggalKey);
                $hariKey = 'h' . $tanggal->day;
                $bulan = $tanggal->month;
                $jadwalPegawai = $allJadwalPegawai[$bulan] ?? null;
                $shift = $jadwalPegawai ? ($jadwalPegawai->$hariKey ?? null) : null;

                $jamMasukStd = null;
                $jamPulangStd = null;
                if ($shift && isset($jamMasukArray[$shift])) {
                    $jamMasukData = $jamMasukArray[$shift];
                    if (!empty($jamMasukData['jam_masuk'])) {
                        $jamMasukStd = $tanggal->copy()->setTimeFromTimeString($jamMasukData['jam_masuk']);
                        $jamPulangStd = !empty($jamMasukData['jam_pulang']) ? $tanggal->copy()->setTimeFromTimeString($jamMasukData['jam_pulang']) : null;
                    }
                }

                $listPresensi[] = [
                    'tanggal' => $tanggal->format('d-m-Y'),
                    'tanggal_key' => $tanggalKey,
                    'shift' => $shift,
                    'jam_masuk_std' => $jamMasukStd ? $jamMasukStd->format('H:i') : null,
                    'jam_pulang_std' => $jamPulangStd ? $jamPulangStd->format('H:i') : null,
                    'jam_datang' => '-',
                    'jam_pulang' => '-',
                    'status' => 'Cuti',
                    'keterlambatan' => '-',
                    'status_db' => $cutiInfo['jenis'] . ' (' . $cutiInfo['status_cuti'] . ')',
                    'tipe' => 'cuti',
                    'jenis_cuti' => $cutiInfo['jenis'],
                    'status_cuti' => $cutiInfo['status_cuti'],
                    'periode_cuti' => $cutiInfo['periode'],
                ];
            }
        }

        // Sort berdasarkan tanggal
        $listPresensi = collect($listPresensi)->sortBy('tanggal_key')->values();

        // Tambahkan tanggal dengan jadwal kosong atau hari Minggu
        $tanggalKosongMap = [];
        $current = $startCarbon->copy();
        while ($current->lte($endCarbon)) {
            $tanggalKey = $current->format('Y-m-d');
            $hari = $current->day;
            $bulan = $current->month;
            $hariKey = 'h' . $hari;
            $isMinggu = $current->dayOfWeek === 0; // 0 = Sunday

            // Skip jika sudah ada presensi atau cuti
            if (isset($presensiTanggalMap[$tanggalKey]) || isset($hariCutiMap[$tanggalKey])) {
                $current->addDay();
                continue;
            }

            // Cek apakah ada jadwal untuk bulan ini dan apakah h{day} kosong
            $jadwalBulan = $allJadwalPegawai[$bulan] ?? null;

            // Tampilkan sebagai Libur jika:
            // 1. Hari Minggu (tanggal merah), ATAU
            // 2. Jadwal ada tapi h{day} kosong
            if ($isMinggu || ($jadwalBulan && (($jadwalBulan->$hariKey ?? null) === null || $jadwalBulan->$hariKey === ''))) {
                $tanggalKosongMap[$tanggalKey] = [
                    'tanggal' => $current->format('d-m-Y'),
                    'tanggal_key' => $tanggalKey,
                    'shift' => null,
                    'jam_masuk_std' => null,
                    'jam_pulang_std' => null,
                    'jam_datang' => '-',
                    'jam_pulang' => '-',
                    'status' => 'Libur',
                    'keterlambatan' => '-',
                    'status_db' => $isMinggu ? 'Tanggal Merah' : 'Libur',
                    'tipe' => 'kosong',
                    'is_minggu' => $isMinggu,
                    'is_tanggal_merah' => $isMinggu,
                ];
            }

            $current->addDay();
        }

        // Merge tanggal kosong ke list
        foreach ($tanggalKosongMap as $kosongData) {
            $listPresensi[] = $kosongData;
        }

        // Re-sort setelah menambahkan tanggal kosong
        $listPresensi = collect($listPresensi)->sortBy('tanggal_key')->values();

        // Statistik
        $jmlTerlambat = $listPresensi->where('status', 'Terlambat')->count();
        $jmlTepatWaktu = $listPresensi->where('status', 'Tepat Waktu')->count();
        $totalKehadiran = $listPresensi->where('tipe', 'presensi')->count();
        $totalCuti = $listPresensi->where('tipe', 'cuti')->count();
        $totalKosong = $listPresensi->where('tipe', 'kosong')->count();

        // Back URL
        $backUrl = route('presensi.rekap_keterlambatan', array_filter([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'departemen' => $departemen,
        ]));

        return view('presensi.rekap_keterlambatan_detail', compact(
            'pegawai',
            'startDate',
            'endDate',
            'departemen',
            'backUrl',
            'listPresensi',
            'jmlTerlambat',
            'jmlTepatWaktu',
            'totalKehadiran',
            'jumlahCuti',
            'totalCuti',
            'totalKosong'
        ));
    }
}