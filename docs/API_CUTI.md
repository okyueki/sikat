# API Cuti & Ijin

API untuk pengajuan **cuti** dan **ijin** dari aplikasi React (atau client lain). Autentikasi memakai **Laravel Sanctum** (Bearer token) — sama dengan [API Absensi](API_ABSENSI.md). Satu kali login bisa dipakai untuk absensi, cuti, dan ijin.

---

## Autentikasi

Sama dengan API Absensi:

- **Login:** `POST /api/login` (body: `username`, `password`) → dapat `token`.
- **Header:** `Authorization: Bearer {token}` untuk semua request di bawah.
- **Logout:** `POST /api/logout` dengan header Bearer.

---

## Endpoint Umum

### Daftar pegawai atasan (untuk dropdown)

Digunakan di form cuti/ijin untuk memilih atasan langsung.

```http
GET /api/pegawai-atasan
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    { "nik": "278.21.11.2018", "nama": "Dr. Ahmad" },
    { "nik": "278.21.11.2019", "nama": "Siti, S.Kep" }
  ]
}
```

Response daftar pegawai atasan **di-cache 10 menit** di server.

---

## API Cuti

Base: `/api/cuti`

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/api/cuti` | Daftar cuti user login |
| GET | `/api/cuti/{id}` | Detail cuti |
| POST | `/api/cuti` | Buat pengajuan cuti |
| PUT | `/api/cuti/{id}` | Update (hanya status Dikirim) |
| DELETE | `/api/cuti/{id}` | Hapus (hanya status Dikirim) |

**Jenis cuti:** `Tahunan`, `Melahirkan`, `Ambil Libur`, `Menikah`.

**Aturan kuota (diverifikasi di server):**
- **Cuti Tahunan:** 12x per tahun (hanya yang status Disetujui), 1 pengajuan maksimal **2 hari**.
- **Ambil Libur:** 1 pengajuan maksimal **2 hari**.
- Melahirkan & Menikah: tidak dibatasi kuota di API.

### GET /api/cuti

Daftar pengajuan cuti dengan **pagination** dan **filter**. Response menyertakan **kuota** cuti Tahunan (sisa, sudah dipakai).

**Query (opsional):**
- `per_page` — jumlah per halaman (default 20, maks 50)
- `page` — nomor halaman (default 1)
- `status` — filter: `Dikirim` | `Disetujui` | `Ditolak`
- `tahun` — filter tahun (berdasarkan tanggal_awal), contoh: `2026`

```http
GET /api/cuti
GET /api/cuti?per_page=10&page=2&status=Dikirim&tahun=2026
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id_pengajuan_libur": 1,
      "kode_pengajuan_libur": "PL-000001",
      "jenis_pengajuan_libur": "Tahunan",
      "nik": "278.21.11.2018",
      "nama_pegawai": "Ahmad",
      "tanggal_awal": "2026-02-01",
      "tanggal_akhir": "2026-02-02",
      "jumlah_hari": 2,
      "nik_atasan_langsung": "278.21.11.2001",
      "nama_atasan": "Kepala Bagian",
      "keterangan": "Cuti tahunan",
      "alamat": "Surabaya",
      "status": "Dikirim",
      "catatan": null,
      "tanggal_dibuat": "2026-01-30T10:00:00+07:00",
      "tanggal_diverifikasi": null
    }
  ],
  "kuota": {
    "tahun": 2026,
    "cuti_tahunan": {
      "kuota_per_tahun": 12,
      "sudah_dipakai": 3,
      "sisa": 9,
      "maks_hari_per_pengajuan": 2
    },
    "ambil_libur_maks_hari_per_pengajuan": 2
  },
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 45,
    "from": 1,
    "to": 20
  }
}
```

### POST /api/cuti

```http
POST /api/cuti
Authorization: Bearer {token}
Content-Type: application/json

{
  "jenis_pengajuan_libur": "Tahunan",
  "tanggal_awal": "2026-02-01",
  "tanggal_akhir": "2026-02-02",
  "jumlah_hari": 2,
  "nik_atasan_langsung": "278.21.11.2001",
  "keterangan": "Cuti tahunan",
  "alamat": "Surabaya"
}
```

- `alamat` opsional.
- `nik` diisi otomatis dari user login.

**Validasi kuota (422):**
- **Tahunan:** `jumlah_hari` maksimal 2; kuota tahun tersebut (status Disetujui) maksimal 12x — jika sudah 12x, response: *"Kuota cuti Tahunan tahun X sudah habis (maksimal 12x per tahun)."*
- **Ambil Libur:** `jumlah_hari` maksimal 2 — jika lebih, response: *"Ambil Libur maksimal 2 hari per pengajuan."*

**Response sukses (201):** `success`, `message`, `data` (objek cuti seperti di list).

**Response validasi (422):** `success: false`, `message`, `errors` (object validasi).

### PUT /api/cuti/{id}

Body sama seperti POST. Hanya bisa update jika `status === "Dikirim"`.  
**Response 400** jika status sudah Disetujui/Ditolak.

### DELETE /api/cuti/{id}

Hanya bisa hapus jika status = Dikirim. **Response 400** jika sudah Disetujui/Ditolak.

---

## API Ijin

Base: `/api/ijin`

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/api/ijin` | Daftar ijin user login |
| GET | `/api/ijin/{id}` | Detail ijin |
| POST | `/api/ijin` | Buat pengajuan ijin (file opsional) |
| PUT | `/api/ijin/{id}` | Update (hanya status Dikirim) |
| DELETE | `/api/ijin/{id}` | Hapus (hanya status Dikirim) |

### GET /api/ijin

Daftar pengajuan ijin dengan **pagination** dan **filter**.

**Query (opsional):** `per_page`, `page`, `status` (Dikirim | Disetujui | Ditolak), `tahun` (filter berdasarkan tanggal_awal). Response menyertakan `data` (array) dan `meta`.

**Response (200):** mirip dengan API cuti, tetapi **tanpa blok `kuota`**. Tiap item menyertakan:

- `foto`: path file di server (nullable)
- `foto_url`: URL lengkap untuk akses file (nullable), contoh: `https://domain.com/storage/uploads/ijin_files/xxx.jpg`

### POST /api/ijin

**Opsi 1 – JSON (tanpa file):**

```http
POST /api/ijin
Authorization: Bearer {token}
Content-Type: application/json

{
  "tanggal_awal": "2026-02-01",
  "tanggal_akhir": "2026-02-01",
  "jumlah_hari": 1,
  "nik_atasan_langsung": "278.21.11.2001",
  "keterangan": "Ijin ke luar kota"
}
```

**Opsi 2 – Multipart (dengan file):**

```http
POST /api/ijin
Authorization: Bearer {token}
Content-Type: multipart/form-data

tanggal_awal=2026-02-01
tanggal_akhir=2026-02-01
jumlah_hari=1
nik_atasan_langsung=278.21.11.2001
keterangan=Ijin ke luar kota
file=@surat_ijin.pdf
```

- **file** opsional; tipe: jpeg, jpg, png, pdf; max 2MB.
- `nik` diisi otomatis dari user login.

**Response sukses (201):** `success`, `message`, `data` (objek ijin termasuk `foto`, `foto_url`).

### PUT /api/ijin/{id}

Body sama seperti POST. Jika dikirim field **file** (multipart), file lama diganti; jika tidak dikirim file, file lama tetap. Hanya bisa update jika status = Dikirim.

### DELETE /api/ijin/{id}

Hapus pengajuan dan file terkait (jika ada). Hanya jika status = Dikirim.

---

## Ringkasan endpoint (Cuti & Ijin)

| Method | Endpoint | Auth |
|--------|----------|------|
| GET | `/api/pegawai-atasan` | Bearer |
| GET | `/api/cuti` | Bearer |
| POST | `/api/cuti` | Bearer |
| GET | `/api/cuti/{id}` | Bearer |
| PUT | `/api/cuti/{id}` | Bearer |
| DELETE | `/api/cuti/{id}` | Bearer |
| GET | `/api/ijin` | Bearer |
| POST | `/api/ijin` | Bearer |
| GET | `/api/ijin/{id}` | Bearer |
| PUT | `/api/ijin/{id}` | Bearer |
| DELETE | `/api/ijin/{id}` | Bearer |
| GET | `/api/persetujuan-libur` | Bearer |
| GET | `/api/persetujuan-libur/{id}` | Bearer |
| PUT | `/api/persetujuan-libur/{id}` | Bearer |

---

## Notifikasi di dashboard (GET /api/dashboard)

Response **GET /api/dashboard** berisi `data.notifikasi` dengan:

- **pengajuan_cuti_ijin_saya_menunggu** — jumlah pengajuan cuti/ijin **milik user** yang statusnya masih **Dikirim** (menunggu persetujuan atasan).
- **pengajuan_cuti_ijin_saya_menunggu_pesan** — teks untuk ditampilkan di UI: *"Pengajuan cuti/ijin menunggu persetujuan atasan"* (hanya ada jika jumlah > 0).
- **pengajuan_menunggu** — jumlah pengajuan cuti/ijin yang **menunggu persetujuan user** (user sebagai atasan, status Dikirim).

Frontend dapat menampilkan reminder/badge: jika `pengajuan_cuti_ijin_saya_menunggu > 0`, tampilkan pesan tersebut (mis. di dashboard atau halaman Cuti/Ijin).

---

## Validasi oleh atasan (API Persetujuan Libur)

Atasan langsung dapat memvalidasi pengajuan cuti/ijin bawahan melalui API ini. Fungsinya **sama dengan web** (`VerifikasiPengajuanLiburController`):

- daftar pengajuan yang `nik_atasan_langsung = user login`
- detail pengajuan bawahan
- verifikasi dengan `status` + `catatan`, lalu server mengisi `tanggal_verifikasi`

Base: `/api/persetujuan-libur`

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/api/persetujuan-libur` | Daftar pengajuan bawahan (cuti + ijin) |
| GET | `/api/persetujuan-libur/{id}` | Detail pengajuan bawahan |
| PUT | `/api/persetujuan-libur/{id}` | Verifikasi (setujui/tolak) |

Status pengajuan:

- `Dikirim` — menunggu validasi atasan
- `Disetujui` — disetujui atasan
- `Ditolak` — ditolak atasan

### GET /api/persetujuan-libur

Daftar pengajuan cuti/ijin dari bawahan yang memilih user login sebagai atasan langsung.

**Query (opsional):**

- `per_page` — jumlah per halaman (default 20, maks 50)
- `page` — nomor halaman (default 1)
- `status` — filter: `Dikirim` | `Disetujui` | `Ditolak`
- `tahun` — filter tahun berdasarkan `tanggal_awal`, contoh: `2026`
- `jenis` — filter: `Cuti` (semua jenis cuti) | `Ijin`

```http
GET /api/persetujuan-libur
GET /api/persetujuan-libur?status=Dikirim&jenis=Cuti&per_page=10&page=1
Authorization: Bearer {token}
```

**Response (200):**

```json
{
  "success": true,
  "data": [
    {
      "id_pengajuan_libur": 12,
      "kode_pengajuan_libur": "PL-000012",
      "jenis_pengajuan_libur": "Tahunan",
      "nik": "278.21.11.2018",
      "nama_pegawai": "Ahmad",
      "tanggal_awal": "2026-02-01",
      "tanggal_akhir": "2026-02-02",
      "jumlah_hari": 2,
      "nik_atasan_langsung": "278.21.11.2001",
      "nama_atasan": "Kepala Bagian",
      "keterangan": "Cuti tahunan",
      "alamat": "Surabaya",
      "status": "Dikirim",
      "catatan": null,
      "foto": null,
      "foto_url": null,
      "tanggal_dibuat": "2026-01-30T10:00:00+07:00",
      "tanggal_diverifikasi": null,
      "dapat_diverifikasi": true
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1,
    "from": 1,
    "to": 1
  }
}
```

Field tambahan di response atasan:

- `nama_pegawai` — nama bawahan yang mengajukan
- `foto` / `foto_url` — lampiran ijin (jika ada)
- `dapat_diverifikasi` — `true` jika status masih `Dikirim` (tombol approve/reject boleh ditampilkan)

### GET /api/persetujuan-libur/{id}

Detail satu pengajuan bawahan. Hanya bisa diakses jika user login adalah atasan langsung pengajuan tersebut.

```http
GET /api/persetujuan-libur/12
Authorization: Bearer {token}
```

**Response (200):** `success`, `data` (objek seperti di list).

**Response (404):** pengajuan tidak ditemukan atau bukan bawahan user login.

### PUT /api/persetujuan-libur/{id}

Proses verifikasi pengajuan. Sama dengan form web di `/verifikasi_pengajuan_libur/detail/{id}`.

Hanya bisa dipanggil jika status saat ini = `Dikirim`.

```http
PUT /api/persetujuan-libur/12
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "Disetujui",
  "catatan": "Disetujui, selamat beristirahat."
}
```

- `status` wajib: `Disetujui` atau `Ditolak`
- `catatan` wajib (sama seperti web)

**Response sukses (200):**

```json
{
  "success": true,
  "message": "Pengajuan berhasil diverifikasi.",
  "data": {
    "id_pengajuan_libur": 12,
    "status": "Disetujui",
    "catatan": "Disetujui, selamat beristirahat.",
    "tanggal_diverifikasi": "2026-02-01T14:30:00+07:00",
    "dapat_diverifikasi": false
  }
}
```

**Response error:**

- **400** — status bukan `Dikirim` (sudah pernah diverifikasi)
- **404** — bukan pengajuan bawahan user login
- **422** — validasi gagal (`status`/`catatan` tidak valid)

### Integrasi dashboard

`GET /api/dashboard` mengembalikan `data.notifikasi.pengajuan_menunggu` — jumlah pengajuan bawahan yang statusnya masih `Dikirim`. Gunakan untuk badge/notifikasi, lalu arahkan user ke halaman persetujuan dan panggil `GET /api/persetujuan-libur?status=Dikirim`.

Equivalent web (referensi):

- `GET /verifikasi_pengajuan_libur` → `GET /api/persetujuan-libur`
- `GET /verifikasi_pengajuan_libur/detail/{id}` → `GET /api/persetujuan-libur/{id}`
- `PUT /verifikasi_pengajuan_libur/update/{id}` → `PUT /api/persetujuan-libur/{id}`

---

## Rate limit

Semua endpoint cuti, ijin, dan pegawai-atasan (seperti API ber-auth lain) dibatasi **90 request per menit per user**. Jika melebihi → **HTTP 429** (Too Many Requests). Client sebaiknya hindari panggilan berulang dalam waktu singkat.

---

## Alur untuk frontend (React/app)

Urutan panggilan API yang disarankan agar alur dan UX konsisten dengan web:

1. **Saat buka halaman Cuti / Ijin**
   - Panggil **GET /api/pegawai-atasan** sekali (untuk dropdown atasan di form buat/edit). Boleh di-cache di state/context; daftar jarang berubah.
   - Panggil **GET /api/cuti** atau **GET /api/ijin** dengan `per_page` dan `page` (mis. `?per_page=20&page=1`) untuk list. Tampilkan `data` dan gunakan `meta` untuk pagination (total, last_page, current_page).

2. **Form buat pengajuan (Cuti)**
   - Isi: jenis cuti, tanggal_awal, tanggal_akhir, jumlah_hari (bisa dihitung dari tanggal di client), nik_atasan_langsung (dari dropdown pegawai-atasan), keterangan, alamat (opsional).
   - Submit: **POST /api/cuti** (JSON). Jika 422, tampilkan `errors` per field. Jika 201, tampilkan sukses dan redirect/refresh list.

3. **Form buat pengajuan (Ijin)**
   - Isi: tanggal_awal, tanggal_akhir, jumlah_hari (hitung dari tanggal), nik_atasan_langsung, keterangan, file (opsional, jpeg/png/pdf max 2MB).
   - Submit: **POST /api/ijin** dengan `Content-Type: multipart/form-data`. Jika 422, tampilkan `errors`. Jika 201, sukses dan refresh list.

4. **Detail / Edit**
   - Untuk tampil detail: **GET /api/cuti/{id}** atau **GET /api/ijin/{id}**. Jika 404 = bukan milik user.
   - **Hanya tampilkan tombol Edit/Hapus jika `data.status === 'Dikirim'`.** Jika status sudah Disetujui/Ditolak, tampilkan hanya tombol Lihat (dan jangan panggil PUT/DELETE).

5. **Edit**
   - **PUT /api/cuti/{id}** atau **PUT /api/ijin/{id}** (ijin: multipart jika ada file baru). Hanya boleh dipanggil jika status = Dikirim; jika tidak, server mengembalikan **400** dengan pesan yang jelas.

6. **Hapus**
   - **DELETE /api/cuti/{id}** atau **DELETE /api/ijin/{id}**. Hanya untuk status Dikirim. Konfirmasi di UI sebelum submit (mis. dialog "Yakin hapus?").

7. **Badge status**
   - Gunakan `data.status` untuk tampilan: **Dikirim** (warning/kuning), **Disetujui** (success/hijau), **Ditolak** (danger/merah). Konsisten dengan web.

8. **Halaman persetujuan atasan**
   - Cek badge dari `GET /api/dashboard` → `data.notifikasi.pengajuan_menunggu`.
   - List pending: `GET /api/persetujuan-libur?status=Dikirim`.
   - Detail: `GET /api/persetujuan-libur/{id}`.
   - Approve/reject: `PUT /api/persetujuan-libur/{id}` dengan `status` (`Disetujui`/`Ditolak`) dan `catatan`.
   - Tampilkan tombol verifikasi hanya jika `data.dapat_diverifikasi === true`.

---

## Rekomendasi UX (frontend)

- **Dropdown atasan:** Isi dari GET /api/pegawai-atasan; tampilkan `nama`, simpan `nik` sebagai value. Cari/type-ahead disarankan jika daftar panjang.
- **Jumlah hari:** Hitung otomatis dari tanggal_awal dan tanggal_akhir (inklusif). Validasi: tanggal_akhir ≥ tanggal_awal; jumlah_hari ≥ 1.
- **Edit/Hapus hanya untuk status "Dikirim":** Sembunyikan atau nonaktifkan tombol Edit/Hapus jika `status !== 'Dikirim'` agar user tidak mengirim request yang akan ditolak (400).
- **Pagination:** Tampilkan "Hal X dari Y" dan tombol prev/next; gunakan `meta.total`, `meta.last_page`, `meta.current_page` dari response list.
- **Pesan error:** Tampilkan `message` dari response 400/422; untuk 422 tampilkan juga `errors` per field di bawah input.
- **Ijin – file:** Field file opsional; jika user ganti file saat edit, kirim field `file` (multipart). Tanpa file = tetap pakai file lama.

---

## Integrasi dengan API Absensi

- **Satu token:** Login sekali (`POST /api/login`) → token dipakai untuk:
  - Absensi: `GET/POST /api/absensi/*`
  - Cuti: `GET/POST/PUT/DELETE /api/cuti`, `/api/cuti/{id}`
  - Ijin: `GET/POST/PUT/DELETE /api/ijin`, `/api/ijin/{id}`
  - Persetujuan atasan: `GET/PUT /api/persetujuan-libur`, `/api/persetujuan-libur/{id}`
  - Dropdown atasan: `GET /api/pegawai-atasan`
- **User:** `username` di response login = NIK pegawai; NIK ini yang dipakai untuk filter cuti/ijin (hanya data milik user yang login).

Di aplikasi React bisa satu layar: setelah login, navigasi ke Absensi, Cuti, Ijin; semua request pakai header `Authorization: Bearer {token}`.
