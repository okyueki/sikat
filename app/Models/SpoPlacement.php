<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpoPlacement extends Model
{
    use HasFactory;

    protected $table = 'spo_placements';

    protected $fillable = [
        'spo_id',
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
    ];

    public function spo()
    {
        return $this->belongsTo(Spo::class, 'spo_id');
    }
}
