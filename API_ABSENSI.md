# API Absensi (Presensi Pegawai)

API untuk presensi datang/pulang dari aplikasi mobile atau client lain. Autentikasi menggunakan **Laravel Sanctum** (Bearer token).

---

## Autentikasi

### Login (dapat token)

```http
POST /api/login
Content-Type: application/json

{
  "username": "NIK_pegawai",
  "password": "password_user"
}
```

**Response sukses (200):**
```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "token": "1|xxxxxxxxxxxx",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "name": "Nama Pegawai",
      "username": "278.21.11.2018"
    }
  }
}
```

Untuk setiap request API absensi di bawah, tambahkan header:

```http
Authorization: Bearer 1|xxxxxxxxxxxx
```

### Logout (cabut token)

```http
POST /api/logout
Authorization: Bearer 1|xxxxxxxxxxxx
```

---

## Endpoint Absensi

Base URL: `/api/absensi/`

### 1. Jadwal hari ini

```http
GET /api/absensi/jadwal-hari-ini
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "jadwal_ada": true,
    "shift": "Pagi2",
    "jam_masuk": "07:00:00",
    "jam_pulang": "15:00:00"
  }
}
```

---

### 2. Status presensi hari ini

```http
GET /api/absensi/status-hari-ini
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "status": "belum",
    "jam_datang": null,
    "jam_pulang": null
  }
}
```

`status`: `belum` | `datang` | `selesai`

---

### 3. Konfigurasi lokasi (titik presensi & radius)

```http
GET /api/absensi/config
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "target_latitude": -7.485628943494862,
    "target_longitude": 112.6527141877153,
    "allowed_radius_meter": 30
  }
}
```

---

### 4. Submit presensi (datang atau pulang)

Sistem otomatis mendeteksi: jika belum ada presensi hari ini = **datang**, jika sudah ada datang tapi belum pulang = **pulang**.

```http
POST /api/absensi/submit
Authorization: Bearer {token}
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "image": "data:image/jpeg;base64,/9j/4AAQ...",
  "latitude": -7.4856,
  "longitude": 112.6527,
  "is_mock_location": false
}
```

Atau kirim **file** dengan `Content-Type: multipart/form-data`:

- `image`: file gambar (JPEG/PNG, max 2MB)
- `latitude`: number
- `longitude`: number
- `is_mock_location`: boolean (opsional, default false). Jika `true` → response 403 Fake GPS.

**Response sukses datang (200):**
```json
{
  "success": true,
  "message": "Presensi datang berhasil dicatat.",
  "data": {
    "tipe": "datang",
    "jam_datang": "2026-01-30T07:15:00+07:00",
    "status": "Terlambat Toleransi",
    "keterlambatan": "00:15:00"
  }
}
```

**Response sukses pulang (200):**
```json
{
  "success": true,
  "message": "Presensi pulang berhasil dicatat.",
  "data": {
    "tipe": "pulang",
    "jam_datang": "2026-01-30T07:15:00+07:00",
    "jam_pulang": "2026-01-30T15:05:00+07:00",
    "durasi": "07:50:00"
  }
}
```

**Response error Fake GPS (403):**
```json
{
  "success": false,
  "message": "Fake GPS terdeteksi. Presensi tidak dapat dilakukan.",
  "is_fake_gps": true
}
```

**Response error di luar radius (400):**
```json
{
  "success": false,
  "message": "Anda berada di luar radius presensi. Jarak: 150 m (maks 30 m).",
  "distance_meter": 150.5,
  "allowed_radius_meter": 30
}
```

---

### 5. Riwayat presensi

```http
GET /api/absensi/riwayat?bulan=1&tahun=2026
Authorization: Bearer {token}
```

Query: `bulan` (1-12), `tahun`. Default: bulan dan tahun saat ini.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "bulan": 1,
    "tahun": 2026,
    "riwayat": [
      {
        "tanggal": "2026-01-30",
        "jam_datang": "2026-01-30T07:15:00+07:00",
        "jam_pulang": "2026-01-30T15:05:00+07:00",
        "shift": "Pagi2",
        "status": "Terlambat Toleransi",
        "keterlambatan": "00:15:00",
        "durasi": "07:50:00"
      }
    ]
  }
}
```

---

## Ringkasan endpoint

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| POST | `/api/login` | Login, dapat token (no auth) |
| POST | `/api/logout` | Logout, cabut token |
| GET | `/api/absensi/jadwal-hari-ini` | Jadwal shift hari ini |
| GET | `/api/absensi/status-hari-ini` | Status presensi hari ini |
| GET | `/api/absensi/config` | Titik presensi & radius |
| POST | `/api/absensi/submit` | Submit presensi (foto + lokasi + is_mock_location) |
| GET | `/api/absensi/riwayat` | Riwayat presensi (query: bulan, tahun) |

Semua endpoint di bawah `/api/absensi/` dan `/api/logout` memerlukan header `Authorization: Bearer {token}`.
