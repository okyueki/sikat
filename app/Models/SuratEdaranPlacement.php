<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuratEdaranPlacement extends Model
{
    use HasFactory;

    protected $table = 'surat_edaran_placements';

    protected $fillable = [
        'surat_edaran_id',
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

    public function suratEdaran()
    {
        return $this->belongsTo(SuratEdaran::class);
    }
}
