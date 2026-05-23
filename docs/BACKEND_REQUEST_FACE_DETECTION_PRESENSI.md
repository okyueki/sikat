# Backend Request: Face Verification Presensi

Tujuan: presensi selfie tidak hanya memastikan "ada wajah", tetapi memverifikasi
bahwa wajah tersebut milik pegawai yang sedang login dan bukan foto ulang /
screen replay.

## Prinsip

- Frontend hanya melakukan pre-check agar foto layak dikirim: satu wajah, wajah
  cukup besar, posisi tengah, cahaya/kontras cukup.
- Keputusan final wajib di backend karena client bisa dimodifikasi.
- Backend tetap harus menyimpan foto presensi seperti sekarang untuk audit.

## Data Master Wajah

Tambahkan enrollment wajah pegawai:

- Minimal 3 foto referensi per pegawai, diambil dari kamera langsung.
- Simpan embedding wajah, bukan hanya file foto.
- Simpan metadata: `pegawai_id`, `model_name`, `model_version`,
  `quality_score`, `created_at`, `updated_at`.
- Foto referensi asli boleh disimpan untuk audit internal, tetapi akses harus
  dibatasi.

Contoh tabel:

```sql
face_enrollments
- id
- pegawai_id
- embedding_json
- model_name
- model_version
- quality_score
- is_active
- created_at
- updated_at
```

## Endpoint yang Dibutuhkan

### POST `/api/face/enroll`

Untuk admin atau pegawai saat pendaftaran wajah.

Request:

```json
{
  "image": "data:image/jpeg;base64,..."
}
```

Response sukses:

```json
{
  "success": true,
  "message": "Wajah berhasil didaftarkan",
  "data": {
    "quality_score": 0.92,
    "embedding_count": 1
  }
}
```

### POST `/api/face/verify`

Opsional jika ingin verifikasi sebelum submit presensi.

Request:

```json
{
  "image": "data:image/jpeg;base64,...",
  "challenge_id": "optional"
}
```

Response:

```json
{
  "success": true,
  "data": {
    "verified": true,
    "similarity": 0.78,
    "threshold": 0.72,
    "liveness_passed": true,
    "quality_score": 0.91
  }
}
```

### POST `/api/absensi/submit`

Endpoint presensi yang sudah ada tetap menjadi keputusan final. Tambahkan
validasi face verification di dalam flow ini sebelum menulis presensi.

Tambahan response gagal yang disarankan:

```json
{
  "success": false,
  "message": "Wajah tidak cocok dengan data pegawai.",
  "face_error_code": "FACE_NOT_MATCH",
  "face_similarity": 0.48,
  "face_threshold": 0.72
}
```

Kode error yang disarankan:

| Code | Makna |
| --- | --- |
| `FACE_NOT_FOUND` | Tidak ada wajah di foto |
| `MULTIPLE_FACES` | Lebih dari satu wajah |
| `FACE_TOO_SMALL` | Wajah terlalu jauh / kecil |
| `IMAGE_QUALITY_LOW` | Gelap, blur, atau kontras rendah |
| `FACE_NOT_ENROLLED` | Pegawai belum punya data wajah |
| `FACE_NOT_MATCH` | Wajah tidak cocok dengan pegawai login |
| `LIVENESS_FAILED` | Terindikasi foto layar / replay |

## Liveness / Anti-Spoof

Minimal tahap 1:

- Tolak foto yang terlalu blur, terlalu gelap, terlalu terang.
- Tolak lebih dari satu wajah.
- Cek EXIF / ukuran / kompresi yang tidak wajar jika memungkinkan.
- Rate limit submit presensi agar brute force wajah tidak mudah.

Tahap 2 yang lebih kuat:

- Challenge acak dari backend sebelum submit: kedip, hadap kiri/kanan, atau
  senyum.
- Challenge berlaku singkat, misalnya 60 detik.
- Backend menyimpan `challenge_id`, `expires_at`, dan status lulus.

## Rekomendasi Model

Pilih salah satu yang realistis untuk server:

- Python service: InsightFace / ArcFace untuk embedding + cosine similarity.
- Node service: ONNX Runtime dengan model face embedding.
- Cloud API jika kebijakan data RS mengizinkan, tetapi perlu persetujuan privasi.

Target awal:

- False accept rendah untuk keamanan presensi.
- Threshold dikalibrasi dari data pegawai internal, jangan asal pakai default.
- Log skor similarity dan quality untuk evaluasi, tetapi jangan tampilkan detail
  sensitif ke user biasa.

## Integrasi Frontend

Frontend saat ini sudah melakukan pre-check lokal. Setelah backend siap:

- Tambahkan status "wajah belum terdaftar" jika backend mengembalikan
  `FACE_NOT_ENROLLED`.
- Tampilkan pesan spesifik dari `face_error_code`.
- Jika memakai challenge, frontend perlu layar singkat sebelum capture:
  "Kedipkan mata" / "Hadap kiri" / "Hadap kanan".

