# WhatsApp Broadcast System Documentation

Sistem pengiriman pesan WhatsApp menggunakan Qontak API untuk RS Aisyiyah Sitifatimah.

## 📋 Overview

Sistem ini menggunakan **Qontak API** dengan HMAC authentication untuk mengirim pesan WhatsApp melalui template yang sudah di-approve oleh Meta.

### Fitur Utama:
- ✅ Multi-variable template support ({{1}}, {{2}}, {{3}}, {{4}})
- ✅ Delivery status tracking (webhook + polling)
- ✅ Database logging untuk semua pesan
- ✅ Rate limiting (1 detik antar pesan)
- ✅ Multiple template per fitur (dinamis)

---

## 🏗️ Struktur Kode

```
app/
├── Services/
│   └── WhatsAppService.php          # Core service untuk Qontak API
├── Http/Controllers/
│   ├── JadwalBudayaKerjaController.php   # Contoh implementasi
│   └── QontakWebhookController.php       # Webhook handler
├── Models/
│   └── WhatsappLog.php              # Model untuk logging
├── Console/Commands/
│   └── SyncWhatsappStatus.php       # Command untuk sync status
└── database/migrations/
    └── *_create_whatsapp_logs_table.php  # Tabel logging
```

---

## ⚙️ Environment Configuration

File `.env` - **Global credentials** (sama untuk semua fitur):

```env
# Qontak API Credentials (Global)
QONTAK_CLIENT_ID=your_client_id
QONTAK_CLIENT_SECRET=your_client_secret
QONTAK_CHANNEL_ID=your_channel_id
```

**Note**: Template ID **tidak** di `.env` karena setiap fitur punya template berbeda.

---

## 📱 Cara Membuat Template Baru di Qontak

### Step 1: Login ke Qontak Dashboard
- URL: https://dashboard.qontak.com
- Login dengan akun admin

### Step 2: Buat Template
1. Menu → **Templates** → **Create Template**
2. Pilih **WhatsApp** sebagai channel
3. Isi informasi:
   - **Template Name**: `nama_fitur` (e.g., `jadwal_budaya_kerja`)
   - **Category**: `UTILITY` atau `MARKETING`
   - **Language**: `Indonesian`

### Step 3: Desain Template
Gunakan format variable `{{1}}`, `{{2}}`, etc:

```
Assalamualaikum Wr Wb 
Ini Adalah Pengingat Jadwal Budaya Kerja
Kami mengingatkan Bapak/Ibu:
Nama: {{1}}
Terjadwal di Tanggal: {{2}}

Melakukan Penilaian Budaya Kerja di Shift: {{3}}
Jam anda Bertugas yaitu Pukul : {{4}} WIB
 
Selamat Bertugas dan SEMANGATT
Matur Nuwun
```

**Catatan**: 
- Maksimal 10 variables per template
- Setiap `{{n}}` akan diganti dengan data dari kode

### Step 4: Submit & Wait Approval
- Klik **Submit**
- Tunggu approval dari Meta (1-24 jam)
- Status akan berubah dari `PENDING` → `APPROVED`

### Step 5: Copy UUID
- Setelah approved, copy **UUID** template
- Contoh: `bbbcc349-de87-4b48-8e30-ebf389d329a6`

---

## 🚀 Cara Implementasi Fitur Baru

### Step 1: Tambah Template ID di Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;

class FiturBaruController extends Controller
{
    protected WhatsAppService $waService;

    // Template ID untuk fitur ini (dinamis)
    protected string $templateIdFitur = 'uuid-template-anda-disini';

    public function __construct(WhatsAppService $waService)
    {
        $this->waService = $waService;
    }
    
    // ... rest of controller
}
```

### Step 2: Kirim Pesan dengan Multiple Variables

```php
public function kirimNotifikasi($dataPenerima)
{
    $results = [];

    foreach ($dataPenerima as $penerima) {
        
        // Kirim dengan sendMessageWithVariables()
        $result = $this->waService->sendMessageWithVariables(
            [
                'nik' => $penerima->nik,
                'nama' => $penerima->nama,
                'nomor' => $penerima->no_telp
            ],
            [
                $penerima->nama,              // {{1}} - Nama
                $penerima->tanggal,          // {{2}} - Tanggal
                $penerima->shift,            // {{3}} - Shift
                $penerima->jam               // {{4}} - Jam
            ],
            $this->templateIdFitur  // Template ID spesifik fitur ini
        );

        $results[] = $result;
        
        // Rate limiting: tunggu 1 detik
        sleep(1);
    }

    return $results;
}
```

### Step 3: Mapping Variable

Sesuaikan urutan array dengan template:

| Index | Variable | Data | Contoh |
|-------|----------|------|--------|
| 0 | `{{1}}` | Nama | "Okyanto" |
| 1 | `{{2}}` | Tanggal | "26 Maret 2026" |
| 2 | `{{3}}` | Shift | "Pagi" |
| 3 | `{{4}}` | Jam | "06:30" |

---

## 📊 Tracking & Monitoring

### 1. Database Logs
Semua pesan tercatat di tabel `whatsapp_logs`:

```sql
SELECT * FROM whatsapp_logs 
WHERE created_at >= NOW() - INTERVAL 1 DAY
ORDER BY created_at DESC;
```

### 2. Cek Status Delivery
**Manual check per message:**
```php
$status = $this->waService->checkMessageStatus($qontakMessageId);
```

**Bulk sync via artisan:**
```bash
php artisan whatsapp:sync-status
```

### 3. Webhook Endpoint
URL untuk Qontak webhook:
```
POST /webhook/qontak/status
```

---

## 🧪 Testing

### Test Manual via Browser:
```
GET /jadwalbudayakerja/kirimmanual/sore
GET /jadwalbudayakerja/kirimmanual/pagi
```

### Test via Artisan:
```bash
php artisan whatsapp:sync-status
```

---

## 📝 Contoh Lengkap: Kontrol Pasien

```php
<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use App\Models\KontrolPasien;

class KontrolPasienController extends Controller
{
    protected WhatsAppService $waService;
    
    // Template untuk reminder kontrol pasien
    protected string $templateIdKontrol = 'uuid-template-kontrol';

    public function __construct(WhatsAppService $waService)
    {
        $this->waService = $waService;
    }

    /**
     * Kirim reminder kontrol pasien
     */
    public function kirimReminderKontrol()
    {
        // Ambil data pasien yang akan kontrol besok
        $pasienKontrol = KontrolPasien::with('pasien')
            ->whereDate('tgl_kontrol', now()->addDay())
            ->get();

        foreach ($pasienKontrol as $kontrol) {
            
            // Template di Qontak:
            // Halo {{1}}, jangan lupa kontrol ke {{2}} 
            // pada tanggal {{3}} pukul {{4}} WIB.
            // Poli: {{5}}
            
            $this->waService->sendMessageWithVariables(
                [
                    'nik' => $kontrol->pasien->nik,
                    'nama' => $kontrol->pasien->nama,
                    'nomor' => $kontrol->pasien->no_hp
                ],
                [
                    $kontrol->pasien->nama,           // {{1}} Nama
                    'RS Aisyiyah Sitifatimah',        // {{2}} Rumah Sakit
                    $kontrol->tgl_kontrol->format('d F Y'),  // {{3}} Tanggal
                    $kontrol->jam_kontrol,            // {{4}} Jam
                    $kontrol->nama_poli               // {{5}} Poli
                ],
                $this->templateIdKontrol
            );

            sleep(1); // Rate limiting
        }
    }
}
```

---

## ⚠️ Troubleshooting

### Error: "Invalid parameter"
- Cek jumlah variable di template vs data yang dikirim
- Harus sama persis

### Error: "Template not found"
- Cek UUID template sudah benar
- Pastikan template sudah di-approve

### Pesan tidak terkirim
- Cek nomor telepon format (628xxx)
- Pastikan nomor terdaftar di kontak Qontak
- Cek logs di `whatsapp_logs` table

---

## 📚 Referensi

- **Qontak API Docs**: https://docs.qontak.com
- **Template Guidelines**: https://business.whatsapp.com/products/business-platform
- **HMAC Authentication**: https://docs.qontak.com/docs/hmac

---

## 💡 Tips

1. **Jangan hardcode credentials** - Selalu gunakan `.env`
2. **Template ID di Controller** - Bukan di `.env` agar fleksibel
3. **Gunakan multiple variables** - Untuk formatting lebih baik
4. **Selalu rate limiting** - Gunakan `sleep(1)` antar pesan
5. **Monitor logs** - Cek `whatsapp_logs` secara berkala

---

**Last Updated**: 26 Maret 2026  
**Author**: RS Aisyiyah Sitifatimah IT Team
