<?php

namespace App\Http\Controllers\Profil;

use App\Http\Controllers\Controller;
use App\Models\BerkasPegawai;
use App\Models\BerkasPegawaiMysql;
use App\Models\MasterBerkasPegawai;
use App\Models\Pegawai;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProfilController extends Controller
{
    private function resolveKategoriPegawai(Pegawai $pegawai): string
    {
        // 1) Dokter: umum vs spesialis
        if ($pegawai->dokter) {
            $kdSps = (string) ($pegawai->dokter->kd_sps ?? '');
            return trim($kdSps) !== '' ? 'Tenaga klinis Dokter Spesialis' : 'Tenaga klinis Dokter Umum';
        }

        // 2) Perawat/Bidan dari jabatan
        $jbtn = (string) ($pegawai->jbtn ?? '');
        if (stripos($jbtn, 'perawat') !== false || stripos($jbtn, 'bidan') !== false) {
            return 'Tenaga klinis Perawat dan Bidan';
        }

        // 3) Profesi klinis lain (heuristik dari jabatan/bidang)
        $bidang = (string) ($pegawai->bidang ?? '');
        $clinicalKeywords = ['staf medis', 'medis', 'farmasi', 'gizi', 'radiologi', 'analis', 'labor', 'rekam medis', 'fisioterapi'];
        foreach ($clinicalKeywords as $kw) {
            if (stripos($jbtn, $kw) !== false || stripos($bidang, $kw) !== false) {
                return 'Tenaga klinis Profesi Lain';
            }
        }

        // 4) Default: non klinis
        return 'Tenaga Non Klinis';
    }

    public function show()
    {
        $showAllBerkas = request()->boolean('show_all', false);

        $pegawai = Pegawai::with(['departemen_unit', 'dokter', 'petugas.jabatan'])
            ->where('nik', Auth::user()->username)
            ->first();

        if (!$pegawai) {
            abort(404, 'Pegawai tidak ditemukan');
        }

        $kategoriPegawai = $this->resolveKategoriPegawai($pegawai);

        // Master jenis berkas SIMRS (difilter sesuai kategori pegawai)
        $masterBerkas = MasterBerkasPegawai::query()
            ->where('kategori', $kategoriPegawai)
            ->orderBy('no_urut')
            ->get();

        // Berkas SIMRS untuk pegawai login (PASTI difilter by nik)
        $berkasSimrs = BerkasPegawai::query()
            ->where('nik', $pegawai->nik)
            ->get();

        // Ambil yang terbaru per kode_berkas
        $berkasLatestByKode = ($berkasSimrs ?? collect())
            ->sortByDesc(function ($b) {
                return $b->tgl_uploud ?? '0000-00-00';
            })
            ->groupBy('kode_berkas')
            ->map(fn ($items) => $items->first());

        // Meta internal (mysql)
        $metaByKode = collect();
        try {
            $metaByKode = BerkasPegawaiMysql::query()
                ->where('nik', $pegawai->nik)
                ->get()
                ->keyBy('kode_berkas');
        } catch (QueryException $e) {
            $metaByKode = collect();
        }

        return view('profil.show', compact('pegawai', 'masterBerkas', 'berkasLatestByKode', 'metaByKode', 'showAllBerkas', 'kategoriPegawai'));
    }

    public function updateProfil(Request $request)
    {
        $nik = Auth::user()->username;

        $validated = $request->validate([
            // Editable fields (semua ada di tabel pegawai)
            'alamat' => ['nullable', 'string', 'max:60'],
            'kota' => ['nullable', 'string', 'max:20'],
            'pendidikan' => ['nullable', 'string', 'max:80'],
            'npwp' => ['nullable', 'string', 'max:15'],
            'rekening' => ['nullable', 'string', 'max:25'],
            'bpd' => ['nullable', 'string', 'max:50'],
            'no_ktp' => ['nullable', 'string', 'max:20'],

            // Editable fields tabel petugas (khusus jika pegawai punya record petugas)
            'petugas_alamat' => ['nullable', 'string', 'max:60'],
            'petugas_no_telp' => ['nullable', 'string', 'max:13'],
            'petugas_email' => ['nullable', 'email', 'max:70'],
        ]);

        $pegawai = Pegawai::where('nik', $nik)->firstOrFail();
        $pegawaiFields = [
            'alamat', 'kota', 'pendidikan', 'npwp', 'rekening', 'bpd', 'no_ktp',
        ];
        $pegawai->fill(array_intersect_key($validated, array_flip($pegawaiFields)));
        $pegawai->save();

        // Update tabel petugas jika ada
        if ($pegawai->petugas) {
            $petugasUpdate = [];
            if (array_key_exists('petugas_alamat', $validated)) $petugasUpdate['alamat'] = $validated['petugas_alamat'];
            if (array_key_exists('petugas_no_telp', $validated)) $petugasUpdate['no_telp'] = $validated['petugas_no_telp'];
            if (array_key_exists('petugas_email', $validated)) $petugasUpdate['email'] = $validated['petugas_email'];

            // Hindari overwrite dengan null kalau field tidak dikirim
            $pegawai->petugas->fill($petugasUpdate);
            $pegawai->petugas->save();
        }

        return redirect()->route('profil.show')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePhoto(Request $request)
    {
        $nik = Auth::user()->username;

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'], // 2MB
        ]);

        $pegawai = Pegawai::where('nik', $nik)->firstOrFail();

        $uploaded = $request->file('photo');
        $ext = strtolower($uploaded->getClientOriginalExtension() ?: 'jpg');
        $fileName = 'pegawai_' . str_replace('.', '_', (string) $pegawai->nik) . '_' . time() . '.' . $ext;

        // Jika webapps SIMRS ada di filesystem server ini (mounted), set env ini:
        // SIMRS_WEBAPPS_FS_PATH=/path/ke/webapps
        $webappsFsRoot = (string) env('SIMRS_WEBAPPS_FS_PATH', '');
        $photoRelDir = trim((string) env('PEGAWAI_PHOTO_PATH', 'penggajian/pages/pegawai/photo'), '/');

        if ($webappsFsRoot !== '' && is_dir($webappsFsRoot)) {
            $targetDir = rtrim($webappsFsRoot, "/\\") . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $photoRelDir);
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0775, true);
            }

            $uploaded->move($targetDir, $fileName);

            // Simpan path relatif di bawah webapps
            $pegawai->photo = ($photoRelDir !== '' ? $photoRelDir . '/' : '') . $fileName;
            $pegawai->save();

            return redirect()->route('profil.show')->with('success', 'Foto profil berhasil diperbarui.');
        }

        // Fallback: simpan ke public Laravel (jika webapps SIMRS ada di server terpisah)
        $fallbackDir = public_path('pages/pegawai/photo');
        if (!is_dir($fallbackDir)) {
            @mkdir($fallbackDir, 0775, true);
        }
        $uploaded->move($fallbackDir, $fileName);

        $pegawai->photo = 'pages/pegawai/photo/' . $fileName;
        $pegawai->save();

        return redirect()->route('profil.show')->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function updateBerkas(Request $request)
    {
        $nik = Auth::user()->username;

        $request->validate([
            'kode_berkas' => ['required', 'string', 'max:10'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // 10MB
        ]);

        // Pegawai dari SIMRS
        $pegawai = Pegawai::where('nik', $nik)->firstOrFail();

        // Validasi kode_berkas ada di master SIMRS
        $master = MasterBerkasPegawai::where('kode', $request->kode_berkas)->firstOrFail();

        // Simpan file ke folder public agar path sesuai pola SIMRS: pages/berkaspegawai/berkas/...
        $dir = public_path('pages/berkaspegawai/berkas');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $uploaded = $request->file('file');
        $ext = $uploaded->getClientOriginalExtension();
        $safeName = Str::slug($master->nama_berkas ?: $master->kode, '_');
        $fileName = $safeName . '_' . str_replace('.', '_', $pegawai->nik) . '_' . time() . '.' . $ext;
        $uploaded->move($dir, $fileName);

        $relativePath = 'pages/berkaspegawai/berkas/' . $fileName;

        // Insert record berkas ke SIMRS
        BerkasPegawai::create([
            'nik' => $pegawai->nik,
            'tgl_uploud' => Carbon::now()->toDateString(),
            'kode_berkas' => $master->kode,
            'berkas' => $relativePath,
        ]);

        // Upsert meta internal (mysql) (optional tapi membantu)
        try {
            BerkasPegawaiMysql::updateOrCreate(
                ['nik' => $pegawai->nik, 'kode_berkas' => $master->kode],
                [
                    'tgl_uploud' => Carbon::now()->toDateString(),
                    'berkas' => $relativePath,
                    'verifikasi_status' => 'review',
                ]
            );
        } catch (QueryException $e) {
            // Abaikan jika tabel meta belum ada.
        }

        return redirect()->route('profil.show')->with('success', 'Berkas berhasil diupload.');
    }

    public function updateMasaBerlaku(Request $request)
    {
        $nik = Auth::user()->username;

        $request->validate([
            'kode_berkas' => ['required', 'string', 'max:10'],
            'masa_berlaku_sampai' => ['nullable', 'date'],
        ]);

        $pegawai = Pegawai::where('nik', $nik)->firstOrFail();

        // Pastikan berkasnya memang ada untuk pegawai ini
        $latest = BerkasPegawai::query()
            ->where('nik', $pegawai->nik)
            ->where('kode_berkas', $request->kode_berkas)
            ->orderByDesc('tgl_uploud')
            ->first();

        if (!$latest) {
            return back()->withErrors([
                'kode_berkas' => 'Berkas belum ada, tidak bisa set masa berlaku.',
            ]);
        }

        // Simpan ke meta (server_74.berkas_pegawai_meta)
        BerkasPegawaiMysql::updateOrCreate(
            ['nik' => $pegawai->nik, 'kode_berkas' => $request->kode_berkas],
            [
                'tgl_uploud' => $latest->tgl_uploud,
                'berkas' => $latest->berkas,
                'masa_berlaku_sampai' => $request->masa_berlaku_sampai,
            ]
        );

        return redirect()->route('profil.show')->with('success', 'Masa berlaku berhasil disimpan.');
    }
}
