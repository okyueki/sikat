<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoInternalPlacement extends Model
{
    protected $table = 'memo_internal_placements';

    protected $fillable = [
        'memo_internal_id',
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

    public function memoInternal(): BelongsTo
    {
        return $this->belongsTo(MemoInternal::class, 'memo_internal_id');
    }
}
