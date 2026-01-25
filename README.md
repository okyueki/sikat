# SIKAT

Aplikasi internal (berbasis Laravel) untuk operasional dan layanan di lingkungan RS/instansi, termasuk modul inventaris dan tiket/permintaan perbaikan.

## Prasyarat

- PHP (sesuai `composer.json`)
- Composer
- Database (sesuai konfigurasi di `config/database.php` dan `.env`)
- Node.js + npm (opsional, jika build asset diperlukan)

## Menjalankan (lokal/dev)

1. Salin env:
   - `cp .env.example .env`
2. Install dependency:
   - `composer install`
3. Generate app key:
   - `php artisan key:generate`
4. Konfigurasi `.env` (database/queue/mail/telegram, dll).
5. Jalankan:
   - `php artisan serve`

## Konfigurasi: File di webapps SIMRS (foto & berkas)

Sebagian file (contoh: **foto pegawai/inventaris** dan **berkas pegawai**) disajikan oleh server SIMRS (Java) melalui folder `webapps`.
Di **dev** bisa satu server (localhost), sedangkan di **production** sering dipisah (server SIMRS terpisah).

Gunakan 1 variabel berikut agar gampang dipindah-pindah:

- `SIMRS_WEBAPPS_BASE_URL=http://127.0.0.1/webapps`

Contoh production (server SIMRS terpisah):

- `SIMRS_WEBAPPS_BASE_URL=http://172.24.xx.yy:80/webapps`

Opsional (jika struktur inventaris berbeda):

- `INVENTARIS_IMAGE_PATH=inventaris`

Opsional (jika struktur foto pegawai SIMRS berbeda):

- `PEGAWAI_PHOTO_PATH=penggajian/pages/pegawai/photo`

Opsional (agar upload foto masuk langsung ke folder webapps SIMRS **di filesystem server ini**):

- `SIMRS_WEBAPPS_FS_PATH=/path/ke/webapps`

> Catatan: proyek ini menggunakan koneksi database `sik3` untuk beberapa modul (lihat model-model yang memakai `$connection = 'sik3'`).

## Modul: Tiketing Permintaan Perbaikan Inventaris (ITIL-ready)

Saat ini terdapat modul:
- `app/Http/Controllers/Inventaris/PermintaanPerbaikanInventarisController.php`
- `app/Http/Controllers/Inventaris/PerbaikanInventarisController.php`
- `app/Models/PermintaanPerbaikanInventaris.php`
- Views terkait di `resources/views/inventaris/`

### Target standar ITIL (ringkas)

Agar modul tiketing sesuai praktik ITIL minimum, sistem harus memiliki:
- **Klasifikasi tiket**: minimal `Incident` vs `Service Request`
- **Lifecycle status** yang konsisten: `Open → Assigned → In Progress → Resolved → Closed` (+ `Pending Customer/Vendor`, `Cancelled`)
- **Assignment & ownership**: `assignment_group` + `assigned_to`
- **Prioritas berbasis impact/urgency** (bukan sekadar label) dan **SLA response/resolution**
- **Audit trail**: riwayat perubahan status (siapa, kapan, alasan)
- **Worklog/komentar** + attachment evidence (internal/external note)
- **Reporting KPI**: MTTA, MTTR, backlog aging, SLA compliance

### Dokumen desain

- `SARAN_SISTEM_TIKETING.md` adalah **dokumen desain/ide awal** dan **tidak ditinggalkan**.
  - Rekomendasi: **disempurnakan** menjadi rujukan implementasi (DB schema, workflow, UI, notifikasi, SLA).
  - README ini hanya merangkum target; detail implementasi tetap di dokumen tersebut.

## Konvensi teknis (disarankan)

- Business logic yang kompleks sebaiknya dipindah ke `app/Services/` (controller fokus request/response).
- Perubahan status tiket wajib lewat satu “service” agar otomatis menulis status history + notifikasi.
- Hindari `update($request->all())` untuk entity tiket; gunakan whitelist field agar aman dan audit-able.

## Lisensi

Private/internal project.
