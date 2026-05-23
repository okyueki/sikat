<?php

namespace App\Http\Controllers\Inventaris;

use App\Http\Controllers\Controller;
use App\Models\Inventaris;
use App\Models\InventarisRuang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventarisVisualisasiController extends Controller
{
    /**
     * Menampilkan halaman visualisasi inventaris
     */
    public function index(Request $request)
    {
        $ruang = InventarisRuang::orderBy('nama_ruang', 'asc')->get();
        
        // Default filter: 1 tahun terakhir
        $tanggalAwal = $request->get('tanggal_awal', Carbon::now()->subYear()->format('Y-m-d'));
        $tanggalAkhir = $request->get('tanggal_akhir', Carbon::now()->format('Y-m-d'));
        $idRuang = $request->get('ruang', '');
        
        // Query dasar
        $query = Inventaris::with(['ruang', 'barang']);
        
        // Filter tanggal pengadaan
        if ($tanggalAwal && $tanggalAkhir) {
            $query->whereBetween('tgl_pengadaan', [$tanggalAwal, $tanggalAkhir]);
        }
        
        // Filter ruangan
        if ($idRuang) {
            $query->where('id_ruang', $idRuang);
        }
        
        // Data untuk grafik distribusi per ruangan
        $dataPerRuang = $this->getDataPerRuang($tanggalAwal, $tanggalAkhir, $idRuang);
        
        // Data untuk grafik kondisi/status
        $dataKondisi = $this->getDataKondisi($tanggalAwal, $tanggalAkhir, $idRuang);
        
        // Data untuk trend pengadaan (bulanan)
        $dataTrendPengadaan = $this->getDataTrendPengadaan($tanggalAwal, $tanggalAkhir, $idRuang);
        
        // Data untuk top 10 ruangan dengan inventaris terbanyak
        $topRuang = $this->getTopRuang($tanggalAwal, $tanggalAkhir, 10);
        
        // Data untuk nilai inventaris per ruangan
        $nilaiPerRuang = $this->getNilaiPerRuang($tanggalAwal, $tanggalAkhir, $idRuang);
        
        // Statistik umum
        $statistik = $this->getStatistik($tanggalAwal, $tanggalAkhir, $idRuang);
        
        return view('inventaris.visualisasi_inventaris', compact(
            'ruang',
            'tanggalAwal',
            'tanggalAkhir',
            'idRuang',
            'dataPerRuang',
            'dataKondisi',
            'dataTrendPengadaan',
            'topRuang',
            'nilaiPerRuang',
            'statistik'
        ));
    }
    
    /**
     * API endpoint untuk mendapatkan data per ruangan (AJAX)
     */
    public function getDataPerRuangApi(Request $request)
    {
        $tanggalAwal = $request->get('tanggal_awal', Carbon::now()->subYear()->format('Y-m-d'));
        $tanggalAkhir = $request->get('tanggal_akhir', Carbon::now()->format('Y-m-d'));
        $idRuang = $request->get('ruang', '');
        
        $data = $this->getDataPerRuang($tanggalAwal, $tanggalAkhir, $idRuang);
        
        return response()->json($data);
    }
    
    /**
     * Mendapatkan data inventaris per ruangan
     */
    private function getDataPerRuang($tanggalAwal, $tanggalAkhir, $idRuang)
    {
        $query = Inventaris::select('inventaris_ruang.nama_ruang', DB::raw('COUNT(*) as jumlah'))
            ->join('inventaris_ruang', 'inventaris.id_ruang', '=', 'inventaris_ruang.id_ruang')
            ->whereBetween('inventaris.tgl_pengadaan', [$tanggalAwal, $tanggalAkhir])
            ->groupBy('inventaris_ruang.id_ruang', 'inventaris_ruang.nama_ruang')
            ->orderBy('jumlah', 'desc');
        
        if ($idRuang) {
            $query->where('inventaris.id_ruang', $idRuang);
        }
        
        return $query->get();
    }
    
    /**
     * Mendapatkan data kondisi/status barang
     */
    private function getDataKondisi($tanggalAwal, $tanggalAkhir, $idRuang)
    {
        $query = Inventaris::select('status_barang', DB::raw('COUNT(*) as jumlah'))
            ->whereBetween('tgl_pengadaan', [$tanggalAwal, $tanggalAkhir])
            ->groupBy('status_barang')
            ->orderBy('jumlah', 'desc');
        
        if ($idRuang) {
            $query->where('id_ruang', $idRuang);
        }
        
        return $query->get();
    }
    
    /**
     * Mendapatkan data trend pengadaan (bulanan)
     */
    private function getDataTrendPengadaan($tanggalAwal, $tanggalAkhir, $idRuang)
    {
        $query = Inventaris::select(
                DB::raw('DATE_FORMAT(tgl_pengadaan, "%Y-%m") as bulan'),
                DB::raw('COUNT(*) as jumlah')
            )
            ->whereBetween('tgl_pengadaan', [$tanggalAwal, $tanggalAkhir])
            ->groupBy('bulan')
            ->orderBy('bulan', 'asc');
        
        if ($idRuang) {
            $query->where('id_ruang', $idRuang);
        }
        
        return $query->get();
    }
    
    /**
     * Mendapatkan top ruangan dengan inventaris terbanyak
     */
    private function getTopRuang($tanggalAwal, $tanggalAkhir, $limit = 10)
    {
        return Inventaris::select('inventaris_ruang.nama_ruang', DB::raw('COUNT(*) as jumlah'))
            ->join('inventaris_ruang', 'inventaris.id_ruang', '=', 'inventaris_ruang.id_ruang')
            ->whereBetween('inventaris.tgl_pengadaan', [$tanggalAwal, $tanggalAkhir])
            ->groupBy('inventaris_ruang.id_ruang', 'inventaris_ruang.nama_ruang')
            ->orderBy('jumlah', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Mendapatkan nilai inventaris per ruangan
     */
    private function getNilaiPerRuang($tanggalAwal, $tanggalAkhir, $idRuang)
    {
        $query = Inventaris::select(
                'inventaris_ruang.nama_ruang',
                DB::raw('SUM(inventaris.harga) as total_nilai'),
                DB::raw('COUNT(*) as jumlah')
            )
            ->join('inventaris_ruang', 'inventaris.id_ruang', '=', 'inventaris_ruang.id_ruang')
            ->whereBetween('inventaris.tgl_pengadaan', [$tanggalAwal, $tanggalAkhir])
            ->groupBy('inventaris_ruang.id_ruang', 'inventaris_ruang.nama_ruang')
            ->orderBy('total_nilai', 'desc');
        
        if ($idRuang) {
            $query->where('inventaris.id_ruang', $idRuang);
        }
        
        return $query->get();
    }
    
    /**
     * Mendapatkan statistik umum
     */
    private function getStatistik($tanggalAwal, $tanggalAkhir, $idRuang)
    {
        $query = Inventaris::whereBetween('tgl_pengadaan', [$tanggalAwal, $tanggalAkhir]);
        
        if ($idRuang) {
            $query->where('id_ruang', $idRuang);
        }
        
        return [
            'total_inventaris' => $query->count(),
            'total_nilai' => $query->sum('harga'),
            'rata_rata_nilai' => $query->avg('harga'),
            'ruang_terbanyak' => $this->getTopRuang($tanggalAwal, $tanggalAkhir, 1)->first(),
        ];
    }
}
