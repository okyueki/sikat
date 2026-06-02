<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AbsensiAgenda extends Model
{
    use HasFactory;

    protected $table = 'absensi_agenda';

    protected $primaryKey = 'id_absensi_agenda';

    protected $fillable = [
        'nik',
        'agenda_id',
        'waktu_kehadiran',
        'token',
        'status_kehadiran',
        'alasan',
        'device_token',
        'device_model',
        'os_version',
        'browser',
        'ip_address',
    ];

    protected $casts = [
        'waktu_kehadiran' => 'datetime',
    ];

    // Relasi ke model Pegawai
    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class, 'nik', 'nik');
    }

    // Relasi ke model Agenda
    public function agenda()
    {
        return $this->belongsTo(Agenda::class, 'agenda_id', 'id');
    }

    // Relasi ke audit logs
    public function auditLogs()
    {
        return $this->hasMany(AbsensiAgendaAudit::class, 'absensi_id', 'id_absensi_agenda');
    }
}
