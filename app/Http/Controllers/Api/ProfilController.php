<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BerkasPegawai;
use App\Models\BerkasPegawaiMysql;
use App\Models\MasterBerkasPegawai;
use App\Models\Pegawai;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProfilController extends Controller
{
    private function resolveKategoriPegawai(Pegawai $pegawai): string
    {
        if ($pegawai->dokter) {
            $kdSps = (string) ($pegawai->dokter->kd_sps ?? '');
            return trim($kdSps) !== '' ? 'Tenaga klinis Dokter Spesialis' : 'Tenaga klinis Dokter Umum';
        }
        $jbtn = (string) ($pegawai->jbtn ?? '');
        if (stripos($jbtn, 'perawat') !== false || stripos($jbtn, 'bidan') !== false) {
            return 'Tenaga klinis Perawat dan Bidan';
        }
        $bidang = (string) ($pegawai->bidang ?? '');
        $clinicalKeywords = ['staf medis', 'medis', 'farmasi', 'gizi', 'radiologi', 'analis', 'labor', 'rekam medis', 'fisioterapi'];
        foreach ($clinicalKeywords as $kw) {
            if (stripos($jbtn, $kw) !== false || stripos($bidang, $kw) !== false) {
                return 'Tenaga klinis Profesi Lain';
            }
        }
        return 'Tenaga Non Klinis';
    }

    /**
     * GET /api/profil
     * Profil pegawai yang login (data pegawai + berkas + foto URL).
     */
    public function show(Request $request)
    {
        $showAllBerkas = $request->boolean('show_all', false);

        $pegawai = Pegawai::with(['departemen_unit', 'dokter', 'petugas.jabatan'])
            ->where('nik', Auth::user()->username)
            ->first();

        if (!$pegawai) {
            return response()->json([
                'success' => false,
                'message' => 'Pegawai tidak ditemukan.',
            ], 404);
        }

        $kategoriPegawai = $this->resolveKategoriPegawai($pegawai);

        $masterBerkas = MasterBerkasPegawai::query()
            ->where('kategori', $kategoriPegawai)
            ->orderBy('no_urut')
            ->get();

        $berkasSimrs = BerkasPegawai::query()
            ->where('nik', $pegawai->nik)
            ->get();

        $berkasLatestByKode = ($berkasSimrs ?? collect())
            ->sortByDesc(fn ($b) => $b->tgl_uploud ?? '0000-00-00')
            ->groupBy('kode_berkas')
            ->map(fn ($items) => $items->first());

        $metaByKode = collect();
        try {
            $metaByKode = BerkasPegawaiMysql::query()
                ->where('nik', $pegawai->nik)
                ->get()
                ->keyBy('kode_berkas');
        } catch (QueryException $e) {
            $metaByKode = collect();
        }

        $photoUrl = getPegawaiPhotoUrl($pegawai->photo ?? null);

        $berkasList = [];
        foreach ($masterBerkas as $m) {
            $berkas = $berkasLatestByKode->get($m->kode);
            $meta = $metaByKode->get($m->kode);
            $hasFile = !empty($berkas?->berkas);
            if (empty($showAllBerkas) && !$hasFile) {
                continue;
            }
            $berkasList[] = [
                'kode' => $m->kode,
                'nama_berkas' => $m->nama_berkas ?? '-',
                'tgl_upload' => $berkas?->tgl_uploud ?? null,
                'masa_berlaku_sampai' => $meta->masa_berlaku_sampai ?? null,
                'verifikasi_status' => $hasFile ? ($meta->verifikasi_status ?? 'review') : null,
                'file_url' => $hasFile ? simrs_asset($berkas->berkas) : null,
                'berkas_path' => $berkas?->berkas ?? null,
            ];
        }

        $data = [
            'pegawai' => $this->formatPegawai($pegawai, $photoUrl),
            'kategori_pegawai' => $kategoriPegawai,
            'dokter' => $pegawai->dokter ? [
                'kd_dokter' => $pegawai->dokter->kd_dokter ?? null,
                'nm_dokter' => $pegawai->dokter->nm_dokter ?? null,
                'kd_sps' => $pegawai->dokter->kd_sps ?? null,
                'no_ijn_praktek' => $pegawai->dokter->no_ijn_praktek ?? null,
                'email' => $pegawai->dokter->email ?? null,
                'no_telp' => $pegawai->dokter->no_telp ?? null,
            ] : null,
            'petugas' => $pegawai->petugas ? [
                'nip' => $pegawai->petugas->nip ?? null,
                'jabatan' => $pegawai->petugas->jabatan->nama_jbtn ?? null,
                'email' => $pegawai->petugas->email ?? null,
                'no_telp' => $pegawai->petugas->no_telp ?? null,
                'alamat' => $pegawai->petugas->alamat ?? null,
            ] : null,
            'master_berkas' => $masterBerkas->map(fn ($m) => [
                'kode' => $m->kode,
                'nama_berkas' => $m->nama_berkas,
                'no_urut' => $m->no_urut,
            ])->values()->all(),
            'berkas' => $berkasList,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * PUT /api/profil
     * Update data profil (alamat, kota, pendidikan, npwp, rekening, bpd, no_ktp, petugas_*).
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'alamat' => ['nullable', 'string', 'max:60'],
            'kota' => ['nullable', 'string', 'max:20'],
            'pendidikan' => ['nullable', 'string', 'max:80'],
            'npwp' => ['nullable', 'string', 'max:15'],
            'rekening' => ['nullable', 'string', 'max:25'],
            'bpd' => ['nullable', 'string', 'max:50'],
            'no_ktp' => ['nullable', 'string', 'max:20'],
            'petugas_alamat' => ['nullable', 'string', 'max:60'],
            'petugas_no_telp' => ['nullable', 'string', 'max:13'],
            'petugas_email' => ['nullable', 'email', 'max:70'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $nik = Auth::user()->username;
        $pegawai = Pegawai::where('nik', $nik)->firstOrFail();

        $pegawaiFields = ['alamat', 'kota', 'pendidikan', 'npwp', 'rekening', 'bpd', 'no_ktp'];
        $pegawai->fill($request->only($pegawaiFields));
        $pegawai->save();

        if ($pegawai->petugas) {
            $petugasUpdate = [];
            if ($request->has('petugas_alamat')) $petugasUpdate['alamat'] = $request->petugas_alamat;
            if ($request->has('petugas_no_telp')) $petugasUpdate['no_telp'] = $request->petugas_no_telp;
            if ($request->has('petugas_email')) $petugasUpdate['email'] = $request->petugas_email;
            if (!empty($petugasUpdate)) {
                $pegawai->petugas->fill($petugasUpdate);
                $pegawai->petugas->save();
            }
        }

        $pegawai->load(['departemen_unit', 'dokter', 'petugas.jabatan']);
        $photoUrl = getPegawaiPhotoUrl($pegawai->photo ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'pegawai' => $this->formatPegawai($pegawai, $photoUrl),
            ],
        ]);
    }

    /**
     * PUT /api/profil/photo
     * Upload foto profil (multipart: photo, image jpg/jpeg/png max 2MB).
     */
    public function updatePhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak valid. Gunakan JPG/PNG, max 2MB.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $nik = Auth::user()->username;
        $pegawai = Pegawai::where('nik', $nik)->firstOrFail();

        $uploaded = $request->file('photo');
        $ext = strtolower($uploaded->getClientOriginalExtension() ?: 'jpg');
        $fileName = 'pegawai_' . str_replace('.', '_', (string) $pegawai->nik) . '_' . time() . '.' . $ext;

        $webappsFsRoot = (string) env('SIMRS_WEBAPPS_FS_PATH', '');
        $photoRelDir = trim((string) env('PEGAWAI_PHOTO_PATH', 'penggajian/pages/pegawai/photo'), '/');

        if ($webappsFsRoot !== '' && is_dir($webappsFsRoot)) {
            $targetDir = rtrim($webappsFsRoot, "/\\") . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $photoRelDir);
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0775, true);
            }
            $uploaded->move($targetDir, $fileName);
            $pegawai->photo = ($photoRelDir !== '' ? $photoRelDir . '/' : '') . $fileName;
        } else {
            $fallbackDir = public_path('pages/pegawai/photo');
            if (!is_dir($fallbackDir)) {
                @mkdir($fallbackDir, 0775, true);
            }
            $uploaded->move($fallbackDir, $fileName);
            $pegawai->photo = 'pages/pegawai/photo/' . $fileName;
        }
        $pegawai->save();

        $photoUrl = getPegawaiPhotoUrl($pegawai->photo);

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui.',
            'data' => [
                'photo' => $pegawai->photo,
                'photo_url' => $photoUrl,
            ],
        ]);
    }

    /**
     * PUT /api/profil/berkas
     * Upload berkas (multipart: kode_berkas, file pdf/jpg/jpeg/png max 10MB).
     */
    public function updateBerkas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode_berkas' => ['required', 'string', 'max:10'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid. File: PDF/JPG/PNG, max 10MB.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $nik = Auth::user()->username;
        $pegawai = Pegawai::where('nik', $nik)->firstOrFail();
        $master = MasterBerkasPegawai::where('kode', $request->kode_berkas)->first();

        if (!$master) {
            return response()->json([
                'success' => false,
                'message' => 'Kode berkas tidak ditemukan.',
            ], 404);
        }

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

        BerkasPegawai::create([
            'nik' => $pegawai->nik,
            'tgl_uploud' => Carbon::now()->toDateString(),
            'kode_berkas' => $master->kode,
            'berkas' => $relativePath,
        ]);

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
            //
        }

        $fileUrl = simrs_asset($relativePath);

        return response()->json([
            'success' => true,
            'message' => 'Berkas berhasil diupload.',
            'data' => [
                'kode_berkas' => $master->kode,
                'nama_berkas' => $master->nama_berkas,
                'tgl_upload' => Carbon::now()->toDateString(),
                'berkas' => $relativePath,
                'file_url' => $fileUrl,
            ],
        ]);
    }

    /**
     * PUT /api/profil/berkas/masa-berlaku
     * Update masa berlaku berkas (body: kode_berkas, masa_berlaku_sampai).
     */
    public function updateMasaBerlaku(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode_berkas' => ['required', 'string', 'max:10'],
            'masa_berlaku_sampai' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $nik = Auth::user()->username;
        $pegawai = Pegawai::where('nik', $nik)->firstOrFail();

        $latest = BerkasPegawai::query()
            ->where('nik', $pegawai->nik)
            ->where('kode_berkas', $request->kode_berkas)
            ->orderByDesc('tgl_uploud')
            ->first();

        if (!$latest) {
            return response()->json([
                'success' => false,
                'message' => 'Berkas belum ada, tidak bisa set masa berlaku.',
            ], 400);
        }

        BerkasPegawaiMysql::updateOrCreate(
            ['nik' => $pegawai->nik, 'kode_berkas' => $request->kode_berkas],
            [
                'tgl_uploud' => $latest->tgl_uploud,
                'berkas' => $latest->berkas,
                'masa_berlaku_sampai' => $request->masa_berlaku_sampai,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Masa berlaku berhasil disimpan.',
            'data' => [
                'kode_berkas' => $request->kode_berkas,
                'masa_berlaku_sampai' => $request->masa_berlaku_sampai,
            ],
        ]);
    }

    private function formatPegawai(Pegawai $pegawai, ?string $photoUrl = null): array
    {
        if ($photoUrl === null) {
            $photoUrl = getPegawaiPhotoUrl($pegawai->photo ?? null);
        }
        return [
            'nik' => $pegawai->nik,
            'nama' => $pegawai->nama,
            'jk' => $pegawai->jk,
            'jbtn' => $pegawai->jbtn,
            'jnj_jabatan' => $pegawai->jnj_jabatan ?? null,
            'bidang' => $pegawai->bidang ?? null,
            'departemen' => $pegawai->departemen ?? null,
            'departemen_nama' => $pegawai->departemen_unit->nama_departemen ?? null,
            'alamat' => $pegawai->alamat ?? null,
            'kota' => $pegawai->kota ?? null,
            'tmp_lahir' => $pegawai->tmp_lahir ?? null,
            'tgl_lahir' => $pegawai->tgl_lahir ? Carbon::parse($pegawai->tgl_lahir)->format('Y-m-d') : null,
            'stts_aktif' => $pegawai->stts_aktif ?? null,
            'stts_kerja' => $pegawai->stts_kerja ?? null,
            'stts_wp' => $pegawai->stts_wp ?? null,
            'pendidikan' => $pegawai->pendidikan ?? null,
            'npwp' => $pegawai->npwp ?? null,
            'mulai_kerja' => $pegawai->mulai_kerja ? Carbon::parse($pegawai->mulai_kerja)->format('Y-m-d') : null,
            'ms_kerja' => $pegawai->ms_kerja ?? null,
            'mulai_kontrak' => $pegawai->mulai_kontrak ? Carbon::parse($pegawai->mulai_kontrak)->format('Y-m-d') : null,
            'cuti_diambil' => $pegawai->cuti_diambil ?? null,
            'gapok' => isset($pegawai->gapok) ? (float) $pegawai->gapok : null,
            'dankes' => isset($pegawai->dankes) ? (float) $pegawai->dankes : null,
            'rekening' => $pegawai->rekening ?? null,
            'bpd' => $pegawai->bpd ?? null,
            'no_ktp' => $pegawai->no_ktp ?? null,
            'photo' => $pegawai->photo ?? null,
            'photo_url' => $photoUrl,
        ];
    }
}
