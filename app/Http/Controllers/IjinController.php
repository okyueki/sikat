<?php

namespace App\Http\Controllers;

use App\Models\PengajuanLibur;
use App\Models\Pegawai;
use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class IjinController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Ijin';
        $nik=Auth::user()->username;
        if ($request->ajax()) {
            $data = PengajuanLibur::with('pegawai')
            ->where('nik', $nik)
            ->where('jenis_pengajuan_libur', 'Ijin')
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
                        <a href="'.route('ijin.edit', $row->id_pengajuan_libur).'" class="btn btn-info waves-effect waves-light"><i class="far fa-edit"></i></a>
                        <form action="'.route('ijin.destroy', $row->id_pengajuan_libur).'" method="POST" style="display:inline;">
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
    
        return view('ijin.index', compact('title'));
    }

    public function create()
    {
        $title = 'Create Ijin';
        $pegawai = Pegawai::where('stts_aktif','AKTIF')->get();
        return view('ijin.create', compact('title','pegawai'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
            'jumlah_hari' => 'required|integer|min:1',
            'keterangan' => 'required|string|max:500',
            'nik_atasan_langsung' => 'required|string',
            'file' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $filePath = $file->storeAs('uploads/ijin_files', $fileName, 'public');
        }

        $pengajuan = PengajuanLibur::create([
            'jenis_pengajuan_libur' => 'Ijin',
            'nik' => Auth::user()->username,
            'tanggal_awal' => $request->tanggal_awal,
            'tanggal_akhir' => $request->tanggal_akhir,
            'jumlah_hari' => (int) $request->jumlah_hari,
            'keterangan' => $request->keterangan,
            'nik_atasan_langsung' => $request->nik_atasan_langsung,
            'foto' => $filePath,
            'tanggal_dibuat' => Carbon::now(),
        ]);

        // Audit trail
        AuditTrail::logCreate('ijin', 'pengajuan_libur', $pengajuan->id_pengajuan_libur, [
            'tanggal_awal' => $request->tanggal_awal,
            'tanggal_akhir' => $request->tanggal_akhir,
            'jumlah_hari' => $request->jumlah_hari,
            'keterangan' => $request->keterangan,
        ], 'Pengajuan ijin baru dibuat');

        return redirect()->route('ijin.index')->with('success', 'Pengajuan Ijin berhasil disimpan.');
    }

    public function edit($id)
    {
        $title = 'Edit Ijin';
        $nik = Auth::user()->username;
        $pengajuanLibur = PengajuanLibur::where('nik', $nik)
            ->where('jenis_pengajuan_libur', 'Ijin')
            ->findOrFail($id);
        if ($pengajuanLibur->status !== 'Dikirim') {
            return redirect()->route('ijin.index')->with('error', 'Hanya pengajuan dengan status Dikirim yang dapat diedit.');
        }
        $pegawai = Pegawai::where('stts_aktif', 'AKTIF')->orderBy('nama')->get();
        return view('ijin.edit', compact('pengajuanLibur', 'pegawai', 'title'));
    }

    public function update(Request $request, $id)
    {
        $nik = Auth::user()->username;
        $pengajuanLibur = PengajuanLibur::where('nik', $nik)
            ->where('jenis_pengajuan_libur', 'Ijin')
            ->findOrFail($id);
        if ($pengajuanLibur->status !== 'Dikirim') {
            return redirect()->route('ijin.index')->with('error', 'Hanya pengajuan dengan status Dikirim yang dapat diedit.');
        }

        $request->validate([
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
            'jumlah_hari' => 'required|integer|min:1',
            'keterangan' => 'required|string|max:500',
            'nik_atasan_langsung' => 'required|string',
            'file' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
        ]);

        $filePath = $pengajuanLibur->foto;
        if ($request->hasFile('file')) {
            if ($pengajuanLibur->foto && Storage::disk('public')->exists($pengajuanLibur->foto)) {
                Storage::disk('public')->delete($pengajuanLibur->foto);
            }
            $file = $request->file('file');
            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $filePath = $file->storeAs('uploads/ijin_files', $fileName, 'public');
        }

        // Simpan old values untuk audit
        $oldValues = $pengajuanLibur->toArray();

        $pengajuanLibur->update([
            'tanggal_awal' => $request->tanggal_awal,
            'tanggal_akhir' => $request->tanggal_akhir,
            'jumlah_hari' => (int) $request->jumlah_hari,
            'keterangan' => $request->keterangan,
            'nik_atasan_langsung' => $request->nik_atasan_langsung,
            'foto' => $filePath,
        ]);

        // Audit trail
        AuditTrail::logUpdate('ijin', 'pengajuan_libur', $pengajuanLibur->id_pengajuan_libur, [
            'tanggal_awal' => $oldValues['tanggal_awal'],
            'tanggal_akhir' => $oldValues['tanggal_akhir'],
            'jumlah_hari' => $oldValues['jumlah_hari'],
            'keterangan' => $oldValues['keterangan'],
        ], [
            'tanggal_awal' => $request->tanggal_awal,
            'tanggal_akhir' => $request->tanggal_akhir,
            'jumlah_hari' => $request->jumlah_hari,
            'keterangan' => $request->keterangan,
        ], 'Pengajuan ijin diupdate');

        return redirect()->route('ijin.index')->with('success', 'Pengajuan Ijin berhasil diupdate.');
    }

    public function destroy($id)
    {
        $nik = Auth::user()->username;
        $pengajuanLibur = PengajuanLibur::where('nik', $nik)
            ->where('jenis_pengajuan_libur', 'Ijin')
            ->findOrFail($id);
        if ($pengajuanLibur->status !== 'Dikirim') {
            return redirect()->route('ijin.index')->with('error', 'Hanya pengajuan dengan status Dikirim yang dapat dihapus.');
        }
        if ($pengajuanLibur->foto && Storage::disk('public')->exists($pengajuanLibur->foto)) {
            Storage::disk('public')->delete($pengajuanLibur->foto);
        }

        // Simpan data untuk audit sebelum delete
        $oldData = $pengajuanLibur->toArray();
        $recordId = $pengajuanLibur->id_pengajuan_libur;

        $pengajuanLibur->delete();

        // Audit trail
        AuditTrail::logDelete('ijin', 'pengajuan_libur', $recordId, $oldData, 'Pengajuan ijin dihapus');
        return redirect()->route('ijin.index')->with('success', 'Pengajuan Ijin berhasil dihapus.');
    }
}
