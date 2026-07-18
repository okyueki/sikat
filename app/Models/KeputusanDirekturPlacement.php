<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeputusanDirekturPlacement extends Model
{
    use HasFactory;

    protected $table = 'keputusan_direktur_placements';

    protected $fillable = [
        'keputusan_direktur_id',
        'field_type',
        'page',
        'x',
        'y',
        'width',
        'height',
        'value',
        'options',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'page' => 'integer',
        'sort_order' => 'integer',
    ];

    public function keputusanDirektur()
    {
        return $this->belongsTo(KeputusanDirektur::class);
    }
}
