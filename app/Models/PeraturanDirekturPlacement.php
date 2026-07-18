<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeraturanDirekturPlacement extends Model
{
    use HasFactory;

    protected $table = 'peraturan_direktur_placements';

    protected $fillable = [
        'peraturan_direktur_id',
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

    public function peraturanDirektur()
    {
        return $this->belongsTo(PeraturanDirektur::class);
    }
}
