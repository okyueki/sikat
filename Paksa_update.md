# Force Update — Versi Lama Tidak Bisa Dipakai

Agar aplikasi versi lama **tidak bisa dipakai** setelah ada update, dipakai mekanisme **backend + app** (bukan Google Play Console saja).

## Ringkasan

| Di mana | Peran |
|--------|--------|
| **Backend API** | Menyediakan endpoint yang mengembalikan **versi minimum app**. Jika app memanggil endpoint ini dan versi app < versi minimum → app menampilkan layar blokir dan hanya mengizinkan buka Play Store. |
| **Project app (ini)** | Saat buka app, memanggil endpoint versi; jika versi app di bawah minimum → tampil layar "Perbarui Aplikasi" dan tombol "Buka Play Store". |
| **Google Play Console** | **Tidak** bisa memaksa versi lama berhenti jalan. Hanya mengontrol siapa yang bisa dapat update (staged rollout, dll.). |

Jadi: **versi lama tidak bisa dipakai** = dikontrol oleh **backend** (nilai `min_app_version`) + **app** (cek versi dan blokir).

---

## 1. Backend API (yang perlu ditambah)

Tambahkan endpoint yang **tidak wajib login** (agar user yang belum login pun bisa dapat paksa update).

### Endpoint

```
GET /api/app/version
```

**Response (200) — JSON:**

```json
{
  "success": true,
  "data": {
    "min_app_version": "1.0.1",
    "force_update_message": "Aplikasi perlu diperbarui ke versi terbaru. Silakan update dari Play Store."
  }
}
```

| Field | Wajib | Keterangan |
|-------|--------|------------|
| `min_app_version` | Ya (kalau mau force update) | Versi minimum yang masih boleh dipakai (format semver: `1.0.0`, `1.0.1`, dll.). App membandingkan versi build (dari HP) dengan ini. Jika versi app **lebih rendah** → tampil layar paksa update. |
| `force_update_message` | Opsional | Teks yang ditampilkan di layar paksa update. Jika kosong, app pakai teks default. |

**Contoh aturan:**

- Jika **tidak** mau paksa update: jangan kirim `min_app_version`, atau kirim string kosong / null.
- Jika mau paksa update ke 1.0.1: set `min_app_version` = `"1.0.1"`. Semua app dengan versi < 1.0.1 (mis. 1.0.0) akan diblokir.

**Contoh implementasi (Laravel):**

- Bisa dari config (file/env), mis. `config('app.min_mobile_version')`.
- Atau dari database (tabel settings) agar bisa diubah tanpa deploy backend.

```php
// Route: GET /api/app/version (tanpa auth)
public function appVersion()
{
    $min = config('app.min_mobile_version', null); // mis. "1.0.1"
    return response()->json([
        'success' => true,
        'data' => [
            'min_app_version' => $min,
            'force_update_message' => 'Aplikasi perlu diperbarui ke versi terbaru. Silakan update dari Play Store.',
        ],
    ]);
}
```

---

## 2. Project app (sudah diimplementasi)

- **API:** `api.getAppVersionConfig()` → GET `/api/app/version`.
- **Komponen:** `ForceUpdateGate` — saat mount memanggil endpoint di atas, baca versi app (dari Capacitor `App.getInfo().version` di native, atau env di web), bandingkan dengan `min_app_version` pakai `compareVersions()`.
- Jika **versi app < min_app_version** → tampil layar penuh "Perbarui Aplikasi" + tombol "Buka Play Store" (link ke `https://play.google.com/store/apps/details?id=com.sikat.mobile`). User tidak bisa lanjut pakai app.
- Jika endpoint gagal/404 atau tidak ada `min_app_version` → app jalan normal (tidak blokir).

Versi app di Android diambil dari **versionName** di `android/app/build.gradle` (setiap release build naikkan versionName/versionCode).

---

## 3. Google Play Console

- Tetap dipakai untuk **publish** versi baru (upload AAB/APK, rollout).
- **Tidak** ada fitur “nonaktifkan versi lama” sehingga app lama tidak bisa jalan. Itu dikontrol oleh **backend + app** seperti di atas.

---

## 4. Alur singkat

1. Anda release versi baru (mis. 1.0.1) di Play Store.
2. Di backend, set `min_app_version` = `"1.0.1"` (lewat config atau settings).
3. User yang masih pakai 1.0.0 saat buka app akan dapat response `min_app_version: "1.0.1"` → app menampilkan layar paksa update dan hanya bisa buka Play Store.
4. Setelah user update ke 1.0.1 (atau lebih tinggi), `compareVersions` tidak lagi < min → app bisa dipakai seperti biasa.

Dengan ini, **versi lama bisa dibuat tidak bisa dipakai** lewat backend (nilai `min_app_version`) dan logic di project app ini; tidak lewat Google Play Console.
