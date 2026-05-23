<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalGeneratorMember extends Model
{
    use HasFactory;

    protected $table = 'jadwal_generator_members';

    protected $fillable = [
        'source_type',
        'source_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

