# InsightFace UI Polish Design

## Goal

Merapikan tampilan halaman admin InsightFace agar terasa seperti dashboard admin yang lebih profesional, bersih, dan mudah dipindai oleh HRD atau admin operasional tanpa mengubah alur backend, route, atau fitur utama yang sudah selesai.

## Scope

Polish difokuskan pada empat halaman:

- `resources/views/insightface/index.blade.php`
- `resources/views/insightface/logs.blade.php`
- `resources/views/insightface/show.blade.php`
- `resources/views/insightface/enroll_search.blade.php`

Perubahan bersifat presentational:

- merapikan hierarchy visual
- memperkuat header dan CTA
- menyeragamkan kartu statistik
- memperjelas filter dan tabel
- memperhalus empty state, badge, dan action

Di luar scope:

- perubahan logic controller atau query
- perubahan flow enroll / delete / re-enroll
- penambahan tabel database
- redesign total layout aplikasi global

## Approaches Considered

### 1. Light Polish

Hanya membenahi spacing, tombol, badge, dan heading.

Kelebihan:
- sangat aman
- cepat dikerjakan

Kekurangan:
- peningkatan visual terasa kecil
- halaman masih terlihat seperti Bootstrap default

### 2. Clean Admin Dashboard

Merapikan struktur per halaman dengan header yang lebih kuat, stat cards yang lebih konsisten, toolbar filter yang lebih rapi, dan tabel yang lebih nyaman dipindai.

Kelebihan:
- peningkatan visual terasa jelas
- tetap selaras dengan layout project saat ini
- risiko rendah karena tidak mengubah arsitektur

Kekurangan:
- butuh sentuhan CSS lokal per halaman

### 3. Full Redesign

Mengubah struktur menjadi split panel, sticky summary, dan komponen visual baru yang lebih agresif.

Kelebihan:
- hasil paling modern

Kekurangan:
- risiko inkonsistensi dengan halaman lain
- effort lebih besar
- lebih rentan mengganggu pola UI aplikasi yang ada

## Selected Direction

Dipilih pendekatan **Clean Admin Dashboard**.

Alasannya:

- sesuai permintaan user untuk “disempurnakan lagi tampilannya”
- memberi hasil yang cukup terasa tanpa over-engineering
- aman untuk codebase Blade + Bootstrap yang sudah dipakai

## Design

### 1. Shared Visual Language

Semua halaman InsightFace akan memakai pola visual yang sama:

- header section dengan judul, deskripsi pendek, dan action utama
- card dengan radius dan spacing yang lebih rapi
- badge status yang konsisten untuk `match`, `mismatch`, `error`, `skipped`
- warna dipakai seperlunya untuk state penting, bukan untuk semua elemen
- teks sekunder dipakai untuk penjelasan teknis seperti tabel sumber dan env status

### 2. Dashboard Page

Halaman `index` akan diubah menjadi dashboard admin yang lebih mudah discan:

- area atas menjadi hero admin dengan ringkasan fungsi halaman
- kartu statistik dibuat setinggi sama, angka lebih menonjol, label lebih rapi
- status InsightFace dibedakan jelas antara `env nonaktif`, `server online/offline`, dan info base URL
- form pencarian enroll diubah menjadi toolbar yang terasa satu kelompok
- daftar pegawai terdaftar dibuat lebih nyaman dibaca dengan metadata sekunder
- panel log terbaru dibuat lebih bersih dengan emphasis pada status dan skor

### 3. Logs Page

Halaman `logs` akan diubah menjadi halaman monitoring:

- header diberi penjelasan singkat agar user paham halaman ini untuk audit log
- filter dikelompokkan dalam card toolbar yang lebih rapat dan rapi
- statistik mini atau info ambang lolos ditampilkan dekat filter atau footer tabel
- tabel dibuat lebih mudah dipindai dengan badge status yang lebih halus
- link ke detail pegawai dan detail log dipertahankan, tetapi affordance aksinya diperjelas

### 4. Detail Pegawai Page

Halaman `show` akan dibuat lebih kuat secara hierarchy:

- panel kiri berisi identitas pegawai, status enroll, dan action penting
- action destruktif dipisah jelas dari action utama
- card enroll / re-enroll dibuat lebih informatif dan terasa aman dipakai
- status saat `INSIGHTFACE_ENABLED=false` tetap tampil jelas sebagai peringatan operasional
- panel riwayat verifikasi di kanan tetap dominan karena itu informasi utama untuk audit

### 5. Enroll Search Page

Halaman pencarian enroll akan dipoles agar terasa seperti halaman kerja admin:

- header dan deskripsi dibuat lebih jelas
- form cari dibuat lebih kokoh dan langsung terbaca
- hasil pencarian diberi empty state yang lebih ramah
- status `sudah / belum enroll` dibuat lebih jelas secara visual
- tombol aksi akan konsisten dengan halaman lain

## Error Handling And States

Polish UI harus tetap mendukung state berikut:

- alert sukses dan error session tetap terlihat jelas
- state kosong pada tabel tidak terasa “patah”
- state InsightFace disabled tetap terlihat menonjol dan informatif
- tombol aksi destruktif tetap meminta konfirmasi seperti sekarang

## Testing Strategy

Verifikasi dilakukan dengan:

- compile Blade via `php artisan view:cache`
- cek lint/diagnostic untuk file Blade yang diubah bila tersedia
- smoke test route render dasar jika tidak terhalang error lama yang tidak terkait
- review visual struktur markup agar tidak memutus form, modal, pagination, atau route existing

## Risks And Mitigations

- Risiko: polish terlalu ramai dan keluar dari gaya aplikasi.
  Mitigasi: tetap pakai Bootstrap project yang ada, hanya memperbaiki hierarchy dan spacing.

- Risiko: action penting tenggelam karena terlalu banyak elemen dekoratif.
  Mitigasi: satu CTA primer per section, warna status dibatasi.

- Risiko: perubahan markup mengganggu modal detail log.
  Mitigasi: partial modal dan script tetap dipakai tanpa perubahan perilaku.
