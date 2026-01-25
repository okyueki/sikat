<?php

namespace App\Http\Controllers;
use App\Models\Pegawai;
use App\Models\BerkasPegawai;
use App\Models\BerkasPegawaiMysql;
use App\Models\MasterBerkasPegawai;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class BerkasPegawaiController extends Controller
{
    private function resolveKategoriFromPegawaiRow($pegawai): string
    {
        $jbtn = (string) ($pegawai->jbtn ?? '');
        $bidang = (string) ($pegawai->bidang ?? '');

        if (stripos($jbtn, 'dokter') !== false) {
            if (stripos($jbtn, 'sp') !== false || stripos($jbtn, 'spesialis') !== false) {
                return 'Tenaga klinis Dokter Spesialis';
            }
            return 'Tenaga klinis Dokter Umum';
        }

        if (stripos($jbtn, 'perawat') !== false || stripos($jbtn, 'bidan') !== false) {
            return 'Tenaga klinis Perawat dan Bidan';
        }

        $clinicalKeywords = ['staf medis', 'medis', 'farmasi', 'gizi', 'radiologi', 'analis', 'labor', 'rekam medis', 'fisioterapi'];
        foreach ($clinicalKeywords as $kw) {
            if (stripos($jbtn, $kw) !== false || stripos($bidang, $kw) !== false) {
                return 'Tenaga klinis Profesi Lain';
            }
        }

        return 'Tenaga Non Klinis';
    }

   public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Pegawai::select('id', 'nik', 'nama', 'jbtn', 'bidang')
                ->where('stts_aktif', 'AKTIF');

            // Filter pegawai berdasarkan status verifikasi berkas (review/approved/rejected/uploaded)
            $verifikasi = $request->get('verifikasi');
            $allowed = ['uploaded', 'review', 'approved', 'rejected'];
            if (is_string($verifikasi) && in_array($verifikasi, $allowed, true)
                && Schema::connection('mysql')->hasTable('berkas_pegawai_meta')) {
                $nikList = BerkasPegawaiMysql::query()
                    ->where('verifikasi_status', $verifikasi)
                    ->distinct()
                    ->pluck('nik')
                    ->all();

                // Jika tidak ada hasil, kembalikan kosong tanpa query besar
                if (count($nikList) === 0) {
                    $query->whereRaw('1=0');
                } else {
                    $query->whereIn('nik', $nikList);
                }
            }

            $data = $query->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('kategori', function ($row) {
                    return $this->resolveKategoriFromPegawaiRow($row);
                })
                ->addColumn('progress', function ($row) {
                    $kategori = $this->resolveKategoriFromPegawaiRow($row);

                    $totalMaster = MasterBerkasPegawai::query()
                        ->where('kategori', $kategori)
                        ->count();

                    $uploadedDistinct = BerkasPegawai::query()
                        ->where('nik', $row->nik)
                        ->distinct()
                        ->count('kode_berkas');

                    $reviewCount = 0;
                    $rejectedCount = 0;
                    if (Schema::connection('mysql')->hasTable('berkas_pegawai_meta')) {
                        $reviewCount = BerkasPegawaiMysql::query()
                            ->where('nik', $row->nik)
                            ->where('verifikasi_status', 'review')
                            ->count();
                        $rejectedCount = BerkasPegawaiMysql::query()
                            ->where('nik', $row->nik)
                            ->where('verifikasi_status', 'rejected')
                            ->count();
                    }

                    $totalMaster = max(0, (int) $totalMaster);
                    $uploadedDistinct = (int) $uploadedDistinct;
                    $missingCount = max($totalMaster - $uploadedDistinct, 0);

                    $progressText = $totalMaster > 0
                        ? ($uploadedDistinct . '/' . $totalMaster . ' uploaded')
                        : '-';

                    $badges = [];
                    if ($reviewCount > 0) $badges[] = '<span class="badge bg-warning text-dark">review ' . (int)$reviewCount . '</span>';
                    if ($rejectedCount > 0) $badges[] = '<span class="badge bg-danger">rejected ' . (int)$rejectedCount . '</span>';
                    if ($missingCount > 0) $badges[] = '<span class="badge bg-secondary">belum upload ' . $missingCount . '</span>';

                    return '<div>' . e($progressText) . '</div>' . (count($badges) ? '<div class="mt-1">' . implode(' ', $badges) . '</div>' : '');
                })
                ->addColumn('action', function ($row) {
                    $btn = '<a href="'.route('berkas_pegawai.edit', $row->nik).'" class="edit btn btn-primary btn-sm"><i class="far fa-edit"></i></a>';
                    return $btn;
                })
                ->rawColumns(['action', 'progress'])
                ->make(true);
        }
         $title = 'Berkas Pegawai';
        return view('berkas_pegawai.index', compact('title')); // Ganti dengan nama view Anda
    }
    
    public function edit($nik)
    {
        $title = 'Edit Berkas Pegawai';
        $Pegawai = Pegawai::where('nik', $nik)->firstOrFail();

        $kategoriPegawai = $this->resolveKategoriFromPegawaiRow($Pegawai);
        $masterBerkas = MasterBerkasPegawai::query()
            ->where('kategori', $kategoriPegawai)
            ->orderBy('no_urut')
            ->get();

        $berkasSimrs = BerkasPegawai::query()
            ->where('nik', $Pegawai->nik)
            ->get();

        $berkasLatestByKode = ($berkasSimrs ?? collect())
            ->sortByDesc(fn ($b) => $b->tgl_uploud ?? '0000-00-00')
            ->groupBy('kode_berkas')
            ->map(fn ($items) => $items->first());

        $metaByKode = collect();
        try {
            $metaByKode = BerkasPegawaiMysql::query()
                ->where('nik', $Pegawai->nik)
                ->get()
                ->keyBy('kode_berkas');
        } catch (QueryException $e) {
            $metaByKode = collect();
        }
        
        return view('berkas_pegawai.edit', compact('masterBerkas', 'berkasLatestByKode', 'metaByKode', 'title', 'Pegawai', 'kategoriPegawai'));
    }
    
    public function update(Request $request, $nik)
    {
        $pegawai = Pegawai::where('nik', $nik)->firstOrFail();
        $files = $request->file('file', []);

        $request->validate([
            'file' => ['array'],
            'file.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // 10MB
            'masa_berlaku_sampai' => ['array'],
            'masa_berlaku_sampai.*' => ['nullable', 'date'],
        ]);

        // Master list sesuai kategori pegawai (supaya update hanya item relevan)
        $kategoriPegawai = $this->resolveKategoriFromPegawaiRow($pegawai);
        $masterList = MasterBerkasPegawai::query()
            ->where('kategori', $kategoriPegawai)
            ->orderBy('no_urut')
            ->get();

        $allowedVerif = ['uploaded', 'review', 'approved', 'rejected'];

        foreach ($masterList as $m) {
            $kode = $m->kode;

            $incomingVerif = $request->input("verifikasi_status.$kode");
            $incomingNote = $request->input("catatan_verifikator.$kode");

            if ($incomingVerif === 'rejected' && empty(trim((string) $incomingNote))) {
                return back()
                    ->withErrors(["catatan_verifikator.$kode" => 'Catatan verifikator wajib diisi jika status Rejected.'])
                    ->withInput();
            }

            // Upload file ke SIMRS (optional)
            if (isset($files[$kode])) {
                $uploaded = $files[$kode];
                $ext = $uploaded->getClientOriginalExtension();
                $safeName = Str::slug(($m->nama_berkas ?: $kode), '_');
                $fileName = $safeName . '_' . str_replace('.', '_', $pegawai->nik) . '_' . time() . '.' . $ext;

                $dir = public_path('pages/berkaspegawai/berkas');
                if (!is_dir($dir)) {
                    @mkdir($dir, 0775, true);
                }
                $uploaded->move($dir, $fileName);
                $relativePath = 'pages/berkaspegawai/berkas/' . $fileName;

                BerkasPegawai::create([
                    'nik' => $pegawai->nik,
                    'tgl_uploud' => now()->toDateString(),
                    'kode_berkas' => $kode,
                    'berkas' => $relativePath,
                ]);
            }

            // Simpan meta masa berlaku/verifikasi/catatan ke mysql (berkas_pegawai_meta)
            try {
                $latest = BerkasPegawai::query()
                    ->where('nik', $pegawai->nik)
                    ->where('kode_berkas', $kode)
                    ->orderByDesc('tgl_uploud')
                    ->first();

                $attrs = [
                    'masa_berlaku_sampai' => $request->input("masa_berlaku_sampai.$kode"),
                    'catatan_verifikator' => $incomingNote,
                ];

                if (is_string($incomingVerif) && in_array($incomingVerif, $allowedVerif, true)) {
                    $attrs['verifikasi_status'] = $incomingVerif;
                }

                if (($attrs['verifikasi_status'] ?? null) === 'approved' || ($attrs['verifikasi_status'] ?? null) === 'rejected') {
                    $attrs['verified_at'] = now();
                    $attrs['verified_by'] = Auth::user()?->username;
                }

                // Simpan referensi file SIMRS terbaru (kalau ada)
                if ($latest) {
                    $attrs['tgl_uploud'] = $latest->tgl_uploud;
                    $attrs['berkas'] = $latest->berkas;
                }

                // Hindari membuat meta untuk item yang benar-benar kosong dan belum ada file
                $shouldWriteMeta = (bool) $latest
                    || !empty($attrs['masa_berlaku_sampai'])
                    || !empty(trim((string) ($attrs['catatan_verifikator'] ?? '')))
                    || (($attrs['verifikasi_status'] ?? 'uploaded') !== 'uploaded');

                if (!$shouldWriteMeta) {
                    continue;
                }

                BerkasPegawaiMysql::updateOrCreate(
                    ['nik' => $pegawai->nik, 'kode_berkas' => $kode],
                    $attrs
                );
            } catch (QueryException $e) {
                $dbName = config('database.connections.mysql.database');
                return back()
                    ->withErrors([
                        'berkas_pegawai_meta' =>
                            "Gagal simpan meta masa berlaku ke DB mysql (database: {$dbName}). "
                            . "Pastikan tabel `berkas_pegawai_meta` ada dan kolomnya sesuai. "
                            . "Error: " . $e->getMessage(),
                    ])
                    ->withInput();
            }
        }
    
        return redirect()->route('berkas_pegawai.index')->with('success', 'Berkas berhasil diperbarui.');
    }
    
}
