<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PegawaiFaceProfile extends Model
{
    protected $table = 'pegawai_face_profiles';

    protected $fillable = [
        'pegawai_id',
        'nik',
        'enrolled_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
    ];

    /**
     * Pegawai ada di connection server_74 — load manual via pegawai_id, bukan Eloquent relation lintas DB.
     */
    public function resolvePegawai(): ?Pegawai
    {
        return Pegawai::find($this->pegawai_id);
    }
}
