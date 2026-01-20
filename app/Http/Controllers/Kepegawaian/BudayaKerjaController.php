<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use App\Models\BudayaKerja;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

class BudayaKerjaController extends Controller
{
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
        
        return view('budayakerja.rekapan', compact(
            'bulan', 'tahun', 'rankingTertinggi', 'rankingTerendah', 
            'statistikItem', 'statistikDepartemen', 'trendData', 'itemLabels',
            'pegawaiBelumDinilai'
        ));
    }
}