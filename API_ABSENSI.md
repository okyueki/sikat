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

Mengecek dari `temporary_presensi` dulu (presensi via API/web yang belum pulang), lalu `rekap_presensi` jika sudah pulang.

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

Nilai ini diambil dari **config server** (lihat di bawah: *Di mana mengubah radius?*). Response **di-cache 10 menit** di server; client boleh cache di app untuk kurangi panggilan.

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
*Catatan: `status` dengan `" & PSW"` muncul saat **pulang** jika jam pulang aktual sebelum jam pulang jadwal.*

**Response sukses pulang (200):**
```json
{
  "success": true,
  "message": "Presensi pulang berhasil dicatat.",
  "data": {
    "tipe": "pulang",
    "jam_datang": "2026-01-30T07:15:00+07:00",
    "jam_pulang": "2026-01-30T15:05:00+07:00",
    "durasi": "07:50:00",
    "status": "Tepat Waktu & PSW"
  }
}
```
*PSW muncul jika pulang sebelum jam pulang jadwal.*

**Response sukses pulang (closing record tertinggal) (200):**

Terjadi jika ada record datang tanpa pulang dari hari sebelumnya; satu presensi hanya menutup (closing) record tersebut. Pegawai wajib presensi lagi untuk datang hari ini:

```json
{
  "success": true,
  "message": "Presensi pulang (closing) berhasil dicatat. Silakan lakukan presensi datang untuk hari ini.",
  "data": {
    "tipe": "pulang",
    "is_closing": true,
    "jam_datang": "2026-02-23T06:45:00+07:00",
    "jam_pulang": "2026-02-24T07:01:00+07:00",
    "durasi": "24:16:00",
    "status": "Tepat Waktu & PSW"
  }
}
```

*`is_closing: true` menandakan ini closing record tertinggal. Karena 24/02 07:01 sebelum jam pulang jadwal → status PSW.*

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

#### Alur presensi (POST /api/absensi/submit)

Urutan yang dilakukan backend saat user memanggil **POST /api/absensi/submit**:

1. **Auth & pegawai**  
   - Cek token (Sanctum) → ambil user → cari **pegawai** by `username` (NIK).  
   - Tabel: `users` (login), `pegawai` (data pegawai).

2. **Rate limit**  
   - Key: `presensi-api:{ip}:{user_id}`. Maks 10 request / 60 detik.  
   - Tidak baca/tulis DB (cache).

3. **Validasi input**  
   - `image` (wajib), `latitude`, `longitude`, `is_mock_location` (opsional).  
   - Jika `is_mock_location === true` → response **403** (Fake GPS).

4. **Lokasi**  
   - Hitung jarak ke titik presensi (config: `presensi.target_latitude`, `presensi.allowed_radius_meter`).  
   - Jika jarak > radius → response **400**.  
   - Tidak baca tabel; pakai config (bisa dari `.env`).

5. **Jadwal & jam shift hari ini**  
   - **Tabel dibaca:** `jadwal_pegawai` (filter `id` = pegawai, `bulan` = bulan ini 2 digit, `tahun` = tahun ini; kolom `h1`..`h31` untuk shift per hari).  
   - Dari nilai shift hari ini → cari jam masuk/pulang:  
     - **Tabel dibaca:** `jam_jaga` (by `shift`); jika tidak ketemu → **`jam_masuk`** (by `shift`).  
   - Jika tidak ada jadwal shift hari ini → response **400** (Tidak ada jadwal shift hari ini).

6. **Transaksi DB (presensi)** — alur lewat `temporary_presensi`  
   - **Tabel dibaca:** `temporary_presensi` — cari baris pegawai ini dengan `whereDate(jam_datang, hari_ini)`, pakai `lockForUpdate()`.  
   - Jika **sudah ada** dan **`jam_pulang` sudah terisi** → response **400** ("Anda sudah melakukan presensi datang dan pulang hari ini.").  
   - Jika **sudah ada** dan **`jam_pulang` masih null** → **presensi pulang**:  
     - **Tabel ditulis:** `temporary_presensi` — **update** baris: set `jam_pulang`, `durasi`.  
     - **Tabel ditulis:** `rekap_presensi` — **insert** 1 baris (copy dari temporary, dengan status + PSW jika Sabtu).  
     - **Tabel ditulis:** `temporary_presensi` — **delete** baris (sudah pindah ke rekap).  
   - Jika **belum ada** baris hari ini → **presensi datang**:  
     - **Tabel dibaca:** `set_keterlambatan` (ambil toleransi, terlambat1, terlambat2 untuk status/keterlambatan).  
     - **Tabel ditulis:** `temporary_presensi` — **insert** 1 baris: `id`, `shift`, `jam_datang`, `status`, `keterlambatan`, `photo`.  
     - **PSW:** Saat pulang, jika jam pulang aktual < jam pulang jadwal, status ditambah `" & PSW"` (Pulang Sebelum Waktunya).  
     - **File:** foto disimpan di `public/presensi/{nama_file}` (dari `image` request).

**Ringkasan tabel**

| Tabel / sumber        | Dipakai untuk                    | Operasi        |
|-----------------------|-----------------------------------|----------------|
| `users`               | Auth (token → user)               | Baca           |
| `pegawai`             | User → pegawai (NIK)              | Baca           |
| `jadwal_pegawai`      | Shift hari ini (h1..h31)          | Baca           |
| `jam_jaga` / `jam_masuk` | Jam masuk & jam pulang per shift | Baca           |
| `temporary_presensi`  | Datang → insert; pulang → update lalu copy ke rekap | Baca + tulis + hapus |
| `rekap_presensi`      | Riwayat final (setelah pulang)    | Tulis          |
| `set_keterlambatan`   | Status datang (tepat/terlambat)   | Baca           |
| Config `presensi.*`   | Titik & radius lokasi             | Baca           |

**PSW (Pulang Sebelum Waktunya)**  
Jika pegawai melakukan presensi pulang **sebelum** jam pulang jadwal shift, status otomatis ditambah `" & PSW"`, misalnya: `Tepat Waktu & PSW`, `Terlambat Toleransi & PSW`, `Terlambat I & PSW`, `Terlambat II & PSW`. Ini memastikan PSW terdeteksi di dashboard dan laporan.

---

#### Record tertinggal (closing — belum di-closing)

Tujuan `temporary_presensi` adalah mengecek apakah ada presensi yang **belum di-closing** (datang tanpa pulang).

Contoh kasus Budi:
- **23/02 06:45** — presensi datang (tercatat di `temporary_presensi`)
- Lupa presensi pulang
- **24/02 07:01** — presensi pertama: sistem menutup record tertinggal → 24/02 07:01 dicatat sebagai **jam pulang** untuk 23/02, lalu pindah ke `rekap_presensi`. Karena pulang sebelum jam jadwal → status **PSW**.
- **24/02 07:02** — presensi kedua: pegawai **wajib** presensi lagi → 24/02 07:02 dicatat sebagai **jam datang** untuk 24/02.

Jadi satu presensi hanya menutup (closing) record tertinggal. Pegawai wajib melakukan presensi lagi untuk datang hari ini.

---

#### Satu hari: maksimal 1x datang + 1x pulang

- **Aturan:** Dalam satu hari (tanggal yang sama), satu pegawai hanya bisa melakukan **satu kali presensi datang** dan **satu kali presensi pulang**. Tidak bisa absen datang/pulang lebih dari sekali per hari.

- **Proteksi di backend (wajib):**  
  Di **Api\\AbsensiController::submit()** proteksi dilakukan di server:
  - Cari record `temporary_presensi` untuk pegawai + tanggal hari ini (dengan `lockForUpdate()`).
  - Jika sudah ada record dan **`jam_pulang` sudah terisi** → response **400** dengan pesan *"Anda sudah melakukan presensi datang dan pulang hari ini."*
  - Jika **tidak ada** record hari ini, cek dulu record **tertinggal** (jam_datang &lt; hari ini, jam_pulang null). Jika ada → tutup record tersebut (closing/pulang) saja. Pegawai wajib presensi lagi untuk datang hari ini.
  - Dengan demikian, sekalipun front end atau client lain memanggil **POST /api/absensi/submit** berkali-kali, backend tetap menolak presensi kedua (datang lagi atau pulang lagi) setelah satu pasang datang–pulang sudah tercatat.

- **Proteksi di front end (disarankan untuk UX):**  
  Front end **tidak mengunci** aturan bisnis (hanya backend yang otoritatif). Akan tetapi disarankan:
  - Sebelum submit: panggil **GET /api/absensi/status-hari-ini**. Jika `status` = `"selesai"`, tombol presensi bisa **disabled** atau disembunyikan, dan tampilkan pesan bahwa presensi hari ini sudah lengkap.
  - Setelah submit sukses dengan `data.tipe === 'pulang'` (atau status sudah `selesai`), nonaktifkan tombol presensi untuk hari ini.

Dengan demikian: **proteksi utama ada di backend**; front end hanya untuk pengalaman pengguna agar tidak mengirim request yang memang akan ditolak server.

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

---

## Di mana mengubah radius & titik presensi?

- **Cara 1 (disarankan):** File **`.env`** di root project:
  ```env
  PRESENSI_TARGET_LAT=-7.485628943494862
  PRESENSI_TARGET_LNG=112.6527141877153
  PRESENSI_ALLOWED_RADIUS_METER=30
  ```
  Ganti angka radius (meter) dan koordinat sesuai lokasi presensi. Lalu jalankan: `php artisan config:clear`.

- **Cara 2:** Langsung di **`config/presensi.php`** (default dipakai jika tidak ada di .env):
  - `target_latitude`, `target_longitude`, `allowed_radius_meter`.

Nilai ini dipakai oleh: halaman presensi web (form + peta OpenStreetMap), presensi mobile, dan API absensi. Peta di halaman presensi menampilkan **lingkaran radius** (OpenStreetMap/Leaflet) sesuai `allowed_radius_meter`.

---

## API Jadwal Pegawai (nyambung dengan absensi)

Pegawai bisa **melihat dan mengubah jadwal presensi** (shift per hari per bulan) lewat API. Jadwal ini yang dipakai untuk **GET /api/absensi/jadwal-hari-ini** (shift hari ini). Hanya jadwal **milik user yang login** (filter by pegawai).

**Keterkaitan:** Tabel `jadwal_pegawai` dan `jam_masuk` dipakai bersama oleh: (1) **Web** — `Kepegawaian\JadwalController` (CRUD jadwal), (2) **API Jadwal Pegawai** — GET/PUT `/api/jadwal-pegawai`, (3) **API Absensi** — `getShiftHariIni()` untuk jadwal-hari-ini dan submit presensi. Format bulan di DB diseragamkan 2 digit ("01".."12"); API menerima `bulan` 1–12 lalu menyimpan/query dengan format yang sama dengan web.

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/api/jadwal-pegawai?bulan=1&tahun=2026` | List/data jadwal bulan & tahun (jadwal_hari h1–h31 + daftar shift) |
| GET | `/api/jadwal-pegawai/data?bulan=1&tahun=2026` | Sama seperti GET / (data lengkap untuk edit) |
| PUT | `/api/jadwal-pegawai` | Update atau buat jadwal (body: bulan, tahun, h1..h31) |

**GET /api/jadwal-pegawai** — Query: `bulan` (1–12), `tahun`. Default: bulan dan tahun saat ini.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "pegawai": { "id": 123, "nik": "278.21.11.2018", "nama": "Ahmad" },
    "bulan": 1,
    "tahun": 2026,
    "jadwal": { "id": 123, "tahun": 2026, "bulan": 1 },
    "jadwal_hari": { "1": "Pagi2", "2": "Pagi2", "3": "", ... },
    "shifts": [
      { "shift": "Pagi2", "jam_masuk": "07:00:00", "jam_pulang": "15:00:00" }
    ]
  }
}
```

Jika belum ada record untuk bulan/tahun tersebut, `jadwal` = null dan `jadwal_hari` berisi h1–h31 kosong; `shifts` tetap ada untuk dropdown di app.

**PUT /api/jadwal-pegawai** — Body (JSON): `bulan`, `tahun`, dan `h1`..`h31` (nullable string, nilai = nama shift dari tabel jam_masuk). Jika record belum ada, akan dibuat (create); jika sudah ada, di-update.

---

## Cache & rate limit

- **Cache:** Response **GET /api/absensi/config** di-cache server 10 menit. Data jam shift (JamJaga/JamMasuk) yang dipakai untuk jadwal-hari-ini dan submit juga di-cache 1 jam.
- **Rate limit:** Semua endpoint API yang memakai Bearer token (termasuk absensi) dibatasi **90 request per menit per user**. Jika melebihi, server mengembalikan **HTTP 429** (Too Many Requests). Client sebaiknya tidak memanggil API secara berulang dalam waktu singkat; gunakan header `Retry-After` (jika ada) untuk tahu kapan boleh request lagi.

---

## API lain (token sama)

Dengan token dari login di atas, pegawai juga bisa mengakses:
- **API Jadwal Pegawai** — lihat & ubah jadwal presensi per bulan (nyambung dengan jadwal-hari-ini absensi), lihat di atas.
- **API Cuti & Ijin** — pengajuan cuti/ijin dari aplikasi React: [API_CUTI_IJIN.md](API_CUTI_IJIN.md)
- **API Profil** — lihat dan ubah profil pegawai (data pribadi, foto, berkas): [API_PROFIL.md](API_PROFIL.md)
- **API Surat Masuk** — baca daftar dan detail surat masuk (read-only): [API_SURAT_MASUK.md](API_SURAT_MASUK.md)
- **API Absensi Agenda** — scan barcode/QR untuk kehadiran rapat: [API_ABSENSI_AGENDA.md](API_ABSENSI_AGENDA.md)
