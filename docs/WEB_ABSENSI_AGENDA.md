# Dokumentasi Web Absensi Agenda

Dokumentasi lengkap untuk fitur **Agenda Management** dan **Absensi Agenda** berbasis web. Mencakup backend acara, detail agenda, rekap absensi, dan budaya kerja.

---

## Daftar Isi

1. [Agenda Management](#1-agenda-management)
   - [Backend Acara (Daftar Agenda)](#11-backend-acara)
   - [Create Agenda](#12-create-agenda)
   - [Detail Agenda (Show)](#13-detail-agenda-show)
   - [Edit Agenda](#14-edit-agenda)
   - [Generate PDF](#15-generate-pdf)
2. [QR Code Absensi](#2-qr-code-absensi)
   - [Generate QR Code](#21-generate-qr-code)
   - [Scan Barcode](#22-scan-barcode)
3. [Rekap Absensi Agenda](#3-rekap-absensi-agenda)
   - [Rekap Per Agenda](#31-rekap-per-agenda)
   - [Update Status Kehadiran](#32-update-status-kehadiran)
   - [Manual Create/Update Absensi](#33-manual-createupdate-absensi)
   - [Export PDF Rekap](#34-export-pdf-rekap)
4. [Budaya Kerja (Rekap)](#4-budaya-kerja-rekap)
   - [Rekapan](#41-rekapan)
   - [Rekap Semua Pegawai](#42-rekap-semua-pegawai)
   - [Export Rekap Pegawai](#43-export-rekap-pegawai)
   - [Detail Rekap Per Pegawai](#44-detail-rekap-per-pegawai)

---

## 1. Agenda Management

### 1.1 Backend Acara

**URL:** `https://sikat.rsaisyiyahsitifatimah.com/backend-acara`

**Controller:** `AgendaController@backendAcara`

**Access:** Authenticated user dengan akses `agenda.backend`

**Fitur:**
- Daftar semua agenda (filter tahun, bulan, status_realisasi)
- Tombol aksi: Detail, PDF, Generate QR, Edit, Hapus
- DataTables server-side

**Routes:**
```php
Route::get('/backend-acara', [AgendaController::class, 'backendAcara'])
```

**Query Parameters:**
| Parameter | Type | Default | Keterangan |
|-----------|------|---------|------------|
| `filter_tahun` | int | tahun sekarang | Filter berdasarkan tahun |
| `filter_bulan` | int | - | Filter berdasarkan bulan (1-12) |
| `filter_status_realisasi` | string | - | Status: belum, sedang, selesai |

**Ajax Endpoint (DataTables):**
```http
GET /backend-acara?filter_tahun=2026&filter_bulan=5&type=datatable
```

**Response Columns:**
| Kolom | Deskripsi |
|-------|-----------|
| `#` | Nomor urut |
| `Nomor Agenda` | Format: RSASF/XXX/III.6.AU/jenis/bulan/tahun |
| `Judul` | Judul agenda |
| `Tanggal` | Tanggal dan waktu mulai |
| `Tempat` | Lokasi agenda |
| `Pimpinan` | Nama pimpinan rapat |
| `Jumlah Terundang` | Jumlah peserta |
| `Status Realisasi` | Badge: Belum, Sedang, Selesai |
| `Aksi` | Detail, PDF, QR, Edit, Hapus |

**Aksi Tombol:**
- **Detail** (`/agenda/show/{id}`) - Lihat detail agenda
- **PDF** (`/agenda/pdf/{id}`) - Download PDF undangan
- **QR** (`/agenda/{id}/qr-code`) - Generate QR Code absensi
- **Edit** (`/agenda/{id}/edit`) - Edit agenda (hanya jika belum selesai)
- **Hapus** - Hapus agenda (hanya jika belum ada absensi)

---

### 1.2 Create Agenda

**URL:** `https://sikat.rsaisyiyahsitifatimah.com/agenda/create`

**Controller:** `AgendaController@create` & `AgendaController@store`

**Access:** Authenticated user dengan akses `agenda.backend`

**Form Fields:**
| Field | Type | Required | Keterangan |
|-------|------|----------|-------------|
| `judul` | text | Ya | Judul agenda |
| `jenis_agenda` | select | Ya | umum, kajian, kegiatan_rs, iht |
| `deskripsi` | textarea | No | Deskripsi agenda |
| `mulai` | datetime | Ya | Tanggal dan waktu mulai |
| `akhir` | datetime | No | Tanggal dan waktu akhir |
| `tempat` | text | No | Lokasi agenda |
| `pimpinan_rapat` | select | Ya | Pilihan pimpinan rapat (from pegawai) |
| `notulen` | select | Ya | Pilihan notulen (from pegawai) |
| `yang_terundang` | multi-select | Ya | Pilihan pegawai yang diundang |
| `foto` | file | No | Foto agenda (jpg, png, max 2MB) |
| `materi.*` | files | No | File materi (pdf, doc, docx, max 2MB) |
| `keterangan` | text | No | Keterangan tambahan |
| `is_realisasi_surat` | checkbox | No | Centang jika realized dari surat keluar |
| `id_surat_keluar` | select | No | Surat keluar yang akan direalisasi |

**Route:**
```php
Route::get('/agenda/create', [AgendaController::class, 'create'])
Route::post('/agenda', [AgendaController::class, 'store'])->name('acara_store')
```

**Auto-generation:**
- `nomor_agenda`: Format `RSASF/XXX/III.6.AU/{jenis}/{bulan}/{tahun}` (auto-increment)
- `status_acara`: Auto berdasarkan waktu (akan_datang, sedang_berlangsung, selesai)
- `created_by`: NIK user yang membuat
- `yang_terundang`: Disimpan sebagai JSON array

**Validasi:**
- `pimpinan_rapat` dan `notulen` harus ada di database server_74
- `yang_terundang.*` harus ada di database server_74 (kecuali "all")

---

### 1.3 Detail Agenda (Show)

**URL:** `https://sikat.rsaisyiyahsitifatimah.com/agenda/show/{id}`

**Controller:** `AgendaController@show`

**Access:** Authenticated user

**Fitur:**
- Detail informasi agenda
- Statistik absensi (jumlah hadir, belum absen)
- Daftar peserta yang sudah absen
- Daftar peserta yang belum absen
- Upload materi tambahan
- Upload dokumentasi
- Simpan kesimpulan (hanya notulen)

**Route:**
```php
Route::get('/agenda/show/{id}', [AgendaController::class, 'show'])->name('acara_show')
```

**Displayed Information:**
| Section | Keterangan |
|---------|------------|
| Info Agenda | Judul, deskripsi, tanggal, tempat |
| Pimpi & Notulen | Nama dan detail pimpinan/notulen |
| Peserta | Jumlah terundang, sudah absen, belum absen |
| Absensi | Daftar yang sudah/h belum absen |
| Materi | File materi yang diupload |
| Dokumentasi | Foto dokumentasi setelah acara |
| Kesimpulan | Kesimpulan dari notulen |
| Undangan | Tombol Generate QR, Download PDF |

**Aksi yang Tersedia:**
| Aksi | Condition | Keterangan |
|------|-----------|-------------|
| Generate QR | Belum selesai + access | Generate QR Code absensi |
| Download PDF | Selalu | Download PDF undangan |
| Edit | Belum selesai + creator/pimpinan/notulen | Edit agenda |
| Upload Materi | Creator only | Upload file materi |
| Upload Dokumentasi | Creator only | Upload foto dokumentasi |
| Simpan Kesimpulan | Notulen only | Simpan kesimpulan rapat |

---

### 1.4 Edit Agenda

**URL:** `https://sikat.rsaisyiyahsitifatimah.com/agenda/{id}/edit`

**Controller:** `AgendaController@edit` & `AgendaController@update`

**Access:** Creator, pimpinan_rapat, atau notulen (dan belum selesai)

**Validasi:**
- Tidak bisa edit jika acara sudah selesai (setelah `akhir`)
- Hanya creator/pimpinan/notulen yang bisa edit

---

### 1.5 Generate PDF

**URL:** `https://sikat.rsaisyiyahsitifatimah.com/agenda/pdf/{id}`

**Controller:** `AgendaController@generateAgendaPDF`

**Access:** Authenticated user

**Route:**
```php
Route::get('/agenda/pdf/{id}', [AgendaController::class, 'generateAgendaPDF'])->name('agenda.pdf')
```

**Output:** PDF surat undangan agenda

**Content:**
- Kop surat dengan logo
- Nomor agenda
- Judul, deskripsi, tanggal, tempat
- Daftar peserta yang terundang (per departemen)
- Tanda tangan digital (barcode pimpinan rapat)

---

## 2. QR Code Absensi

### 2.1 Generate QR Code

**URL:** `https://sikat.rsaisyiyahsitifatimah.com/agenda/{agendaId}/qr-code`

**Controller:** `AgendaController@showQRCodePage`

**Access:** Creator, pimpinan_rapat, notulen, atau user dengan akses `rekap.view`

**Route:**
```php
Route::get('/agenda/{agendaId}/qr-code', [AgendaController::class, 'showQRCodePage'])->name('agenda.qr_code')
```

**Validasi:**
- Acara belum selesai (belum lewat `akhir`)
- QR tersedia 35 menit sebelum waktu mulai
- Tidak bisa generate jika acara sudah berakhir

**QR Content (JSON):**
```json
{
  "agenda_id": 237,
  "token": "abc123..."
}
```

**Mekanisme:**
1. Generate token random 32 karakter
2. Set expiry 2 menit
3. Simpan token ke tabel `agenda_tokens`
4. Generate QR dengan JSON (agenda_id + token)
5. QR auto-refresh via AJAX setiap 2 detik

**Ajax Refresh Endpoint:**
```http
GET /agenda/{id}/generate-qr?agenda_id=237
```

**Response:**
```json
{
  "qrCodeUrl": "data:image/png;base64,...",
  "token": "abc123..."
}
```

---

### 2.2 Scan Barcode

**URL:** `https://sikat.rsaisyiyahsitifatimah.com/scan-barcode/{agendaId?}`

**Controller:** `AbsensiAgendaController@scanBarcode`

**Access:** Authenticated user

**Route:**
```php
Route::get('/scan-barcode/{agendaId?}', [AbsensiAgendaController::class, 'scanBarcode'])->name('absensi.scan')
```

**Fitur:**
- QR Scanner via camera
- Form input manual (agenda_id + token)
- Validasi waktu: bisa scan 15 menit sebelum - 1 jam setelah

**Scan Process:**
1. Kamera scan QR → parse JSON (agenda_id + token)
2. Kirim ke `/proses-scan` (POST)
3. Server validasi token + check-in user
4. Tampilkan hasil (sukses/gagal)

**Post Endpoint (Scan Attendance):**
```http
POST /proses-scan
Content-Type: application/json

{
  "agenda_id": 237,
  "token": "abc123..."
}
```

**Validasi Server:**
1. Token valid & belum expired
2. User terundang di agenda
3. Waktu scan valid (15 menit sebelum - 1 jam setelah)
4. Belum pernah absen untuk agenda ini

**Response Sukses:**
```json
{
  "success": true,
  "message": "Kehadiran berhasil dicatat.",
  "data": {
    "id_absensi_agenda": 123,
    "agenda_id": 237,
    "judul_agenda": "Rapat Koordinasi",
    "waktu_kehadiran": "2026-01-30T09:05:00+07:00"
  }
}
```

---

## 3. Rekap Absensi Agenda

### 3.1 Rekap Per Agenda

**URL:** `https://sikat.rsaisyiyahsitifatimah.com/rekap-absensi?agenda_id={id}`

**Controller:** `AbsensiAgendaController@rekapAbsensi`

**Access:** pimpinan_rapat, notulen, atau user dengan akses `rekap.view`

**Route:**
```php
Route::get('/rekap-absensi', [AbsensiAgendaController::class, 'rekapAbsensi'])->name('rekap-absensi')
```

**Query Parameters:**
| Parameter | Type | Keterangan |
|-----------|------|-------------|
| `agenda_id` | int | Filter untuk agenda tertentu |
| `type` | string | `terundang` untuk DataTables daftar lengkap |

**Fitur:**
- Filter agenda
- Statistik: jumlah undangan, hadir, ijin, cuti, sakit, berhalangan, tidak hadir
- Tabel daftar peserta dengan status
- Edit status kehadiran (pimpinan/notulen)
- Export PDF

**DataTables Ajax (Daftar Peserta):**
```http
GET /rekap-absensi?agenda_id=211&type=terundang
```

**Response:**
```json
{
  "data": [
    {
      "nik": "278.21.11.2018",
      "nama": "Budi Santoso",
      "jabatan": "Staff IT",
      "departemen": "IT",
      "status": "hadir",
      "alasan": null,
      "waktu_kehadiran": "30 Jan 2026 09:05",
      "id_absensi": 123,
      "can_edit": true
    }
  ],
  "recordsTotal": 50,
  "recordsFiltered": 50
}
```

**Status Options:**
| Status | Keterangan |
|--------|-------------|
| `hadir` | Hadir tepat waktu |
| `ijin` | Izin tidak hadir |
| `cuti` | Cuti |
| `sakit` | Sakit |
| `berhalangan` | Berhalangan |
| `tidak_hadir` | Tidak hadir tanpa keterangan |

---

### 3.2 Update Status Kehadiran

**URL:** `https://sikat.rsaisyiyahsitifatimah.com/absensi-agenda/update-status`

**Controller:** `AbsensiAgendaController@updateStatusKehadiran`

**Access:** pimpinan_rapat atau notulen

**Route:**
```php
Route::post('/absensi-agenda/update-status', [AbsensiAgendaController::class, 'updateStatusKehadiran'])
```

**Request Body:**
```json
{
  "id_absensi": 123,
  "status_kehadiran": "ijin",
  "alasan": "Sakit kepala"
}
```

**Validasi:**
- `status_kehadiran`: hadir, ijin, cuti, sakit, berhalangan, tidak_hadir
- `alasan`: optional, max 500 karakter

**Actions:**
- Update status di `absensi_agenda`
- Jika status "hadir" dan belum ada waktu_kehadiran, set waktu sekarang
- Jika status bukan "hadir", set waktu_kehadiran = null
- **Log audit** ke `absensi_agenda_audit`

**Response:**
```json
{
  "success": true,
  "message": "Status kehadiran berhasil diupdate.",
  "data": {
    "status": "ijin",
    "alasan": "Sakit kepala"
  }
}
```

---

### 3.3 Manual Create/Update Absensi

**URL:** `https://sikat.rsaisyiyahsitifatimah.com/absensi-agenda/create-update`

**Controller:** `AbsensiAgendaController@createOrUpdateAbsensi`

**Access:** pimpinan_rapat atau notulen

**Route:**
```php
Route::post('/absensi-agenda/create-update', [AbsensiAgendaController::class, 'createOrUpdateAbsensi'])
```

**Request Body:**
```json
{
  "agenda_id": 211,
  "nik": "278.21.11.2018",
  "status_kehadiran": "ijin",
  "alasan": "Sakit"
}
```

**Validasi:**
- `agenda_id`: wajib, harus ada di tabel agendas
- `nik`: wajib, harus ada di database
- `status_kehadiran`: ijin, cuti, sakit, berhalangan, tidak_hadir (bukan hadir)
- `alasan`: optional

**Actions:**
- Cek apakah sudah ada absensi untuk NIK + agenda_id
- Jika ada: update status dan alasan
- Jika tidak ada: create baru dengan token "MANUAL-{agenda_id}-{nik}-{timestamp}"
- **Log audit** ke `absensi_agenda_audit`

**Response:**
```json
{
  "success": true,
  "message": "Status kehadiran berhasil disimpan."
}
```

---

### 3.4 Export PDF Rekap

**URL:** `https://sikat.rsaisyiyahsitifatimah.com/absensi-agenda/export-pdf?agenda_id={id}`

**Controller:** `AbsensiAgendaController@exportPDF`

**Access:** pimpinan_rapat, notulen, atau user dengan akses `rekap.view`

**Route:**
```php
Route::get('/absensi-agenda/export-pdf', [AbsensiAgendaController::class, 'exportPDF'])
```

**Query Parameters:**
| Parameter | Type | Keterangan |
|-----------|------|-------------|
| `agenda_id` | int | Wajib - ID agenda |

**Output:** PDF daftar hadir agenda

**Content:**
- Kop surat dengan logo
- Info agenda (judul, tanggal, tempat)
- Statistik (total, hadir, ijin, cuti, sakit, berhalangan, tidak hadir)
- Tabel daftar hadir (NIK, Nama, Jabatan, Departemen, Status, Waktu)

**Filename:** `Daftar_Presensi_{judul_agenda}_{tanggal}.pdf`

---

## 4. Budaya Kerja Rekap

### 4.1 Rekapan

**URL:** `https://sikat.rsaisyiyahsitifatimah.com/budayakerja/rekapan`

**Controller:** `BudayaKerjaController@rekapan`

**Access:** Authenticated user

**Route:**
```php
Route::get('/budayakerja/rekapan', [BudayaKerjaController::class, 'rekapan'])->name('budayakerja.rekapan')
```

**Fitur:**
- Daftar penilaian budaya kerja
- Filter tanggal
- DataTables server-side

---

### 4.2 Rekap Semua Pegawai

**URL:** `https://sikat.rsaisyiyahsitifatimah.com/budayakerja/rekap-pegawai`

**Controller:** `BudayaKerjaController@rekapSemuaPegawai`

**Access:** Authenticated user (biasanya HRD/admin)

**Route:**
```php
Route::get('/budayakerja/rekap-pegawai', [BudayaKerjaController::class, 'rekapSemuaPegawai'])->name('budayakerja.rekap_pegawai')
```

**Fitur:**
- Rekapitulasi budaya kerja semua pegawai
- Filter periode (bulan/tahun)
- Statistik per departemen
- Total score per pegawai

**Displayed Data:**
| Kolom | Keterangan |
|-------|------------|
| NIK | Nomor Induk Karyawan |
| Nama | Nama lengkap |
| Jabatan | Jabatan |
| Departemen | Departemen |
| Total Penilaian | Jumlah penilaian yang diisi |
| Score Total | Total skor |
| Rata-rata | Skor rata-rata |
| Aksi | Detail |

---

### 4.3 Export Rekap Pegawai

**URL:** `https://sikat.rsaisyiyahsitifatimah.com/budayakerja/rekap-pegawai/export`

**Controller:** `BudayaKerjaController@exportRekapPegawai`

**Access:** Authenticated user

**Route:**
```php
Route::get('/budayakerja/rekap-pegawai/export', [BudayaKerjaController::class, 'exportRekapPegawai'])->name('budayakerja.rekap_pegawai_export')
```

**Query Parameters:**
| Parameter | Type | Keterangan |
|-----------|------|-------------|
| `bulan` | int | Bulan (1-12) |
| `tahun` | int | Tahun |
| `format` | string | Format export: excel, pdf |

**Output:** File Excel/PDF rekap budaya kerja

---

### 4.4 Detail Rekap Per Pegawai

**URL:** `https://sikat.rsaisyiyahsitifatimah.com/budayakerja/rekap-pegawai/detail/{nik}`

**Controller:** `BudayaKerjaController@rekapPegawaiDetail`

**Access:** Authenticated user

**Route:**
```php
Route::get('/budayakerja/rekap-pegawai/detail/{nik}', [BudayaKerjaController::class, 'rekapPegawaiDetail'])->name('budayakerja.rekap_pegawai_detail')
```

**Fitur:**
- Detail penilaian budaya kerja satu pegawai
- Riwayat penilaian per tanggal
- Breakdown per kriteria penilaian
- Grafik perkembangan

---

## Appendix

### A. Access Control Matrix

| Fitur | Creator | Pimpinan | Notulen | Rekap.View | Admin |
|-------|---------|----------|---------|------------|-------|
| Create Agenda | ✅ | ❌ | ❌ | ❌ | ✅ |
| Edit Agenda | ✅ | ✅ | ✅ | ❌ | ✅ |
| Delete Agenda | ✅ | ❌ | ❌ | ❌ | ✅ |
| View Detail | ✅ | ✅ | ✅ | ✅ | ✅ |
| Generate QR | ✅ | ✅ | ✅ | ✅ | ✅ |
| Update Status | ❌ | ✅ | ✅ | ❌ | ✅ |
| Manual Absensi | ❌ | ✅ | ✅ | ❌ | ✅ |
| View Rekap | ❌ | ✅ | ✅ | ✅ | ✅ |
| Export Rekap | ❌ | ✅ | ✅ | ✅ | ✅ |
| Upload Dok | ✅ | ❌ | ❌ | ❌ | ✅ |
| Kesimpulan | ❌ | ❌ | ✅ | ❌ | ✅ |

### B. Route Summary (Web)

```php
// Agenda Management
GET  /agenda                        → index (calendar view)
GET  /agenda/create                 → create form
POST /agenda                        → store
GET  /agenda/show/{id}              → detail/show
GET  /agenda/{id}/edit              → edit form
PUT  /agenda/{id}                   → update
GET  /agenda/pdf/{id}               → generate PDF
POST /agenda/{id}/upload-materi     → upload materi
POST /agenda/{id}/upload-dokumentasi → upload dokumentasi
POST /agenda/{id}/kesimpulan        → simpan kesimpulan

// Backend
GET  /backend-acara                 → backend list

// QR Code
GET  /agenda/{id}/qr-code          → show QR page
GET  /agenda/{id}/generate-qr      → AJAX generate QR

// Absensi Agenda
GET  /absensi_agenda               → list agenda untuk scan
GET  /scan-barcode/{agendaId?}    → scan barcode page
POST /proses-scan                  → process scan attendance

// Rekap
GET  /rekap-absensi                → rekap absensi
POST /absensi-agenda/update-status → update status
POST /absensi-agenda/create-update → manual create/update
GET  /absensi-agenda/export-pdf    → export PDF

// Budaya Kerja
GET  /budayakerja                  → list
GET  /budayakerja/create           → create form
POST /budayakerja                  → store
GET  /budayakerja/rekapan          → rekapan
GET  /budayakerja/rekap-pegawai    → rekap semua pegawai
GET  /budayakerja/rekap-pegawai/export → export
GET  /budayakerja/rekap-pegawai/detail/{nik} → detail pegawai
GET  /budayakerja/rekap-petugas    → rekap petugas
```

### C. Database Tables

| Table | Fungsi |
|-------|--------|
| `agendas` | Data agenda (judul, tanggal, tempat, terundang) |
| `agenda_tokens` | Token QR (sementara, auto-expire) |
| `absensi_agenda` | Record absensi (nik, agenda_id, status, device_info) |
| `absensi_agenda_audit` | Audit trail perubahan status |
| `budaya_kerja` | Penilaian budaya kerja |
| `items_penilaian` | Template kriteria penilaian |
| `penilaian_harian` | Record penilaian harian |

### D. Model Relationships

```
Agenda
├── hasMany AbsensiAgenda (absensi)
├── belongsTo Pegawai (pimpinan)
├── belongsTo Pegawai (notulen)
├── belongsTo Pegawai (creator)
├── hasMany AgendaMateri (materiFiles, dokumentasiFiles)
└── hasOne Surat (suratKeluar)

AbsensiAgenda
├── belongsTo Agenda
├── belongsTo Pegawai
└── hasMany AbsensiAgendaAudit (auditLogs)

AgendaToken
└── belongsTo Agenda
```