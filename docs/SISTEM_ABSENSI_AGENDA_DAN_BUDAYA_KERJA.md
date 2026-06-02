# Dokumentasi Lengkap Sistem Absensi Agenda & Budaya Kerja

Dokumentasi teknis untuk memahami alur program, struktur database, route, controller, dan keterbatasan sistem.

---

## Daftar Isi

1. [Ringkasan Sistem](#1-ringkasan-sistem)
2. [Arsitektur Database](#2-arsitektur-database)
3. [Modul Agenda Management](#3-modul-agenda-management)
4. [Modul Absensi Agenda](#4-modul-absensi-agenda)
5. [Modul Budaya Kerja](#5-modul-budaya-kerja)
6. [Route Definitions](#6-route-definitions)
7. [Identifikasi Kelemahan & Keterbatasan](#7-identifikasi-kelemahan--keterbatasan)
8. [Integrasi Antar Modul](#8-integrasi-antar-modul)
9. [Flow Diagram](#9-flow-diagram)

---

## 1. Ringkasan Sistem

Sistem ini terdiri dari 3 modul utama:

| Modul | Fungsi | URL Backend |
|-------|--------|-------------|
| **Agenda Management** | Membuat, edit, hapus agenda/rapat | `/backend-acara`, `/agenda/*` |
| **Absensi Agenda** | Scan QR, rekap kehadiran rapat | `/rekap-absensi`, `/scan-barcode` |
| **Budaya Kerja** | Penilaian kerapihan & kehadiran | `/budayakerja/*`, `/budayakerja/rekap-pegawai` |

---

## 2. Arsitektur Database

### 2.1 Tabel Utama

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                                 Table: agendas                               │
├─────────────────────────────────────────────────────────────────────────────┤
│ Kolom                  │ Type      │ Deskripsi                              │
├───────────────────────┼───────────┼────────────────────────────────────────┤
│ id                    │ bigint PK │ Primary key                            │
│ nomor_agenda         │ varchar   │ Format: RSASF/XXX/III.6.AU/jenis/bln/thn│
│ judul                 │ varchar   │ Judul agenda                           │
│ jenis_agenda         │ enum      │ umum, kajian, kegiatan_rs, iht          │
│ deskripsi            │ text      │ Deskripsi agenda                       │
│ mulai                │ datetime  │ Waktu mulai                           │
│ akhir                │ datetime  │ Waktu akhir                           │
│ tempat                │ varchar   │ Lokasi agenda                         │
│ pimpinan_rapat       │ varchar   │ NIK pimpinan                           │
│ notulen              │ varchar   │ NIK notulen                           │
│ yang_terundang       │ json      │ Array NIK atau ["all"]                 │
│ status_acara         │ varchar   │ draft, akan_datang, sedang, selesai     │
│ created_by           │ varchar   │ NIK pembuat                             │
│ id_surat_keluar     │ bigint    │ FK ke surat keluar                     │
│ status_realisasi     │ enum      │ belum, sedang, selesai                  │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                             Table: agenda_tokens                            │
├─────────────────────────────────────────────────────────────────────────────┤
│ Kolom                  │ Type      │ Deskripsi                              │
├───────────────────────┼───────────┼────────────────────────────────────────┤
│ id_agenda_tokens     │ bigint PK │ Primary key                            │
│ agenda_id            │ bigint    │ FK ke agendas                          │
│ token                │ varchar   │ Token random (32 char)                  │
│ expiry               │ datetime  │ Waktu kadaluarsa token                 │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                           Table: absensi_agenda                           │
├─────────────────────────────────────────────────────────────────────────────┤
│ Kolom                  │ Type      │ Deskripsi                              │
├───────────────────────┼───────────┼────────────────────────────────────────┤
│ id_absensi_agenda    │ bigint PK │ Primary key                            │
│ nik                   │ varchar   │ NIK pegawai                            │
│ agenda_id            │ bigint    │ FK ke agendas                          │
│ token                │ varchar   │ Token yang digunakan                    │
│ waktu_kehadiran     │ datetime  │ Waktu absen                            │
│ status_kehadiran     │ enum      │ hadir, ijin, cuti, sakit, berhalangan  │
│ alasan               │ text      │ Alasan ketidakhadiran                   │
│ device_token        │ varchar   │ UUID device (localStorage)              │
│ device_model        │ varchar   │ Model HP (Samsung SM-A536B)             │
│ os_version          │ varchar   │ OS (Android 13)                        │
│ browser             │ varchar   │ Browser (Chrome 120)                   │
│ ip_address          │ varchar   │ IP client                              │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                        Table: absensi_agenda_audit                        │
├─────────────────────────────────────────────────────────────────────────────┤
│ Kolom                  │ Type      │ Deskripsi                              │
├───────────────────────┼───────────┼────────────────────────────────────────┤
│ id                    │ bigint PK │ Primary key                            │
│ absensi_id           │ bigint    │ FK ke absensi_agenda (nullable)        │
│ agenda_id            │ bigint    │ FK ke agendas                          │
│ nik                   │ varchar   │ NIK yang berubah                       │
│ aksi                  │ enum      │ create, update_status, manual_create    │
│ status_lama          │ varchar   │ Status sebelum                          │
│ status_baru          │ varchar   │ Status sesudah                          │
│ alasan_perubahan     │ text      │ Alasan perubahan                       │
│ perubahan_oleh       │ varchar   │ NIK yang mengubah                       │
│ perubahan_pada       │ timestamp │ Waktu perubahan                        │
│ ip_address           │ varchar   │ IP yang mengubah                        │
│ device_token        │ varchar   │ Device token                           │
│ device_info          │ varchar   │ JSON: model, os, browser                │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                              Table: budaya_kerja                             │
├─────────────────────────────────────────────────────────────────────────────┤
│ Kolom                  │ Type      │ Deskripsi                              │
├───────────────────────┼───────────┼────────────────────────────────────────┤
│ id                    │ bigint PK │ Primary key                            │
│ tanggal               │ date      │ Tanggal penilaian                       │
│ jam                   │ time      │ Waktu penilaian                        │
│ nik_pegawai         │ varchar   │ NIK yang dinilai                       │
│ nama_pegawai        │ varchar   │ Nama yang dinilai                       │
│ departemen           │ varchar   │ Departemen                             │
│ petugas              │ varchar   │ NIK penilai                           │
│ shift                │ varchar   │ Shift (Pagi/Siang/Malam)              │
│ sepatu               │ tinyint   │ 0 atau 1                              │
│ sabuk                │ tinyint   │ 0 atau 1                              │
│ make_up              │ tinyint   │ 0 atau 1                              │
│ minyak_wangi         │ tinyint   │ 0 atau 1                              │
│ jilbab               │ tinyint   │ 0 atau 1                              │
│ kuku                 │ tinyint   │ 0 atau 1                              │
│ baju                 │ tinyint   │ 0 atau 1                              │
│ celana               │ tinyint   │ 0 atau 1                              │
│ name_tag             │ tinyint   │ 0 atau 1                              │
│ perhiasan            │ tinyint   │ 0 atau 1                              │
│ kaos_kaki            │ tinyint   │ 0 atau 1                              │
│ total_nilai         │ int       │ Total score (max 11)                   │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Relasi Antar Tabel

```
agendas
├── agenda_tokens (1:N)
├── absensi_agenda (1:N)
└── absensi_agenda_audit (1:N)

absensi_agenda
└── absensi_agenda_audit (1:N)

budaya_kerja
└── Pegawai (via nik_pegawai)

Pegawai (external: server_74)
├── absensi_agenda (via nik)
├── budaya_kerja (via nik_pegawai)
└── absensi_agenda_audit (via nik)
```

---

## 3. Modul Agenda Management

### 3.1 Controller: AgendaController
**Path:** `app/Http/Controllers/AgendaController.php`

#### Methods:

| Line | Method | Route | Fungsi |
|------|--------|-------|--------|
| 27 | `index()` | GET `/agenda` | Calendar view semua agenda |
| 81 | `create()` | GET `/agenda/create` | Form create agenda |
| 100 | `store()` | POST `/agenda` | Simpan agenda baru |
| 239 | `edit()` | GET `/agenda/{id}/edit` | Form edit agenda |
| 290 | `update()` | PUT `/agenda/{id}` | Update agenda |
| 421 | `destroy()` | DELETE `/agenda/{id}` | Hapus agenda |
| 429 | `show()` | GET `/agenda/show/{id}` | Detail + statistik absensi |
| 527 | `backendAcara()` | GET `/backend-acara` | DataTables backend |
| 730 | `generateQRCodeBaru()` | - | QR legacy (unused) |
| 743 | `showQRCodePage()` | GET `/agenda/{id}/qr-code` | Generate QR page |
| 823 | `generateQRCode()` | GET `/generate-qrcode` | AJAX refresh QR |
| 893 | `checkTokenStatus()` | GET `/check-token-status` | Cek token used |
| 908 | `generateAgendaPDF()` | GET `/agenda/pdf/{id}` | PDF undangan |
| 1028 | `uploadMateri()` | POST `/agenda/{id}/upload-materi` | Upload file materi |
| 1064 | `uploadDokumentasi()` | POST `/agenda/{id}/upload-dokumentasi` | Upload foto |
| 1105 | `simpanKesimpulan()` | POST `/agenda/{id}/kesimpulan` | Simpan notulen |
| 681 | `sendMessage()` | GET `/agenda/{id}/send-message` | Kirim WA via WAHA |

### 3.2 Alur Create Agenda (store)

```
1. User akses /agenda/create
   └── Ambil data pegawai (pimpinan, notulen, terundang)
   └── Ambil surat keluar yang belum direalisasi

2. User submit form
   ├── Validasi input
   ├── Generate nomor_agenda: RSASF/XXX/III.6.AU/{jenis}/{bulan}/{tahun}
   ├── Handle "all" → simpan semua NIK aktif
   ├── Upload foto (jika ada)
   ├── Upload materi (jika ada)
   └── Simpan ke tabel agendas

3. Redirect ke /agenda/show/{id}
```

### 3.3 Auto-Generated Fields

| Field | Cara Generate |
|-------|--------------|
| `nomor_agenda` | `RSASF/{no_urut}/III.6.AU/{jenis}/{bulan}/{tahun}` |
| `yang_terundang` | JSON array NIK atau ["all"] |
| `status_acara` | Auto: draft → akan_datang → sedang_berlangsung → selesai |
| `created_by` | NIK user yang login |

---

## 4. Modul Absensi Agenda

### 4.1 Controller: AbsensiAgendaController
**Path:** `app/Http/Controllers/AbsensiAgendaController.php`

#### Methods:

| Line | Method | Route | Fungsi |
|------|--------|-------|--------|
| 23 | `index()` | GET `/absensi_agenda` | Daftar agenda untuk scan |
| 55 | `scanBarcode()` | GET `/scan-barcode/{id?}` | Halaman scan QR |
| 96 | `showScanQRCodePage()` | GET `/scan-qr` | Halaman QR scanner |
| 103 | `scanAttendance()` | POST `/proses-scan` | Proses absensi scan |
| 165 | `rekapAbsensi()` | GET `/rekap-absensi` | Rekap absensi |
| 398 | `updateStatusKehadiran()` | POST `/absensi-agenda/update-status` | Update status |
| 465 | `createOrUpdateAbsensi()` | POST `/absensi-agenda/create-update` | Manual input |
| 547 | `exportPDF()` | GET `/absensi-agenda/export-pdf` | Export PDF |

### 4.2 Alur QR Code Generation

```
showQRCodePage() - Line 743
├── Cek akses (creator/pimpinan/notulen/rekap.view)
├── Cek apakah acara sudah selesai ❌ → "Agenda Telah Berakhir"
├── Cek waktu (harus 35 menit sebelum mulai) ❌ → "Belum Waktunya"
└── Generate QR:
    ├── Buat token random 32 char
    ├── Set expiry +2 menit
    ├── Simpan ke agenda_tokens
    └── Render view dengan QR (base64)
```

### 4.3 Alur Scan Attendance

```
scanAttendance() - Line 103
├── Validasi input (agenda_id, token)
├── Validasi token: ada & belum expired ❌ → error
├── Validasi user terundang ❌ → error
├── Validasi waktu scan ❌ → error
├── Cek belum pernah absen ❌ → error
└── Simpan:
    ├── Create absensi_agenda
    ├── Log audit (device_info, ip_address)
    └── Return success
```

### 4.4 Alur Rekap Absensi

```
rekapAbsensi() - Line 165
├── Cek akses (pimpinan/notulen/rekap.view)
├── Ambil daftar agenda (filter jika ada agenda_id)
├── Sync dari pengajuan libur (auto-detect status)
└── Return DataTables:
    ├── type=terundang → daftar lengkap peserta
    └── default → statistik + yang sudah absen
```

---

## 5. Modul Budaya Kerja

### 5.1 Controller: BudayaKerjaController
**Path:** `app/Http/Controllers/Kepegawaian/BudayaKerjaController.php`

#### Methods:

| Line | Method | Route | Fungsi |
|------|--------|-------|--------|
| 44 | `create()` | GET `/budayakerja/create` | Form tambah penilaian |
| 62 | `store()` | POST `/budayakerja` | Simpan penilaian |
| 90 | `index()` | GET `/budayakerja` | List penilaian |
| 95 | `getData()` | GET `/databudayakerja` | DataTables |
| 154 | `destroy()` | DELETE `/budayakerja/{id}` | Hapus penilaian |
| 171 | `bulkDestroy()` | POST `/budayakerja/bulk-destroy` | Hapus massal |
| 199 | `show()` | GET `/budayakerja/{id}` | Detail penilaian |
| 210 | `rekapan()` | GET `/budayakerja/rekapan` | Rekap bulanan |
| 436 | `rekapSemuaPegawai()` | GET `/budayakerja/rekap-pegawai` | Rekap semua |
| 643 | `exportRekapPegawai()` | GET `/budayakerja/rekap-pegawai/export` | Export CSV |
| 862 | `rekapPegawaiDetail()` | GET `/budayakerja/rekap-pegawai/detail/{nik}` | Detail per NIK |
| 950 | `rekapPetugas()` | GET `/budayakerja/rekap-petugas` | Rekap petugas penilai |

### 5.2 Item Penilaian Budaya Kerja

| Item | Bobot | Keterangan |
|------|-------|------------|
| Sepatu | 1 | Wajib |
| Sabuk | 1 | Wajib |
| Make Up | 1 | Rapi berdandan |
| Minyak Wangi | 1 | Wangi |
| Jilbab | 1 | Proper |
| Kuku | 1 | Tidak panjang |
| Baju | 1 | Rapi |
| Celana | 1 | Rapi |
| Name Tag | 1 | Dipakai |
| Perhiasan | 1 | Tidak berlebihan |
| Kaos Kaki | 1 | Dipakai |

**Total Maximum Score: 11**

### 5.3 Status Ketertiban

| Total Pelanggaran | Status |
|-------------------|--------|
| 0 | **Tertib** |
| 1-3 | **Warning** |
| >3 | **Tidak Tertib** |
| Belum dinilai | **Belum Dinilai** |

### 5.4 Alur Rekap Semua Pegawai (rekapSemuaPegawai)

```
rekapSemuaPegawai() - Line 436
├── Ambil filter (tanggal, departemen)
├── Ambil semua pegawai AKTIF (kecuali MIT/MITRA)
├── Inisialisasi array rekap per pegawai
├── Hitung data budaya kerja:
│   ├── Total penilaian
│   ├── Total nilai
│   └── Nilai tertinggi/terendah
├── Hitung data presensi:
│   ├── Jumlah hadir
│   ├── Tepat waktu
│   └── Terlambat
├── Hitung data agenda:
│   ├── Diundang
│   ├── Hadir
│   ├── Ijin
│   ├── Cuti
│   └── Tidak hadir
└── Return view dengan statistik
```

---

## 6. Route Definitions

### 6.1 Agenda Routes

```php
// Agenda Management
GET    /agenda                          → index (calendar)
GET    /agenda/create                   → create (form)
POST   /agenda                          → store
GET    /agenda/show/{id}              → show (detail)
GET    /agenda/{id}/edit              → edit (form)
PUT    /agenda/{id}                   → update
DELETE /agenda/{id}                   → destroy

// Backend
GET    /backend-acara                  → backendAcara (DataTables)

// QR Code
GET    /agenda/{id}/qr-code           → showQRCodePage
GET    /agenda/{id}/generate-qr        → generateQRCode
GET    /generate-qrcode                 → generateQRCode (AJAX)

// PDF & WA
GET    /agenda/pdf/{id}               → generateAgendaPDF
GET    /agenda/{id}/send-message       → sendMessage

// Upload
POST   /agenda/{id}/upload-materi    → uploadMateri
POST   /agenda/{id}/upload-dokumentasi → uploadDokumentasi
POST   /agenda/{id}/kesimpulan         → simpanKesimpulan
```

### 6.2 Absensi Routes

```php
// Scan
GET    /scan-barcode/{agendaId?}      → scanBarcode
GET    /scan-qr                       → showScanQRCodePage
POST   /proses-scan                   → scanAttendance

// List
GET    /absensi_agenda               → index

// Rekap
GET    /rekap-absensi                → rekapAbsensi
POST   /absensi-agenda/update-status  → updateStatusKehadiran
POST   /absensi-agenda/create-update  → createOrUpdateAbsensi
GET    /absensi-agenda/export-pdf     → exportPDF
```

### 6.3 Budaya Kerja Routes

```php
// CRUD
GET    /budayakerja                  → index
GET    /budayakerja/create           → create (form)
POST   /budayakerja                  → store
GET    /budayakerja/{id}            → show
DELETE /budayakerja/{id}            → destroy
GET    /databudayakerja             → getData (DataTables)

// Bulk
POST   /budayakerja/bulk-destroy    → bulkDestroy

// Rekap
GET    /budayakerja/rekapan         → rekapan
GET    /budayakerja/rekap-pegawai   → rekapSemuaPegawai
GET    /budayakerja/rekap-petugas   → rekapPetugas
GET    /budayakerja/rekap-pegawai/export → exportRekapPegawai
GET    /budayakerja/rekap-pegawai/detail/{nik} → rekapPegawaiDetail
```

---

## 7. Identifikasi Kelemahan & Keterbatasan

### 7.1 Modul Agenda

| No | Kelemahan | Dampak | Solusi |
|----|-----------|--------|--------|
| 1 | `yang_terundang` sebagai JSON array | Tidak bisa filter/join SQL | Normalize ke tabel pivot |
| 2 | Tidak ada validasi waktu overlap | Bisa buat agenda bentrok | Tambah validasi jadwal |
| 3 | Tidak ada reminder otomatis | Peserta lupa agenda | Tambahkan scheduled notification |
| 4 | PDF generate lambat | Timeout untuk agenda besar | Cache atau queue job |
| 5 | Tidak ada template agenda | Input manual setiap kali | Tambah template agenda |

### 7.2 Modul Absensi

| No | Kelemahan | Dampak | Solusi |
|----|-----------|--------|--------|
| 1 | QR auto-refresh setiap 2 detik | Boros resource | Tambah countdown, refresh saat expired |
| 2 | Tidak ada check-out | Tidak bisa hitung durasi | Tambah scan kedua |
| 3 | Token 2 menit terlalu singkat | QR expiring cepat | Tambah timeout configurable |
| 4 | Tidak ada GPS validation | Absen dari luar lokasi | Tambah validasi koordinat |
| 5 | Tidak ada push notification | Peserta tidak tahu ada agenda baru | Integrasi FCM |

### 7.3 Modul Budaya Kerja

| No | Kelemahan | Dampak | Solusi |
|----|-----------|--------|--------|
| 1 | Nilai hardcoded (semua 1) | Tidak ada bobot berbeda | Tambah kolom bobot |
| 2 | Tidak ada foto bukti | Sengketa nilai | Tambah upload foto |
| 3 | Petugas bisa menilai dirinya sendiri | Konflik kepentingan | Validasi exclude self |
| 4 | Tidak ada tolerance untuk sakit | Langsung dipotong | Tambah kondisi khusus |
| 5 | RekapAllPegawai lambat | Timeout untuk banyak data | Pagination atau cache |

### 7.4 Umum

| No | Kelemahan | Dampak | Solusi |
|----|-----------|--------|--------|
| 1 | Tidak ada audit trail di Agenda | Tidak tahu siapa ubah | Tambahkan logs |
| 2 | Integrasi presensi terpisah | Data terpisah | JOIN presensi ke absensi agenda |
| 3 | Tidak ada KPI dashboard | Sulit ukur performa | Buat dashboard analytics |
| 4 | Filter masih manual | Input berulang | Auto-filter based on role |

---

## 8. Integrasi Antar Modul

### 8.1 Agenda → Absensi

```php
// Di rekapSemuaPegawai() - Line 536
$agendaIdsInRange = Agenda::whereDate('mulai', '>=', $startDate)
    ->whereDate('mulai', '<=', $endDate)
    ->pluck('id')
    ->toArray();

// Hitung yang_diundang, hadir, ijin, cuti, tidak_hadir per NIK
$absensiAgendaRows = AbsensiAgenda::whereIn('agenda_id', $agendaIdsInRange)
    ->whereIn('nik', $listNik)
    ->get(['nik', 'status_kehadiran']);
```

### 8.2 Budaya Kerja → Absensi

```php
// Di rekapPegawaiDetail() - Line 912
$listPresensi = RekapPresensi::where('id', $pegawai->id)
    ->whereDate('jam_datang', '>=', $startDate)
    ->whereDate('jam_datang', '<=', $endDate)
    ->orderBy('jam_datang')
    ->get();
```

### 8.3 Data Flow Keseluruhan

```
┌─────────────────────────────────────────────────────────────────┐
│                    FRONTEND (Web/Mobile)                         │
│  ├── /backend-acara         → Daftar agenda                     │
│  ├── /agenda/show/{id}     → Detail + Statistik                │
│  ├── /agenda/{id}/qr-code  → Generate QR                      │
│  ├── /scan-barcode         → Scan QR                           │
│  ├── /rekap-absensi        → Rekap kehadiran                   │
│  └── /budayakerja/rekap-pegawai → Rekap budaya kerja           │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      BACKEND CONTROLLERS                          │
│  ├── AgendaController          → CRUD Agenda + QR                │
│  ├── AbsensiAgendaController  → Scan + Rekap                     │
│  └── BudayaKerjaController    → Penilaian + Rekap              │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                        SERVICES                                 │
│  ├── AbsensiAgendaService      → Validasi scan                  │
│  ├── AbsensiAgendaAuditService → Audit trail                   │
│  └── (inline logic)           → Budaya kerja calculation        │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       DATABASE                                   │
│  ├── agendas               → Data agenda                        │
│  ├── agenda_tokens         → Token QR (sementara)              │
│  ├── absensi_agenda       → Record absensi                    │
│  ├── absensi_agenda_audit → Audit trail                       │
│  ├── budaya_kerja          → Penilaian kerapihan               │
│  ├── rekap_presensi       → Data presensi (external)          │
│  └── pegawai (server_74)  → Data pegawai (external)           │
└─────────────────────────────────────────────────────────────────┘
```

---

## 9. Flow Diagram

### 9.1 Flow Create Agenda

```
┌──────────┐     GET /agenda/create     ┌────────────────┐
│  User    │ ───────────────────────────▶│ create()        │
└──────────┘                            │ 1. Ambil pegawai│
                                        │ 2. Ambil surat  │
                                        └────────┬─────────┘
                                                 │ render view
                                                 ▼
┌──────────┐     POST /agenda              ┌────────────────┐
│  User    │ ───────────────────────────▶│ store()         │
└──────────┘     (form submit)            │ 1. Validasi    │
                                        │ 2. Generate no_ │
                                        │    agenda       │
                                        │ 3. Handle "all" │
                                        │ 4. Upload file  │
                                        │ 5. Create agenda│
                                        └────────┬─────────┘
                                                 │ redirect
                                                 ▼
                                        ┌────────────────┐
                                        │ show(id)       │
                                        │ Detail agenda  │
                                        └────────────────┘
```

### 9.2 Flow Absensi Scan

```
┌──────────┐  GET /agenda/{id}/qr-code   ┌────────────────┐
│  Admin   │ ───────────────────────────▶│ showQRCodePage()│
└──────────┘                             │ 1. Cek akses   │
                                        │ 2. Cek waktu   │
                                        │ 3. Generate    │
                                        │    token + QR  │
                                        └────────┬─────────┘
                                                 │ render QR view
                                                 │ (auto-refresh 3min)
                                                 ▼
┌──────────┐  POST /proses-scan           ┌────────────────┐
│  Peserta │ ───────────────────────────▶│ scanAttendance()│
└──────────┘  (scan QR via camera)       │ 1. Validate    │
                                        │    token       │
                                        │ 2. Cek terundang│
                                        │ 3. Cek waktu   │
                                        │ 4. Create      │
                                        │    absensi     │
                                        │ 5. Log audit  │
                                        └────────┬─────────┘
                                                 │ JSON response
                                                 ▼
                                        ┌────────────────┐
                                        │ Mobile App     │
                                        │ "Kehadiran     │
                                        │  berhasil"    │
                                        └────────────────┘
```

### 9.3 Flow Rekap Absensi

```
┌──────────┐  GET /rekap-absensi?agenda_id=X  ┌────────────────┐
│ Pimpinan │ ────────────────────────────────▶│ rekapAbsensi() │
└──────────┘                                  │ 1. Cek akses   │
                                               │ 2. Sync from   │
                                               │    pengajuan   │
                                               │    libur       │
                                               └────────┬────────┘
                                                        │ DataTables
                                                        ▼
┌──────────┐  AJAX /rekap-absensi?type=terundang  ┌────────────────┐
│  View    │ ◀─────────────────────────────────── │ JSON data      │
│  (JS)    │                                    │ - NIK, Nama    │
└──────────┘                                    │ - Jabatan      │
                                               │ - Departemen   │
                                               │ - Status       │
                                               │ - Aksi (edit)  │
                                               └────────────────┘

┌──────────┐  POST /absensi-agenda/update-status ┌────────────────┐
│  User    │ ────────────────────────────────▶│ updateStatus()  │
└──────────┘                                    │ 1. Validasi    │
                                               │ 2. Update      │
                                               │    absensi     │
                                               │ 3. Log audit  │
                                               └────────────────┘
```

### 9.4 Flow Rekap Budaya Kerja

```
┌──────────┐  GET /budayakerja/rekap-pegawai  ┌────────────────┐
│  HRD     │ ────────────────────────────────▶│rekapSemuaPegawai│
└──────────┘                                  │ 1. Filter:      │
                                               │    tanggal, dept│
                                               │ 2. Ambil semua │
                                               │    pegawai     │
                                               │ 3. Join:      │
                                               │    budaya_kerja│
                                               │    presensi    │
                                               │    absensi_    │
                                               │    agenda      │
                                               │ 4. Calculate: │
                                               │    nilai,      │
                                               │    pelanggaran │
                                               │    status      │
                                               └────────┬────────┘
                                                        │
                                                        ▼
┌──────────┐  GET /budayakerja/rekap-pegawai/export ┌────────────────┐
│  HRD     │ ──────────────────────────────────────▶│exportRekapPegawai│
└──────────┘                                        │ → CSV download │
                                                    └────────────────┘
```

---

## Appendix A: Access Control Matrix

| Fitur | Admin | Creator | Pimpinan | Notulen | User Biasa |
|-------|-------|---------|---------|---------|------------|
| Create Agenda | ✅ | ❌ | ❌ | ❌ | ❌ |
| Edit Agenda | ✅ | ✅ | ✅ | ✅ | ❌ |
| Delete Agenda | ✅ | ✅ | ❌ | ❌ | ❌ |
| View Detail | ✅ | ✅ | ✅ | ✅ | ✅ |
| Generate QR | ✅ | ✅ | ✅ | ✅ | ❌ |
| Update Status Absensi | ✅ | ❌ | ✅ | ✅ | ❌ |
| View Rekap Absensi | ✅ | ❌ | ✅ | ✅ | ❌ |
| Input Penilaian BK | ✅ | ✅ | ✅ | ✅ | ✅ |
| View Rekap BK | ✅ | ❌ | ❌ | ❌ | ❌ |

## Appendix B: Status Enum Values

### agendas.status_acara
- `draft` - Agenda baru dibuat
- `akan_datang` - Akan dimulai (mulai > now)
- `sedang_berlangsung` - Sedang berjalan (mulai <= now <= akhir)
- `selesai` - Sudah selesai (akhir < now)

### agendas.status_realisasi
- `belum` - Belum direalisasi
- `sedang` - Sedang direalisasi (sudah ada surat)
- `selesai` - Sudah direalisasi

### absensi_agenda.status_kehadiran
- `hadir` - Hadir tepat waktu
- `ijin` - Izin tidak hadir
- `cuti` - Cuti
- `sakit` - Sakit
- `berhalangan` - Berhalangan
- `tidak_hadir` - Tidak hadir tanpa keterangan

### absensi_agenda_audit.aksi
- `create` - Absensi baru (scan pertama)
- `update_status` - Update status oleh pimpinan/notulen
- `manual_create` - Input manual ketidakhadiran

### budaya_kerja items (0/1)
- `sepatu`, `sabuk`, `make_up`, `minyak_wangi`, `jilbab`
- `kuku`, `baju`, `celana`, `name_tag`, `perhiasan`, `kaos_kaki`

---

## Appendix C: File Paths Reference

### Controllers
```
app/Http/Controllers/
├── AgendaController.php
├── AbsensiAgendaController.php
├── Api/AbsensiAgendaController.php
└── Kepegawaian/BudayaKerjaController.php
```

### Services
```
app/Services/
├── AbsensiAgendaService.php
├── AbsensiAgendaAuditService.php (BARU)
└── (inline logic di controller untuk budaya kerja)
```

### Models
```
app/Models/
├── Agenda.php
├── AgendaToken.php
├── AbsensiAgenda.php
├── AbsensiAgendaAudit.php (BARU)
├── AgendaMateri.php
├── BudayaKerja.php
├── JadwalBudayaKerja.php
└── Pegawai.php (external: server_74)
```

### Views
```
resources/views/
├── event/
│   ├── acara_index.blade.php
│   ├── acara_create.blade.php
│   ├── acara_edit.blade.php
│   ├── acara_show.blade.php
│   ├── backend_acara.blade.php
│   └── generate_qr_code.blade.php
├── absensi_agenda/
│   ├── index.blade.php
│   ├── scan.blade.php
│   ├── scan_qr.blade.php
│   └── rekap.blade.php
└── budayakerja/
    ├── index.blade.php
    ├── create.blade.php
    ├── show.blade.php
    ├── tambah.blade.php
    ├── rekapan.blade.php
    ├── rekap_semua_pegawai.blade.php
    ├── rekap_pegawai_detail.blade.php
    └── rekap_petugas.blade.php
```

---

*Dokumentasi ini dibuat secara otomatis dari analisis code. Jika ada perbedaan dengan implementasi aktual, mohon dikoreksi.*
