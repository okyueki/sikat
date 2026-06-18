# Spesifikasi Backend — Integrasi InsightFace (Presensi)

Dokumen ini untuk **tim backend Laravel SIKAT**. Mobile app (`sikat-navy-flow`) akan mengonsumsi endpoint di bawah setelah backend siap.

**Prinsip utama (fase 1 — production-safe):**

> Aplikasi sudah **production**. Presensi existing **tidak boleh rusak**.  
> Face recognition adalah **tambahan informatif**, bukan gate wajib.  
> Pegawai yang **belum** daftar wajah tetap bisa absen seperti sekarang.

**Ruang lingkup fase 1:**

| Termasuk | Tidak termasuk |
|----------|----------------|
| `Api\AbsensiController` (mobile API) | Presensi web (`Kepegawaian\AbsensiController`) |
| Tabel baru di DB **`mysql`** (default Laravel) | `ALTER TABLE pegawai` di **`server_74`** / SIMRS |
| Mode **`soft`** — tidak memblokir presensi | Mode `strict` (fase 2, dokumen terpisah) |

---

## 1. Arsitektur

```
┌─────────────┐     HTTPS      ┌──────────────────┐    HTTP (LAN)    ┌─────────────────────┐
│  Mobile App │ ─────────────► │  Laravel SIKAT   │ ───────────────► │ InsightFace Python  │
│  (Capacitor)│                │  sikat.../api    │                  │ 192.168.10.44:6700  │
└─────────────┘                └──────────────────┘                  └─────────────────────┘
                                      │
                                      ├── server_74: temporary_presensi, rekap_presensi (TIDAK DIUBAH)
                                      └── mysql: pegawai_face_profiles, face_verification_logs (BARU)
```

| Komponen | URL |
|----------|-----|
| API SIKAT (publik) | `https://sikat.rsaisyiyahsitifatimah.com/api` |
| InsightFace (LAN only) | `http://192.168.10.44:6700` |
| Swagger InsightFace | `http://192.168.10.44:6700/docs` |

**Aturan:**

- Mobile **tidak** memanggil `192.168.10.44` langsung.
- Port **6700 tidak** di-NAT ke internet.
- Firewall: hanya IP **server production Laravel** yang boleh akses InsightFace.
- Uji `curl` wajib dari **server production**, bukan hanya mesin dev lokal.

---

## 2. Apa yang disimpan di mana?

| Data | Lokasi | Keterangan |
|------|--------|------------|
| Foto presensi (JPEG) | `public/presensi/` + kolom `photo` di `temporary_presensi` / `rekap_presensi` (`server_74`) | **Tidak berubah** — sama seperti sekarang |
| Embedding wajah referensi | Server **InsightFace** (`192.168.10.44`) | Laravel **tidak** menyimpan embedding |
| Status enroll | Tabel **`pegawai_face_profiles`** (`mysql`) | Pengganti `face_enrolled_at` di `pegawai` |
| Hasil verify per submit | Tabel **`face_verification_logs`** (`mysql`) | `match` / `mismatch` / `skipped` / `error` + score |

---

## 3. Mode operasi

```env
FACE_VERIFY_MODE=soft
```

| `FACE_VERIFY_MODE` | Perilaku |
|--------------------|----------|
| `soft` (default) | Verifikasi wajah **tidak memblokir** presensi — log + field informatif di response |
| `strict` | Presensi **ditolak server** (HTTP 403) jika belum enroll, mismatch, atau InsightFace error |

### Matriks perilaku — mode `soft`

| Situasi | Presensi | Log | Response `face_verification.status` |
|---------|----------|-----|-------------------------------------|
| Belum enroll wajah | ✅ Sukses | `skipped` | `skipped` |
| Sudah enroll + wajah cocok | ✅ Sukses | `match` | `match` |
| Sudah enroll + wajah tidak cocok | ✅ Sukses | `mismatch` | `mismatch` |
| InsightFace down / timeout | ✅ Sukses | `error` | `error` |

**Jangan** return `403` karena wajah di mode `soft`.

### Matriks perilaku — mode `strict`

Verifikasi dijalankan **sebelum** `DB::beginTransaction()` — presensi tidak pernah tersimpan jika ditolak.

| Situasi | Presensi | HTTP | Response |
|---------|----------|------|----------|
| Belum enroll wajah | ❌ Ditolak | 403 | `success: false`, `face_rejected: true`, `verified: false` |
| Wajah cocok (InsightFace match) | ✅ Sukses | 200 | `verified: true` |
| Wajah tidak cocok | ❌ Ditolak | 403 | `face_rejected: true`, `status: mismatch` |
| InsightFace down / timeout | ❌ Ditolak | 403 | `status: error` |

Aktifkan setelah app mobile siap (liveness + enroll wajib):

```env
FACE_VERIFY_MODE=strict
php artisan config:clear
```

### Liveness / anti foto cetak

**Bukan tanggung jawab backend Laravel saat ini.** InsightFace `face-verification` membandingkan embedding wajah — **tidak** mendeteksi foto cetak vs wajah hidup. Lapisan anti-spoof (kedip, tekstur 2 frame, dll.) **wajib di mobile** (`face-utils.ts`). Backend `strict` melengkapi dengan penolakan server saat wajah tidak cocok dengan NIK login.

---

## 4. Environment variables

Tambahkan di `.env` server Laravel:

```env
INSIGHTFACE_ENABLED=true
INSIGHTFACE_BASE_URL=http://192.168.10.44:6700
INSIGHTFACE_TIMEOUT=5
INSIGHTFACE_MIN_SCORE=70
FACE_VERIFY_MODE=soft
FACE_SOFT_MISMATCH_AUDIT_NOTICE="Wajah tidak cocok dengan data profil Anda. Apakah Anda yakin ingin melanjutkan presensi? Seluruh aktivitas presensi dapat diaudit oleh SDI."
```

| Variable | Keterangan |
|----------|------------|
| `INSIGHTFACE_ENABLED` | `false` = fitur face off, presensi normal tanpa verify |
| `INSIGHTFACE_BASE_URL` | Base URL service Python (tanpa trailing slash) |
| `INSIGHTFACE_TIMEOUT` | Detik — jangan terlalu besar agar tidak delay response |
| `INSIGHTFACE_MIN_SCORE` | Threshold similarity **0–100** (default **70**); selaras uji lab `refpytonbyverif.md` |
| `FACE_VERIFY_MODE` | Fase 1: tetap `soft` |
| `FACE_SOFT_MISMATCH_AUDIT_NOTICE` | Teks peringatan audit untuk mobile saat mismatch (soft) |

---

## 5. Service: `App\Services\InsightFaceService`

Wrapper HTTP ke InsightFace. Model Eloquent **tidak** memakai `connection = server_74`.

| Method | Memanggil InsightFace | Input | Output |
|--------|----------------------|-------|--------|
| `enroll(string $nik, $image)` | `POST /upload-selfie?name={nik}` | multipart `file` | sukses/gagal + message |
| `verify(string $nik, $image)` | `POST /face-verification?name={nik}` | multipart `file` | `matched`, `score`, `raw` |
| `ping()` (opsional) | `GET /` | — | `bool` |

**Parameter `name` = NIK pegawai** — format sama dengan `username` login / `pegawai.nik` (contoh: `278.21.11.2018`).

**Re-enroll:** Panggil `upload-selfie` lagi dengan NIK yang sama → InsightFace **overwrite** wajah referensi (tidak perlu hapus manual).

**Catatan:** OpenAPI InsightFace (`/openapi.json`) menampilkan response `200` bertipe `string`. Parser production sudah disesuaikan dengan format aktual (lihat §15).

**Format response aktual** (uji production, selaras `refpytonbyverif.md`):

```json
{
  "status_code": 200,
  "result": {
    "similarity": 99,
    "status": true
  }
}
```

- `result.status` → `matched` / `verified`
- `result.similarity` → skor 0–100; dibandingkan dengan `INSIGHTFACE_MIN_SCORE`
- Parameter `name` (NIK) dikirim sebagai **query string**, bukan field multipart body

### Contoh uji dari server production Laravel

```bash
# Health check
curl -s -o /dev/null -w "%{http_code}" http://192.168.10.44:6700/

# Lihat kontrak API
curl -s http://192.168.10.44:6700/openapi.json

# Enroll (overwrite jika NIK sudah ada)
curl -X POST "http://192.168.10.44:6700/upload-selfie?name=278.21.11.2018" \
  -F "file=@selfie.jpg"

# Verify
curl -X POST "http://192.168.10.44:6700/face-verification?name=278.21.11.2018" \
  -F "file=@selfie_baru.jpg"
```

---

## 6. Endpoint API baru (SIKAT)

Semua endpoint memakai auth **Laravel Sanctum** (`Authorization: Bearer {token}`), sama seperti absensi existing.  
Hanya **`Api\AbsensiController`** — presensi web tidak terpengaruh.

### 6.1 `POST /api/absensi/enroll-face`

Mendaftarkan wajah referensi pegawai (**opsional** — tidak wajib untuk bisa absen).

**Request** — sama fleksibelnya dengan `POST /api/absensi/submit`:

| Cara | Content-Type | Field `image` |
|------|--------------|---------------|
| **Opsi A** | `application/json` | Base64: `"image": "data:image/jpeg;base64,..."` |
| **Opsi B** | `multipart/form-data` | File JPEG/PNG (max 2MB) |

**Alur backend:**

1. Ambil pegawai dari token Sanctum (`username` = NIK)
2. Panggil `InsightFaceService::enroll($pegawai->nik, $image)`
3. Jika sukses → upsert `pegawai_face_profiles` (`pegawai_id`, `nik`, `enrolled_at = now()`)
4. Return response

**Response sukses (200):**

```json
{
  "success": true,
  "message": "Wajah berhasil didaftarkan.",
  "data": {
    "face_enrolled": true,
    "enrolled_at": "2026-06-07T10:00:00+07:00"
  }
}
```

**Response error (contoh):**

```json
{
  "success": false,
  "message": "Gagal mendaftarkan wajah. Pastikan wajah terlihat jelas."
}
```

---

### 6.2 `POST /api/absensi/verify-face`

Verifikasi wajah **tanpa** menyimpan presensi. Mobile memanggil **sebelum** `POST /submit` agar user tahu cocok/tidak sebelum data masuk database.

**Request:** sama dengan enroll — field `image` (multipart atau base64).

**Response (200):** `face_verification` + `min_score` (ambang `INSIGHTFACE_MIN_SCORE`, skala 0–100).

Log ke `face_verification_logs` dengan `tipe = verify`.

---

### 6.3 `GET /api/absensi/face-status`

Cek apakah pegawai sudah pernah enroll wajah + mode verifikasi aktif.

**Response — sudah enroll (200):**

```json
{
  "success": true,
  "data": {
    "face_enrolled": true,
    "enrolled_at": "2026-06-07T10:00:00+07:00",
    "verify_mode": "soft"
  }
}
```

**Response — belum enroll (200):**

```json
{
  "success": true,
  "data": {
    "face_enrolled": false,
    "enrolled_at": null,
    "verify_mode": "soft"
  }
}
```

---

## 7. Perubahan `POST /api/absensi/submit` (existing)

Lihat alur lengkap di [`API_ABSENSI.md`](../API_ABSENSI.md).

### Yang TIDAK diubah

- Validasi token / pegawai
- Rate limit (`presensi-api:{ip}:{user_id}`, 10/menit)
- Fake GPS (`is_mock_location`)
- Radius lokasi
- Jadwal shift (termasuk shift malam, closing, jadwal tambahan)
- Transaksi DB presensi (`temporary_presensi` → `rekap_presensi`)
- Simpan foto JPEG ke `public/presensi/`
- Presensi web (`Kepegawaian\AbsensiController`)

### Kapan face verify dijalankan

Verify dijalankan pada **datang dan pulang** — keduanya informatif (soft mode).

| `data.tipe` | Setelah apa | Tabel presensi yang sudah tersimpan |
|-------------|-------------|-------------------------------------|
| `datang` | Insert `temporary_presensi` | `temporary_presensi` |
| `pulang` | Insert `rekap_presensi`, hapus `temporary` | `rekap_presensi` |

### Urutan eksekusi (penting — production-safe)

**Mode `soft`:**

```
1. Validasi, GPS, radius
2. Transaksi DB presensi → DB::commit()
3. InsightFace verify (di LUAR transaksi) → log + face_verification informatif
4. Return JSON (presensi selalu sukses kecuali error lain)
```

**Mode `strict`:**

```
1. Validasi, GPS, radius
2. FaceVerificationService::gateSubmit() — SEBELUM transaksi
   - Belum enroll / mismatch / error → HTTP 403, presensi TIDAK ditulis
   - Match → lanjut
3. Transaksi DB presensi → DB::commit()
4. Return JSON + face_verification (verified: true)
```

**Jangan** panggil InsightFace **di dalam** `DB::beginTransaction()` — timeout bisa lock row. Panggil **sebelum** `beginTransaction()` (strict) atau **setelah** `commit()` (soft).

### Pseudocode

```php
// Setelah DB::commit() dan presensi sukses:
$faceVerification = ['status' => 'skipped', 'score' => null];

$profile = PegawaiFaceProfile::where('pegawai_id', $pegawai->id)->first();
if ($profile && config('face.verify_mode') === 'soft') {
    try {
        $result = $insightFace->verify($pegawai->nik, $requestImage);
        $faceVerification = [
            'status' => $result->matched ? 'match' : 'mismatch',
            'score' => $result->score,
        ];
        FaceVerificationLog::create([
            'pegawai_id' => $pegawai->id,
            'nik' => $pegawai->nik,
            'tipe' => $resultData['tipe'], // datang | pulang
            'status' => $faceVerification['status'],
            'score' => $result->score,
            'jam_datang' => ...,
            'shift' => ...,
            'insightface_response' => $result->raw,
        ]);
    } catch (\Throwable $e) {
        $faceVerification = ['status' => 'error', 'score' => null];
        FaceVerificationLog::create([..., 'status' => 'error', ...]);
        // JANGAN rollback presensi — sudah commit
    }
} else {
    FaceVerificationLog::create([..., 'status' => 'skipped', ...]);
}

return response()->json([
    'success' => true,
    'message' => '...',
    'data' => [...],
    'face_verification' => $faceVerification,
]);
```

### Field tambahan di response submit

| Field | Tipe | Keterangan |
|-------|------|------------|
| `face_verification.nik` | string | NIK pegawai login (identitas yang dicek) |
| `face_verification.nama` | string | Nama pegawai login — untuk tampilan UI |
| `face_verification.status` | string | `match` \| `mismatch` \| `skipped` \| `error` |
| `face_verification.score` | number \| null | Skor similarity InsightFace (0–100) |
| `face_verification.verified` | boolean | `true` hanya jika wajah cocok dengan akun login |
| `face_verification.message` | string \| null | Teks siap tampil, mis. `Wajah terverifikasi: {nama}` |

Muncul di response **datang dan pulang**. Verifikasi 1:1 terhadap pegawai dari token Sanctum (bukan identifikasi orang lain). Mobile boleh tampilkan `message` atau `nama` saat `verified: true`. Field ini **opsional** bagi client lama (backward compatible).

---

## 8. Database

Semua tabel baru di connection **`mysql`** (default Laravel). **Tidak** mengubah schema `server_74` / SIMRS.

**Migrasi:** File migration Laravel disediakan di repo; **dijalankan manual** oleh tim infra (bukan otomatis saat deploy).

### 8.1 Tabel `pegawai_face_profiles` (`mysql`)

Pengganti rencana lama `ALTER TABLE pegawai ADD face_enrolled_at` — agar tabel `pegawai` SIMRS tidak disentuh.

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | auto-increment |
| `pegawai_id` | unsigned int | ID pegawai di `server_74.pegawai` (tanpa FK cross-database) |
| `nik` | varchar | NIK / username login; dipakai sebagai `name` di InsightFace |
| `enrolled_at` | timestamp | Waktu enroll terakhir (re-enroll = update) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Satu baris per `pegawai_id` (unique). Re-enroll = update `enrolled_at`.

### 8.2 Tabel `face_verification_logs` (`mysql`)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | |
| `pegawai_id` | unsigned int | |
| `nik` | varchar | |
| `tipe` | string | `datang` \| `pulang` |
| `status` | string | `match`, `mismatch`, `skipped`, `error` |
| `score` | decimal(5,4) nullable | |
| `jam_datang` | datetime nullable | Referensi waktu presensi (bukan FK) |
| `shift` | varchar nullable | |
| `insightface_response` | text nullable | Response mentah untuk debug |
| `created_at` | timestamp | |

**Tidak** memakai `rekap_presensi_id` — tabel `rekap_presensi` di `server_74` tidak punya PK auto-increment per baris (kolom `id` = id pegawai).

### 8.3 SQL referensi (jalankan manual di DB `mysql`)

```sql
CREATE TABLE pegawai_face_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pegawai_id INT UNSIGNED NOT NULL,
  nik VARCHAR(50) NOT NULL,
  enrolled_at TIMESTAMP NOT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_pegawai_face_profiles_pegawai_id (pegawai_id),
  KEY idx_pegawai_face_profiles_nik (nik)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE face_verification_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pegawai_id INT UNSIGNED NOT NULL,
  nik VARCHAR(50) NOT NULL,
  tipe VARCHAR(10) NOT NULL COMMENT 'datang|pulang',
  status VARCHAR(20) NOT NULL COMMENT 'match|mismatch|skipped|error',
  score DECIMAL(5,4) NULL,
  jam_datang DATETIME NULL,
  shift VARCHAR(50) NULL,
  insightface_response TEXT NULL,
  created_at TIMESTAMP NULL,
  KEY idx_face_verification_logs_pegawai_id (pegawai_id),
  KEY idx_face_verification_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 9. Mapping InsightFace API

| InsightFace endpoint | Dipakai kapan | Query `name` |
|---------------------|---------------|--------------|
| `POST /upload-selfie` | Enroll / re-enroll dari `POST /absensi/enroll-face` | NIK (overwrite) |
| `POST /face-verification` | Soft verify saat `POST /absensi/submit` (datang & pulang) | NIK |
| `GET /faces` | Opsional — cek daftar wajah terdaftar | — |
| `POST /face-search` | Tidak dipakai fase 1 | — |

---

## 10. Keamanan

| Item | Wajib |
|------|-------|
| InsightFace hanya di LAN | ✅ |
| Port 6700 tidak expose ke internet | ✅ |
| Mobile tidak tahu URL InsightFace | ✅ |
| Hanya Laravel yang proxy ke InsightFace | ✅ |
| Log verifikasi di `mysql` untuk evaluasi | ✅ |
| Tabel `pegawai` SIMRS tidak diubah | ✅ |

InsightFace **tidak punya auth** — rawan jika dibuka publik. Jangan expose.

---

## 11. Checklist QA sebelum go-live

- [ ] `curl` dari **server production Laravel** ke `192.168.10.44:6700` berhasil
- [ ] Format response aktual `face-verification` & `upload-selfie` didokumentasikan
- [ ] Tabel `pegawai_face_profiles` & `face_verification_logs` dibuat di **mysql** (manual migrate)
- [ ] `POST /absensi/enroll-face` — enroll 1 NIK uji, baris `pegawai_face_profiles` terisi
- [ ] Re-enroll NIK yang sama — overwrite sukses, `enrolled_at` ter-update
- [ ] `GET /absensi/face-status` — return `face_enrolled`, `verify_mode: soft` benar
- [ ] Submit **datang** tanpa enroll → sukses, `face_verification.status = skipped`
- [ ] Submit **pulang** tanpa enroll → sukses, `face_verification.status = skipped`
- [ ] Submit datang setelah enroll, foto sama → sukses, `status = match`
- [ ] Submit setelah enroll, foto orang lain → sukses, `status = mismatch` (bukan 403)
- [ ] Matikan service InsightFace → submit tetap sukses, `status = error`
- [ ] GPS / radius / jadwal / fake GPS / closing / jadwal tambahan tetap normal
- [ ] Presensi **web** tidak terpengaruh (tidak ada hook face verify)

---

## 12. Yang dikerjakan mobile (setelah API siap)

Panduan lengkap tim frontend: **[`docs/FRONTEND_INSIGHTFACE_PRESENSI.md`](FRONTEND_INSIGHTFACE_PRESENSI.md)**.

| Fitur | Endpoint |
|-------|----------|
| Capture shutter in-memory (tanpa simpan galeri) | Client-side — lihat dokumen frontend §3 |
| Daftar wajah opsional di Profil | `POST /absensi/enroll-face` |
| Cek status enroll | `GET /api/absensi/face-status` |
| Presensi (URL tidak berubah) | `POST /api/absensi/submit` |
| Banner info jika belum enroll | — (tidak blokir absen) |
| Toast informatif jika `mismatch` | Baca `face_verification` + `audit_notice` |

Mobile **tidak** menunggu blocking dari face verification di fase 1.

**Format image:** Backend mendukung base64 (JSON) dan multipart (file) — samakan dengan cara kirim `submit` yang sudah dipakai app.

---

## 13. Prioritas implementasi

| Prioritas | Task |
|-----------|------|
| P0 | `InsightFaceService` + uji curl dari server production |
| P0 | Migration file + SQL referensi (`mysql` only) |
| P0 | Model `PegawaiFaceProfile`, `FaceVerificationLog` (connection default) |
| P0 | `POST /api/absensi/enroll-face` |
| P0 | `GET /api/absensi/face-status` |
| P0 | Soft hook di `POST /api/absensi/submit` (datang + pulang, setelah commit) |
| P1 | Evaluasi log 1–2 minggu |
| P2 | `FACE_VERIFY_MODE=strict` — lihat dokumen fase 2 terpisah |

---

## 14. Open items (setelah deploy kode)

1. Fine-tune `INSIGHTFACE_MIN_SCORE` di `.env` setelah uji foto pegawai internal (default 70)
2. Konfirmasi tim mobile: base64 atau multipart (backend sudah support keduanya)
3. Evaluasi log `face_verification_logs` 1–2 minggu sebelum aktifkan `strict`
4. Tim mobile: integrasi UI per [`FRONTEND_INSIGHTFACE_PRESENSI.md`](FRONTEND_INSIGHTFACE_PRESENSI.md)

### File implementasi

| File | Keterangan |
|------|------------|
| `config/insightface.php` | Konfigurasi env (enabled, min_score, audit notice) |
| `app/Services/InsightFaceService.php` | Proxy HTTP ke InsightFace (`file` multipart) |
| `app/Services/FaceVerificationService.php` | Enroll, status, soft/strict hook submit |
| `app/Models/PegawaiFaceProfile.php` | mysql |
| `app/Models/FaceVerificationLog.php` | mysql |
| `database/migrations/2026_06_07_*` | Jalankan manual di DB mysql |
| `Api/AbsensiController` | `enrollFace`, `faceStatus`, gate + finalize submit |
| `routes/api.php` | `/enroll-face`, `/verify-face`, `/face-status` |

**Tanpa migrasi mysql:** presensi tetap jalan; `face_verification.status = skipped`. Enroll mengembalikan pesan agar migrasi dijalankan.

---

## 15. Status implementasi & perbaikan backend

Kode P0 **sudah diimplementasi** di repo. Ringkasan perbaikan yang relevan untuk tim mobile:

### 15.1 Parser response InsightFace

`InsightFaceService::parseVerificationResponse()` membaca format production:

```json
{"status_code":200,"result":{"similarity":99,"status":true}}
```

Fitur parser:

- Ekstrak `result.status` / `result.similarity` (alias: `score`, `confidence`, dll.)
- Fallback: jika `status` boolean tidak ada, bandingkan `similarity >= INSIGHTFACE_MIN_SCORE`
- Log response mentah ke `face_verification_logs.insightface_response` untuk debug

### 15.2 Enroll & re-enroll

| Perbaikan | Detail |
|-----------|--------|
| Query param `name` | NIK dikirim sebagai `?name={nik}`, bukan body form |
| Re-enroll otomatis | Jika InsightFace return 422 (unique constraint), backend `DELETE /faces?name={nik}` lalu upload ulang |
| Upsert profil | `pegawai_face_profiles` di-update; `enrolled_at` refresh |

### 15.3 Alur submit presensi + verify

```
strict:  gateSubmit() SEBELUM DB::beginTransaction()
         → mismatch/enroll/error = HTTP 403, presensi TIDAK ditulis
         → match = lanjut transaksi, face_verification di response

soft:    transaksi presensi → commit()
         → verify SETELAH commit (tidak rollback jika InsightFace timeout)
         → face_verification informatif di response
```

Implementasi di `AbsensiController::submit()`:

1. `FaceVerificationService::gateSubmit()` — baris sebelum `DB::beginTransaction()`
2. `FaceVerificationService::finalizeSubmitResponse()` — setelah `DB::commit()`

### 15.4 Payload `face_verification` (response mobile)

Setiap submit (datang & pulang) menyertakan:

| Field | Keterangan |
|-------|------------|
| `nik`, `nama` | Identitas pegawai login (bukan hasil identifikasi orang lain) |
| `status` | `match` \| `mismatch` \| `skipped` \| `error` |
| `score` | Similarity 0–100 atau `null` |
| `verified` | `true` hanya jika `status === 'match'` |
| `message` | Teks siap tampil di UI |
| `audit_notice` | Hanya mismatch + mode soft |
| `show_confirm_dialog` | `true` hanya mismatch + mode soft |

Mode strict ditolak dengan HTTP 403 + `face_rejected: true`.

### 15.5 Graceful degradation

| Kondisi | Perilaku |
|---------|----------|
| `INSIGHTFACE_ENABLED=false` | Verify dilewati; enroll gagal dengan pesan jelas |
| Tabel mysql belum dimigrate | Presensi normal; `status: skipped`; enroll minta jalankan migrasi |
| InsightFace timeout / down | Soft: presensi sukses, `status: error`; Strict: HTTP 403 |
| Base64 image | Decode ke temp file → kirim ke InsightFace → hapus temp file |

### 15.6 Endpoint `face-status`

Response `data` sekarang menyertakan `nik` dan `nama` selain `face_enrolled`, `enrolled_at`, `verify_mode` — agar mobile bisa menampilkan identitas tanpa lookup terpisah.

### 15.7 Referensi uji lab Python

| Script | Endpoint InsightFace | Dipakai production? |
|--------|---------------------|---------------------|
| `docs/refpytonbyverif.md` | `POST /face-verification?name={NIK}` | ✅ Ya — sama dengan backend Laravel |
| `docs/refpython.md` | `POST /face-search` | ❌ Tidak — identifikasi 1:N, bukan verify akun login |

Script Python membuktikan alur **capture in-memory → multipart POST → baca similarity/status**. Mobile meniru konsep capture; proxy API lewat Laravel (lihat dokumen frontend).
