<?php
namespace App\Http\Controllers;

use App\Models\PengajuanLibur;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CutiController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Cuti';
        $nik=Auth::user()->username;
        if ($request->ajax()) {
            $data = PengajuanLibur::with('pegawai')->where('nik', $nik)
                ->whereIn('jenis_pengajuan_libur', ['Tahunan', 'Melahirkan', 'Ambil Libur', 'Menikah'])
                ->orderBy('tanggal_dibuat', 'desc')
                ->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nama_pegawai', function($row) {
                    // Mengakses nama pegawai dari relasi
                    return $row->pegawai ? $row->pegawai->nama : '-';
                })
                ->addColumn('action', function($row){
                    if ($row->status == 'Dikirim') {
                    $btn = '
                        <a href="'.route('cuti.edit', $row->id_pengajuan_libur).'" class="btn btn-info waves-effect waves-light"><i class="far fa-edit"></i></a>
                        <form action="'.route('cuti.destroy', $row->id_pengajuan_libur).'" method="POST" style="display:inline;">
                            '.csrf_field().'
                            '.method_field("DELETE").'
                            <button type="submit" class="btn btn-danger waves-effect waves-light delete-confirm"><i class="far fa-trash-alt"></i></button>
                        </form>';
                    }else{
                        $btn = '<a href="'.route('pengajuan_libur.show', encrypt($row->kode_pengajuan_libur)).'" class="btn btn-primary waves-effect waves-light"><i class="fas fa-eye"></i></a>';
                    }
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    
        return view('cuti.index', compact('title'));
    }

    public function create()
    {
        $title = 'Create Cuti';
        $pegawai = Pegawai::where('stts_aktif','AKTIF')->get();
        return view('cuti.create', compact('title','pegawai'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis_pengajuan_libur' => 'required|in:Tahunan,Melahirkan,Ambil Libur,Menikah',
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
            'jumlah_hari' => 'required|integer|min:1',
            'nik_atasan_langsung' => 'required|string',
            'keterangan' => 'required|string|max:500',
            'alamat' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $nik = Auth::user()->username;
        $jenis = $request->jenis_pengajuan_libur;
        $jumlahHari = (int) $request->jumlah_hari;
        $tahunCuti = (int) Carbon::parse($request->tanggal_awal)->format('Y');

        if ($jenis === 'Tahunan') {
            if ($jumlahHari > 2) {
                return redirect()->back()->withErrors(['jumlah_hari' => 'Cuti Tahunan maksimal 2 hari per pengajuan.'])->withInput();
            }
            $sudahDipakai = PengajuanLibur::where('nik', $nik)
                ->where('jenis_pengajuan_libur', 'Tahunan')
                ->where('status', 'Disetujui')
                ->whereYear('tanggal_awal', $tahunCuti)
                ->count();
            if ($sudahDipakai >= 12) {
                return redirect()->back()->withErrors(['jenis_pengajuan_libur' => 'Kuota cuti Tahunan tahun ' . $tahunCuti . ' sudah habis (maksimal 12x per tahun).'])->withInput();
            }
        }
        if ($jenis === 'Ambil Libur' && $jumlahHari > 2) {
            return redirect()->back()->withErrors(['jumlah_hari' => 'Ambil Libur maksimal 2 hari per pengajuan.'])->withInput();
        }

        $request->merge(['nik' => $nik, 'tanggal_dibuat' => Carbon::now()]);
        $pengajuanLibur = PengajuanLibur::create($request->only([
            'jenis_pengajuan_libur', 'nik', 'tanggal_awal', 'tanggal_akhir', 'jumlah_hari',
            'nik_atasan_langsung', 'keterangan', 'alamat', 'tanggal_dibuat',
        ]));

        return redirect()->route('cuti.index')->with('success', 'Cuti created successfully.');
    }

    public function edit($id)
    {
        $title = 'Edit Cuti';
        $nik = Auth::user()->username;
        $cuti = PengajuanLibur::where('nik', $nik)
            ->whereIn('jenis_pengajuan_libur', ['Tahunan', 'Melahirkan', 'Ambil Libur', 'Menikah'])
            ->findOrFail($id);
        if ($cuti->status !== 'Dikirim') {
            return redirect()->route('cuti.index')->with('error', 'Hanya pengajuan dengan status Dikirim yang dapat diedit.');
        }
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')->orderBy('nama')->get();
        return view('cuti.edit', compact('cuti', 'title', 'pegawai'));
    }

    public function update(Request $request, $id)
    {
        $nik = Auth::user()->username;
        $cuti = PengajuanLibur::where('nik', $nik)
            ->whereIn('jenis_pengajuan_libur', ['Tahunan', 'Melahirkan', 'Ambil Libur', 'Menikah'])
            ->findOrFail($id);
        if ($cuti->status !== 'Dikirim') {
            return redirect()->route('cuti.index')->with('error', 'Hanya pengajuan dengan status Dikirim yang dapat diedit.');
        }
        $validator = Validator::make($request->all(), [
            'jenis_pengajuan_libur' => 'required|in:Tahunan,Melahirkan,Ambil Libur,Menikah',
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
            'jumlah_hari' => 'required|integer|min:1',
            'nik_atasan_langsung' => 'required|string',
            'keterangan' => 'required|string|max:500',
            'alamat' => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $cuti->update($request->only([
            'jenis_pengajuan_libur', 'tanggal_awal', 'tanggal_akhir', 'jumlah_hari',
            'nik_atasan_langsung', 'keterangan', 'alamat',
        ]));
        return redirect()->route('cuti.index')->with('success', 'Cuti updated successfully.');
    }

    public function destroy($id)
    {
        $nik = Auth::user()->username;
        $cuti = PengajuanLibur::where('nik', $nik)
            ->whereIn('jenis_pengajuan_libur', ['Tahunan', 'Melahirkan', 'Ambil Libur', 'Menikah'])
            ->findOrFail($id);
        if ($cuti->status !== 'Dikirim') {
            return redirect()->route('cuti.index')->with('error', 'Hanya pengajuan dengan status Dikirim yang dapat dihapus.');
        }
        $cuti->delete();
        return redirect()->route('cuti.index')->with('success', 'Cuti deleted successfully.');
    }
}