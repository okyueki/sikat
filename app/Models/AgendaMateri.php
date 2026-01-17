<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgendaMateri extends Model
{
    use HasFactory;

    protected $table = 'agenda_materi';

    protected $fillable = [
        'agenda_id',
        'nama_file',
        'path_file',
        'ukuran_file',
        'tipe_file',
        'diupload_oleh',
        'diupload_pada',
        'keterangan',
        'jenis'
    ];

    protected $casts = [
        'diupload_pada' => 'datetime',
    ];

    // Relasi ke Agenda
    public function agenda()
    {
        return $this->belongsTo(Agenda::class, 'agenda_id');
    }

    // Relasi ke Pegawai (uploader)
    public function uploader()
    {
        return $this->belongsTo(Pegawai::class, 'diupload_oleh', 'nik');
    }

    // Scope untuk filter jenis materi
    public function scopeMateri($query)
    {
        return $query->where('jenis', 'materi');
    }

    // Scope untuk filter jenis dokumentasi
    public function scopeDokumentasi($query)
    {
        return $query->where('jenis', 'dokumentasi');
    }
}
