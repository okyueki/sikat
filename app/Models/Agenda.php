<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pegawai;

class Agenda extends Model
{
    use HasFactory;

    protected $table = 'agendas';

    // Definisikan kolom yang boleh diisi (mass assignable)
    protected $fillable = [
        'nomor_agenda',
        'judul',
        'jenis_agenda',
        'deskripsi',
        'mulai',
        'akhir',
        'tempat',
        'pimpinan_rapat',
        'notulen',
        'yang_terundang',
        'kepada_undangan',
        'foto',
        'materi',
        'keterangan',
        'status_acara',
        'kesimpulan_notulen',
        'tanggal_selesai_notulen',
        'created_by',
        'id_surat_keluar',
        'status_realisasi',
    ];

    // Relasi ke model Pegawai untuk pimpinan rapat
    public function pimpinan()
    {
        return $this->belongsTo(Pegawai::class, 'pimpinan_rapat', 'nik');
    }

    // Relasi ke model Pegawai untuk notulen
    public function notulenPegawai()
    {
        return $this->belongsTo(Pegawai::class, 'notulen', 'nik');
    }

    // Relasi ke model Pegawai untuk pembuat agenda
    public function creator()
    {
        return $this->belongsTo(Pegawai::class, 'created_by', 'nik');
    }

    // Relasi untuk yang terundang, karena menggunakan JSON kita perlu memprosesnya berbeda
    public function getYangTerundangAttribute($value)
    {
        // Mengembalikan array NIK yang terundang
        return json_decode($value, true);
    }

    /**
     * Cek apakah NIK (user) terundang di agenda ini.
     * Satu sumber kebenaran: all, isAll (semua pegawai aktif), atau in_array(nik).
     */
    public function userTerundang(string $nik): bool
    {
        $terundang = is_array($this->yang_terundang) ? $this->yang_terundang : [];
        if (empty($terundang)) {
            return false;
        }
        if (in_array('all', $terundang)) {
            return true;
        }
        if (in_array($nik, $terundang)) {
            return true;
        }
        $semuaNikAktif = Pegawai::where('stts_aktif', 'AKTIF')->pluck('nik')->toArray();
        $intersect = array_intersect($semuaNikAktif, $terundang);
        $isAll = count($intersect) === count($semuaNikAktif) && count($terundang) === count($semuaNikAktif);
        return $isAll;
    }

    // Method helper untuk mengecek apakah semua pegawai aktif terundang
    public function isAllPegawaiTerundang()
    {
        $yangTerundang = is_array($this->yang_terundang) 
            ? $this->yang_terundang 
            : json_decode($this->yang_terundang, true);
        
        if (!is_array($yangTerundang) || count($yangTerundang) === 0) {
            return false;
        }
        
        // Pastikan menggunakan connection yang benar (Pegawai sudah set connection di model)
        $semuaNikAktif = Pegawai::on('server_74')
            ->where('stts_aktif', 'AKTIF')
            ->pluck('nik')
            ->toArray();
        $intersect = array_intersect($semuaNikAktif, $yangTerundang);
        
        return count($intersect) === count($semuaNikAktif) && count($yangTerundang) === count($semuaNikAktif);
    }
    public function absensi()
    {
        return $this->hasMany(AbsensiAgenda::class, 'agenda_id', 'id');
    }

    // Relasi ke Surat Keluar
    public function suratKeluar()
    {
        return $this->belongsTo(Surat::class, 'id_surat_keluar', 'id_surat');
    }

    // Relasi ke AgendaMateri (materi)
    public function materiFiles()
    {
        return $this->hasMany(AgendaMateri::class, 'agenda_id')->where('jenis', 'materi');
    }

    // Relasi ke AgendaMateri (dokumentasi)
    public function dokumentasiFiles()
    {
        return $this->hasMany(AgendaMateri::class, 'agenda_id')->where('jenis', 'dokumentasi');
    }

    // Relasi ke semua AgendaMateri
    public function semuaMateri()
    {
        return $this->hasMany(AgendaMateri::class, 'agenda_id');
    }

    public static function defaultKepadaUndangan(): string
    {
        return "Kepada Yth.\nYang tersebut dalam lampiran surat ini\ndi tempat";
    }

    public function kepadaUndanganForPdf(): string
    {
        $text = trim((string) ($this->kepada_undangan ?? ''));

        return $text !== '' ? $text : self::defaultKepadaUndangan();
    }
}
