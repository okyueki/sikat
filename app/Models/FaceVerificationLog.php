<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaceVerificationLog extends Model
{
    public $timestamps = false;

    protected $table = 'face_verification_logs';

    protected $fillable = [
        'pegawai_id',
        'nik',
        'tipe',
        'status',
        'score',
        'jam_datang',
        'shift',
        'insightface_response',
        'created_at',
    ];

    protected $casts = [
        'score' => 'float',
        'jam_datang' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function resolvePegawai(): ?Pegawai
    {
        return Pegawai::find($this->pegawai_id);
    }
}
