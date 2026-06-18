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
}
