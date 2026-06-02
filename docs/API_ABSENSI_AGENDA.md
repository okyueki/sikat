# API Absensi Agenda (Scan Barcode/QR)

API untuk **absen kehadiran agenda/rapat** lewat scan barcode/QR dari aplikasi mobile. User membuka kamera → scan QR (berisi `agenda_id` + `token`) → app kirim ke API → validasi → **kehadiran disimpan** ke tabel `absensi_agenda`. Autentikasi **Laravel Sanctum** (Bearer token), sama dengan API lain.

**Undangan agenda di dashboard:** Agar setiap user bisa melihat undangan agenda yang otomatis nampil di dashboard, gunakan **GET /api/dashboard**. Response berisi `undangan_agenda` (agenda yang mengundang user) + `notifikasi` (jumlah verifikasi/disposisi/pengajuan menunggu). Lihat [API Dashboard](#api-dashboard) di bawah.

---

## Autentikasi

- **Login:** `POST /api/login` (body: `username`, `password`) → dapat token.
- Semua request di bawah pakai header: `Authorization: Bearer {token}`.

---

## API Dashboard (undangan agenda + notifikasi)

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/api/dashboard` | Data dashboard: **undangan agenda** (otomatis untuk user yang terundang) + jumlah notifikasi (verifikasi surat, disposisi, pengajuan menunggu) |

**GET /api/dashboard** — Dipanggil saat app membuka dashboard. Setiap user hanya dapat data miliknya (NIK dari token).

**Response (200):**
```json
{
  "success": true,
  "data": {
    "undangan_agenda": [
      {
        "id": 5,
        "judul": "Rapat Koordinasi",
        "deskripsi": "...",
        "mulai": "2026-01-30 09:00:00",
        "akhir": "2026-01-30 11:00:00",
        "tempat": "Ruang Rapat A",
        "pimpinan_rapat": "278.21.11.2001",
        "pimpinan_nama": "Dr. Ahmad",
        "status_label": "Akan Datang",
        "status_class": "info",
        "waktu_info": "30 Jan 2026 09:00",
        "sudah_absen": false,
        "status_kehadiran": null,
        "mulai_format": "30 Jan 2026 09:00",
        "akhir_format": "30 Jan 2026 11:00"
      }
    ],
    "notifikasi": {
      "verifikasi_surat_belum_dibaca": 2,
      "disposisi_belum_dibaca": 1,
      "pengajuan_menunggu": 0,
      "total": 3
    }
  }
}
```

- **undangan_agenda:** Agenda yang mengundang user (hari ini + 7 hari ke depan, atau sedang berlangsung). `sudah_absen` = true jika user sudah scan hadir.
- **notifikasi:** Jumlah untuk badge (verifikasi surat, disposisi, pengajuan libur menunggu atasan). Bukan push notification — app bisa polling GET /api/dashboard atau memanggil saat buka dashboard.

---

## Endpoint Absensi Agenda

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| GET | `/api/absensi-agenda/agenda` | Daftar agenda yang belum berakhir & user terundang |
| GET | `/api/absensi-agenda/agenda/{id}` | **Detail satu agenda** (user harus terundang) |
| GET | `/api/absensi-agenda/agenda/{id}/rekap` | **Daftar hadir** agenda + departemen (hanya pimpinan/notulen) |
| GET | `/api/absensi-agenda/riwayat` | **Riwayat kehadiran** rapat user (agenda yang sudah absen) |
| POST | `/api/absensi-agenda/scan` | Submit hasil scan → simpan kehadiran |
| GET | `/api/absensi-agenda/device-info` | **Deteksi device** (HP model, OS, browser) |
| GET | `/api/absensi-agenda/audit/{agendaId}` | **Audit trail** perubahan status |

---

### 1. GET /api/absensi-agenda/agenda

Daftar agenda yang **belum berakhir** dan user **terundang** (yang_terundang = `all` atau NIK user). Bisa dipakai untuk menampilkan list agenda sebelum user scan QR.

**Query (opsional):**
- `hari` — jangkauan hari ke depan dari hari ini (default 30, min 1, max 90). Hanya agenda dengan `mulai` dalam jangkauan ini.

```http
GET /api/absensi-agenda/agenda
GET /api/absensi-agenda/agenda?hari=14
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "judul": "Rapat Koordinasi Bulanan",
      "deskripsi": "...",
      "mulai": "2026-01-30 09:00:00",
      "akhir": "2026-01-30 11:00:00",
      "tempat": "Ruang Rapat A",
      "pimpinan_rapat": "278.21.11.2001",
      "status": "bisa_scan",
      "sudah_absen": false,
      "status_kehadiran": null,
      "mulai_format": "30 Jan 2026 09:00",
      "akhir_format": "30 Jan 2026 11:00"
    }
  ]
}
```

- `status`: `akan_datang` | `bisa_scan` | `selesai`.
- `sudah_absen`: `true` jika user sudah scan hadir untuk agenda ini, `false` jika belum.
- `status_kehadiran`: `hadir` jika sudah absen, `null` jika belum. Berguna untuk tampilkan badge/tanda di list.
- Response dibatasi **maks 50 agenda**; gunakan `hari` untuk mempersempit jangkauan.

---

### 2. GET /api/absensi-agenda/agenda/{id}

**Detail satu agenda.** Untuk halaman "Detail agenda" di app sebelum user scan. Hanya boleh akses jika user **terundang** (yang_terundang = `all` atau NIK user).

```http
GET /api/absensi-agenda/agenda/5
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "judul": "Rapat Koordinasi Bulanan",
    "deskripsi": "...",
    "mulai": "2026-01-30 09:00:00",
    "akhir": "2026-01-30 11:00:00",
    "tempat": "Ruang Rapat A",
    "pimpinan_rapat": "278.21.11.2001",
    "pimpinan_nama": "Dr. Ahmad",
    "notulen": "278.21.11.2018",
    "notulen_nama": "Budi",
    "status": "bisa_scan",
    "sudah_absen": false,
    "status_kehadiran": null,
    "waktu_kehadiran": null,
    "mulai_format": "30 Jan 2026 09:00",
    "akhir_format": "30 Jan 2026 11:00"
  }
}
```

- **status**: `akan_datang` | `bisa_scan` | `selesai`.
- **sudah_absen**, **status_kehadiran**, **waktu_kehadiran**: terisi jika user sudah scan hadir.

**Response 403:** User tidak diundang. **Response 404:** Agenda tidak ditemukan.

---

### 3. GET /api/absensi-agenda/riwayat

**Riwayat kehadiran rapat** — daftar agenda yang **user sudah absen** (dengan waktu_kehadiran, judul). Berguna untuk layar "Riwayat kehadiran rapat" di app.

**Query (opsional):**
- `per_page` — jumlah per halaman (default 20, max 50)
- `page` — nomor halaman
- `tahun` — filter tahun waktu_kehadiran (contoh: 2026)
- `bulan` — filter bulan waktu_kehadiran (1–12)

```http
GET /api/absensi-agenda/riwayat
GET /api/absensi-agenda/riwayat?tahun=2026&bulan=1&per_page=10
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id_absensi_agenda": 123,
      "agenda_id": 5,
      "judul_agenda": "Rapat Koordinasi Bulanan",
      "tempat": "Ruang Rapat A",
      "waktu_kehadiran": "2026-01-30T09:05:00+07:00",
      "status_kehadiran": "hadir",
      "mulai_agenda": "30 Jan 2026 09:00"
    }
  ],
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

---

### 4. GET /api/absensi-agenda/agenda/{id}/rekap

**Daftar hadir** agenda (nik, nama, **departemen**, waktu_kehadiran, status_kehadiran). **Hanya** user yang **pimpinan_rapat** atau **notulen** agenda tersebut yang boleh akses.

```http
GET /api/absensi-agenda/agenda/5/rekap
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "agenda_id": 5,
    "judul_agenda": "Rapat Koordinasi Bulanan",
    "mulai": "2026-01-30 09:00:00",
    "akhir": "2026-01-30 11:00:00",
    "daftar_hadir": [
      {
        "nik": "278.21.11.2018",
        "nama": "Budi Santoso",
        "departemen": "IT",
        "waktu_kehadiran": "2026-01-30T09:05:00+07:00",
        "status_kehadiran": "hadir"
      }
    ]
  }
}
```

- **departemen:** Departemen pegawai (dari tabel `pegawai.departemen`)

**Response 403:** User bukan pimpinan rapat atau notulen agenda ini. **Response 404:** Agenda tidak ditemukan.

---

### 5. POST /api/absensi-agenda/scan

**Submit hasil scan barcode/QR.** Isi QR di web biasanya JSON: `{"agenda_id": 5, "token": "xxx"}`. App baca itu, lalu kirim `agenda_id` dan `token` ke API ini.

**Body (JSON):**
```json
{
  "agenda_id": 5,
  "token": "string-token-dari-qr",
  "device_token": "uuid-dari-localstorage",
  "ip_address": "192.168.1.100"
}
```

- **agenda_id** (wajib): ID agenda dari QR
- **token** (wajib): Token dari QR
- **device_token** (opsional): UUID dari LocalStorage untuk tracking device
- **ip_address** (opsional): IP address client

**Validasi di server:**
- Token harus ada di `agenda_tokens` dan belum expired.
- User (NIK dari token login) harus terundang di agenda (yang_terundang = `all` atau NIK user).
- Waktu: scan boleh **15 menit sebelum mulai** sampai **1 jam setelah akhir**.
- Belum pernah absen untuk agenda ini (satu user satu kali per agenda).

**Response sukses (201):**
```json
{
  "success": true,
  "message": "Kehadiran berhasil dicatat.",
  "data": {
    "id_absensi_agenda": 123,
    "agenda_id": 5,
    "judul_agenda": "Rapat Koordinasi Bulanan",
    "waktu_kehadiran": "2026-01-30T09:05:00+07:00"
  }
}
```

**Response error:**
- **400** — Token tidak valid/expired, atau agenda belum dimulai / sudah berakhir (pesan di `message`).
- **403** — User tidak diundang.
- **409** — Sudah pernah absen untuk agenda ini.
- **422** — Validasi input gagal (`agenda_id`/`token` wajib, `agenda_id` harus exists).

---

### 6. GET /api/absensi-agenda/device-info

**Deteksi device info** dari User-Agent menggunakan library Jenssegers/Agent. Hasilnya bisa di-cache di LocalStorage mobile app untuk tracking.

```http
GET /api/absensi-agenda/device-info
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "device_model": "Samsung SM-A536B",
    "os_version": "Android 13",
    "browser": "Chrome Mobile 120"
  }
}
```

**Penggunaan di mobile app:**
1. Saat app startup, fetch endpoint ini dan simpan ke LocalStorage
2. Saat scan, kirimkan data dari LocalStorage ke POST `/api/absensi-agenda/scan`

---

### 7. GET /api/absensi-agenda/audit/{agendaId}

**Audit trail** perubahan status absensi agenda. **Hanya** user yang **pimpinan_rapat**, **notulen**, atau punya **akses rekap.view**.

```http
GET /api/absensi-agenda/audit/5
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nik": "278.21.11.2018",
      "nama": "Budi Santoso",
      "aksi": "create",
      "status_lama": null,
      "status_baru": "hadir",
      "alasan": null,
      "perubahan_oleh": "278.21.11.2018",
      "perubahan_oleh_nama": "Budi Santoso",
      "perubahan_pada": "30 Jan 2026 09:05",
      "ip_address": "192.168.1.100",
      "device_info": {
        "model": "Samsung SM-A536B",
        "os": "Android 13",
        "browser": "Chrome Mobile 120"
      }
    },
    {
      "id": 2,
      "nik": "278.21.11.2019",
      "nama": "Ani Wijaya",
      "aksi": "update_status",
      "status_lama": "tidak_hadir",
      "status_baru": "ijin",
      "alasan": "Sakit kepala",
      "perubahan_oleh": "278.21.11.2001",
      "perubahan_oleh_nama": "Dr. Ahmad",
      "perubahan_pada": "30 Jan 2026 10:00",
      "ip_address": "192.168.1.101",
      "device_info": {
        "model": "iPhone 15 Pro",
        "os": "iOS 17",
        "browser": "Safari Mobile 17"
      }
    }
  ]
}
```

- **aksi:** `create` (scan pertama) | `update_status` (ubahan pimpinan/notulen) | `manual_create` (input manual)
- **status_lama:** null untuk aksi `create`/`manual_create`
- **device_info:** detail device yang digunakan (parse dari User-Agent)

**Response 403:** User bukan pimpinan/notulen dan tidak punya akses rekap. **Response 404:** Agenda tidak ditemukan.

---

## Alur di aplikasi mobile

1. User login → dapat Bearer token.
2. (Opsional) GET `/api/absensi-agenda/agenda` → tampilkan daftar agenda. Bisa lanjut GET `/api/absensi-agenda/agenda/{id}` untuk detail sebelum scan.
3. (Opsional) GET `/api/absensi-agenda/device-info` → fetch device info dan simpan ke LocalStorage.
4. User buka kamera, scan QR yang dipajang di lokasi (atau dari layar). QR berisi JSON `{ "agenda_id": 5, "token": "..." }`.
5. App parse QR → POST `/api/absensi-agenda/scan` dengan `agenda_id`, `token`, dan data device dari LocalStorage.
6. Jika sukses → kehadiran tersimpan. Tampilkan pesan sukses di app.
7. (Opsional) GET `/api/absensi-agenda/riwayat` → layar "Riwayat kehadiran rapat". Pimpinan/notulen bisa GET `/api/absensi-agenda/agenda/{id}/rekap` untuk daftar hadir.

---

## Ringkasan

| Method | Endpoint | Auth |
|--------|----------|------|
| GET | `/api/absensi-agenda/agenda` | Bearer |
| GET | `/api/absensi-agenda/agenda/{id}` | Bearer |
| GET | `/api/absensi-agenda/agenda/{id}/rekap` | Bearer |
| GET | `/api/absensi-agenda/riwayat` | Bearer |
| POST | `/api/absensi-agenda/scan` | Bearer |
| GET | `/api/absensi-agenda/device-info` | Bearer |
| GET | `/api/absensi-agenda/audit/{agendaId}` | Bearer |

Token dari login yang sama dipakai untuk API absensi harian (`/api/absensi/*`), cuti/ijin, profil, surat masuk, **dashboard**, dan **absensi agenda** ini.

**Rate limit:** Endpoint absensi agenda (dan semua API ber-auth) dibatasi **90 request per menit per user**. Jika melebihi → **HTTP 429** (Too Many Requests).

---

## Notifikasi push (real-time)

Saat ini **tidak ada** push notification (FCM/OneSignal) dari server. Artinya:
- **Undangan agenda nampil di dashboard:** App memanggil **GET /api/dashboard** saat dashboard dibuka (atau pull-to-refresh); response berisi `undangan_agenda` sehingga setiap user melihat agenda yang mengundang mereka.
- **Badge notifikasi:** Dari response `notifikasi.total` (verifikasi surat, disposisi, pengajuan menunggu).
- Jika kelak ingin notifikasi push saat ada agenda baru yang mengundang user, perlu tambah: penyimpanan device token (FCM), dan trigger kirim push saat agenda dibuat/disimpan.

---

## Rekomendasi pengembangan

### API (Api\AbsensiAgendaController)

| Rekomendasi | Status / Keterangan |
|-------------|----------------------|
| **sudah_absen & status_kehadiran di GET agenda** | ✅ **Sudah:** Tiap item punya `sudah_absen` dan `status_kehadiran` agar app bisa tampilkan mana yang sudah di-scan. |
| **Parameter hari + limit** | ✅ **Sudah:** Query `hari` (1–90, default 30) dan limit 50 agenda untuk hindari response terlalu besar. |
| **Null-safe yang_terundang di scan** | ✅ **Sudah:** `terundang` dipastikan array (bukan null) saat cek undangan. |
| **Pagination GET agenda** | Opsional: Jika butuh lebih dari 50 agenda, bisa tambah `page` & `per_page` (sama seperti API surat masuk/cuti). |
| **Cache daftar agenda** | Opsional: Cache GET agenda per user 1–2 menit (agenda jarang berubah per menit). |

### Web (AbsensiAgendaController / AgendaController)

| Rekomendasi | Keterangan |
|-------------|------------|
| **Refactor logika “user terundang”** | ✅ **Sudah:** Logika dipindah ke **Agenda::userTerundang(string $nik): bool**. Dipakai di API scan, API agenda, Dashboard, web scan, web rekap. |
| **Validasi waktu scan (web)** | ✅ **Sudah:** Di **scanBarcode** (web) pakai `$mulai->copy()->subMinutes(15)` dan `$akhir->copy()->addHour()` agar tidak memodifikasi object Carbon asli. |
| **Response scan (web) konsisten** | ✅ **Sudah:** **scanAttendance** (web) mengembalikan JSON dengan `data` (id_absensi_agenda, judul_agenda, waktu_kehadiran) dan HTTP 201, sama dengan API scan. |

### Umum

| Rekomendasi | Keterangan |
|-------------|------------|
| **Satu service untuk validasi scan** | ✅ **Sudah:** **AbsensiAgendaService::validateScan(Agenda, string $nik, string $token)** mengembalikan array error atau null. Dipakai oleh API scan dan web scanAttendance. |
| **Rate limit khusus POST scan** | Opsional: Batasi mis. 20 request/menit per user untuk POST scan agar hindari spam absen. |

---

## Rekomendasi & inovasi (khusus API Absensi Agenda)

Semua ide di bawah ini **hanya untuk lingkup API Absensi Agenda** (endpoint `/api/absensi-agenda/*` dan pengalaman scan/agenda di app).

### Fitur endpoint

| Ide | Status | Deskripsi | Manfaat |
|-----|--------|-----------|--------|
| **GET /api/absensi-agenda/agenda/{id}** | ✅ **Sudah diterapkan** | Detail satu agenda (setelah cek user terundang): judul, deskripsi, mulai/akhir, tempat, **pimpinan_nama**, notulen_nama, status, sudah_absen. | App bisa tampilkan halaman “Detail agenda” sebelum user scan (sebelum scan). |
| **GET /api/absensi-agenda/riwayat** | ✅ **Sudah diterapkan** | Daftar agenda yang **user sudah absen** (waktu_kehadiran, judul). Query: `page`, `per_page`, `tahun`, `bulan`. | Layar “Riwayat kehadiran rapat” di app. Lihat **§3 GET riwayat** di atas. |
| **GET agenda/{id}/rekap** | ✅ **Sudah diterapkan** | Daftar hadir (nik, nama, waktu_kehadiran, status_kehadiran). Hanya pimpinan_rapat atau notulen. | Pimpinan/notulen lihat rekap dari app. Lihat **§4 GET agenda/{id}/rekap** di atas. |

### Keamanan & validasi

| Ide | Deskripsi | Manfaat |
|-----|-----------|--------|
| **Validasi lokasi (opsional)** | POST scan terima body tambahan `lat`, `lng`. Jika agenda punya field `venue_lat`, `venue_lng`, `venue_radius_m`, tolak scan jika user di luar radius. | Mengurangi absen titip (harus hadir di lokasi). Butuh tambah kolom di tabel agenda. |
| **Rate limit POST scan** | Throttle khusus mis. 20 request/menit per user untuk POST scan (di samping throttle global). | Mengurangi spam/percobaan brute force token. |

### UX & data

| Ide | Deskripsi | Manfaat |
|-----|-----------|--------|
| **Pagination GET agenda** | Tambah query `page` & `per_page` di GET agenda (mis. default 20). | Jika suatu saat agenda > 50, list bisa di-paginate. |
| **Cache GET agenda** | Cache response GET agenda per user 1–2 menit (key mis. `absensi_agenda_list_{nik}`). | Mengurangi query ke DB saat user sering buka list. |
| **Filter GET agenda** | Query `status` = `akan_datang` \| `bisa_scan` \| `selesai` atau `sudah_absen` = true/false. | App bisa filter “Hanya yang belum absen” atau “Yang bisa di-scan sekarang”. |

### Notifikasi & pengingat

| Ide | Deskripsi | Manfaat |
|-----|-----------|--------|
| **Reminder “agenda bisa di-scan”** | Saat waktu masuk window scan (15 menit sebelum mulai), kirim broadcast/Telegram ke user terundang: “Rapat X bisa di-scan sekarang.” | Meningkatkan partisipasi absen tepat waktu. Butuh job/scheduler cek agenda. |
| **Push “agenda besok”** | Notifikasi (jika nanti ada FCM): “Besok ada Rapat Y jam 09:00.” | Pengingat untuk peserta. |

---

Ringkasan prioritas (opsional):
- **Sudah diterapkan:** GET agenda/{id} (detail agenda), GET riwayat (riwayat kehadiran rapat), GET agenda/{id}/rekap (daftar hadir). Dokumen lengkap di §2, §3, §4 di atas.
- **Cepat & ringan:** Rate limit POST scan, cache GET agenda, filter GET agenda.
- **Butuh perubahan DB/job:** Validasi lokasi, reminder bisa-scan, push agenda besok.
