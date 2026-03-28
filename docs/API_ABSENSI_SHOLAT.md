# Dokumentasi API Absensi Sholat (Frontend)

API untuk absensi sholat di masjid RS berbasis **QR code** dan **geolocation**. User harus login, scan QR yang dipasang di masjid, dan mengirim koordinat GPS; absensi hanya valid jika berada dalam radius masjid.

---

## Autentikasi

Semua endpoint membutuhkan **Bearer token** (Laravel Sanctum).

- **Login**: `POST /api/login` dengan body `username` (NIK) dan `password`. Response berisi `token`.
- **Header** untuk setiap request: `Authorization: Bearer {token}`

Contoh setelah login:

```http
Authorization: Bearer 1|abc123...
```

---

## Base URL

Ganti dengan domain Anda, misalnya: `https://sikat.rsaisyiyahsitifatimah.com/api`

| Endpoint | Method | Keterangan |
|----------|--------|------------|
| `/absensi-sholat/config` | GET | Config lokasi masjid (lat, lng, radius) |
| `/absensi-sholat/scan` | POST | Submit absen (token QR + GPS) |
| `/absensi-sholat/riwayat` | GET | Riwayat absen user (per bulan) |
| `/absensi-sholat/rekap-bulanan` | GET | Rekap total & per jenis sholat (per bulan) |

---

## 1. GET `/absensi-sholat/config`

Mengambil koordinat masjid dan radius (meter) untuk validasi di client (opsional: bisa cek jarak sebelum kirim scan).

**Request:** Tidak ada body. Cukup header `Authorization`.

**Response 200:**

```json
{
  "success": true,
  "data": {
    "target_latitude": -7.485628943494862,
    "target_longitude": 112.6527141877153,
    "allowed_radius_meter": 50
  }
}
```

**Kegunaan di frontend:** Bisa hitung jarak user ke masjid (Haversine) dan tampilkan pesan "Anda di luar radius" sebelum memanggil `POST /scan`, atau langsung kirim ke server dan tampilkan pesan error dari response.

---

## 2. POST `/absensi-sholat/scan`

Submit absensi sholat. Wajib: token (dari QR), latitude, longitude. Opsional: jenis sholat, is_mock_location.

**Request body (JSON atau form):**

| Field | Tipe | Wajib | Keterangan |
|-------|------|-------|------------|
| `token` | string | Ya | Isi QR yang di-scan (satu token untuk semua orang) |
| `latitude` | number | Ya | Koordinat latitude (-90 s/d 90) |
| `longitude` | number | Ya | Koordinat longitude (-180 s/d 180) |
| `jenis_sholat` | string | Tidak | Nilai: `subuh`, `dzuhur`, `ashar`, `maghrib`, `isya`, `sunnah`. Default: `sunnah` |
| `is_mock_location` | boolean | Tidak | Jika `true`, request ditolak (fake GPS) |

**Response 200 (sukses):**

```json
{
  "success": true,
  "message": "Absensi sholat berhasil dicatat.",
  "data": {
    "waktu_absen": "2026-03-11T12:30:00+07:00",
    "jenis_sholat": "dzuhur"
  }
}
```

**Response 400 – Token tidak valid / kedaluwarsa:**

```json
{
  "success": false,
  "message": "Token tidak valid atau telah kedaluwarsa."
}
```

**Response 400 – Di luar radius:**

```json
{
  "success": false,
  "message": "Anda berada di luar radius masjid. Jarak: 120 m (maks 50 m).",
  "distance_meter": 120.5,
  "allowed_radius_meter": 50
}
```

**Response 403 – Fake GPS:**

```json
{
  "success": false,
  "message": "Fake GPS terdeteksi. Absensi tidak dapat dilakukan.",
  "is_fake_gps": true
}
```

**Response 409 – Sudah absen untuk jenis sholat hari ini:**

```json
{
  "success": false,
  "message": "Anda sudah melakukan absensi sholat dzuhur untuk hari ini."
}
```

**Response 422 – Validasi gagal:**

```json
{
  "success": false,
  "message": "Data tidak valid.",
  "errors": {
    "token": ["Token dari QR wajib."],
    "latitude": ["Lokasi latitude wajib."]
  }
}
```

**Response 429 – Terlalu banyak request:**

```json
{
  "success": false,
  "message": "Terlalu banyak percobaan. Coba lagi nanti."
}
```

**Response 401:** User belum login atau token expired.

---

## 3. GET `/absensi-sholat/riwayat`

Riwayat absen sholat user yang login, per bulan.

**Query string:**

| Parameter | Tipe | Wajib | Default | Keterangan |
|-----------|------|-------|---------|------------|
| `bulan` | number | Tidak | Bulan saat ini (1–12) | Bulan |
| `tahun` | number | Tidak | Tahun saat ini | Tahun |

**Contoh:** `GET /api/absensi-sholat/riwayat?bulan=3&tahun=2026`

**Response 200:**

```json
{
  "success": true,
  "data": {
    "bulan": 3,
    "tahun": 2026,
    "riwayat": [
      {
        "tanggal": "2026-03-11",
        "waktu_absen": "2026-03-11T12:30:00+07:00",
        "jenis_sholat": "dzuhur"
      },
      {
        "tanggal": "2026-03-10",
        "waktu_absen": "2026-03-10T05:15:00+07:00",
        "jenis_sholat": "subuh"
      }
    ]
  }
}
```

---

## 4. GET `/absensi-sholat/rekap-bulanan`

Rekap kehadiran user: total dan per jenis sholat untuk satu bulan.

**Query string:** Sama seperti riwayat (`bulan`, `tahun`, default bulan/tahun ini).

**Contoh:** `GET /api/absensi-sholat/rekap-bulanan?bulan=3&tahun=2026`

**Response 200:**

```json
{
  "success": true,
  "data": {
    "bulan": 3,
    "tahun": 2026,
    "total_kehadiran": 15,
    "per_jenis_sholat": {
      "subuh": 5,
      "dzuhur": 6,
      "ashar": 2,
      "maghrib": 1,
      "sunnah": 1
    }
  }
}
```

---

## Alur di Frontend (Portal Absensi)

1. **Login**  
   Panggil `POST /api/login`, simpan `token` (untuk header `Authorization: Bearer ...`).

2. **Halaman scan**  
   - User membuka kamera / scanner QR untuk scan QR yang dipasang di masjid.  
   - Isi QR = **string token** (bukan JSON). Simpan ke state (mis. `scannedToken`).

3. **Minta lokasi**  
   - Gunakan Geolocation API: `navigator.geolocation.getCurrentPosition`.  
   - Di Android WebView, pastikan izin lokasi dan (jika ada) deteksi mock location; kirim `is_mock_location: true` jika terdeteksi fake GPS.

4. **Kirim absensi**  
   - Panggil `POST /api/absensi-sholat/scan` dengan:
     - `token`: nilai dari QR
     - `latitude`, `longitude`: dari `getCurrentPosition`
     - `jenis_sholat`: pilihan user (atau default `sunnah`)
     - `is_mock_location`: `true`/`false` jika tersedia

5. **Tampilkan hasil**  
   - Jika `success: true`, tampilkan pesan sukses dan waktu/jenis sholat.  
   - Jika 400/403/409, tampilkan `message` ke user.  
   - Jika 429, minta user coba lagi nanti.

6. **Riwayat & rekap**  
   - Riwayat: `GET /api/absensi-sholat/riwayat?bulan=...&tahun=...`  
   - Rekap: `GET /api/absensi-sholat/rekap-bulanan?bulan=...&tahun=...`

---

## Contoh Kode (JavaScript)

**Login dan simpan token:**

```javascript
const res = await fetch('/api/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ username: 'NIK_PEGAWAI', password: '***' })
});
const { token } = await res.json();
// Simpan token (localStorage / state) untuk header Authorization
```

**Ambil config (opsional):**

```javascript
const configRes = await fetch('/api/absensi-sholat/config', {
  headers: { 'Authorization': `Bearer ${token}` }
});
const { data } = await configRes.json();
// data.target_latitude, data.target_longitude, data.allowed_radius_meter
```

**Submit scan setelah dapat token QR dan koordinat:**

```javascript
const scanRes = await fetch('/api/absensi-sholat/scan', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    token: scannedToken,        // dari QR
    latitude: position.coords.latitude,
    longitude: position.coords.longitude,
    jenis_sholat: 'dzuhur',     // optional
    is_mock_location: false     // optional
  })
});
const result = await scanRes.json();
if (result.success) {
  console.log('Absen berhasil:', result.data.waktu_absen);
} else {
  console.error(result.message);
}
```

**Rekap bulanan:**

```javascript
const rekapRes = await fetch(
  '/api/absensi-sholat/rekap-bulanan?bulan=3&tahun=2026',
  { headers: { 'Authorization': `Bearer ${token}` } }
);
const { data } = await rekapRes.json();
console.log('Total kehadiran:', data.total_kehadiran);
console.log('Per jenis:', data.per_jenis_sholat);
```

---

## Catatan

- **QR berisi hanya token** (string). Bukan JSON. Admin generate token di backend (menu **Event → Token QR Absensi Sholat**), lalu QR di-print dan dipasang di masjid.
- Satu user **hanya bisa satu kali absen per jenis sholat per hari**. Misalnya satu kali dzuhur per hari.
- **Rate limit:** maksimal 15 request scan per menit per IP/user; jika lebih, response 429.
- Waktu di server memakai timezone **Asia/Jakarta**.
