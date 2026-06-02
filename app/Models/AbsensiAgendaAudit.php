<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsensiAgendaAudit extends Model
{
    protected $table = 'absensi_agenda_audit';

    public $timestamps = false;

    protected $fillable = [
        'absensi_id',
        'agenda_id',
        'nik',
        'aksi',
        'status_lama',
        'status_baru',
        'alasan_perubahan',
        'perubahan_oleh',
        'perubahan_pada',
        'ip_address',
        'device_token',
        'device_info',
    ];

    protected $casts = [
        'perubahan_pada' => 'datetime',
    ];

    public function absensi(): BelongsTo
    {
        return $this->belongsTo(AbsensiAgenda::class, 'absensi_id', 'id_absensi_agenda');
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class, 'agenda_id', 'id');
    }

    public function perubahanOlehPegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'perubahan_oleh', 'nik');
    }
}