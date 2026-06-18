<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasjidToken extends Model
{
    protected $table = 'masjid_tokens';

    protected $fillable = [
        'token',
        'valid_until',
        'is_active',
    ];

    protected $casts = [
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Cek apakah token valid (aktif DAN belum kedaluwarsa).
     */
    public static function isValid(string $token): bool
    {
        return static::where('token', $token)
            ->where('is_active', true)
            ->where('valid_until', '>', now())
            ->exists();
    }

    /**
     * Ambil record token yang valid (untuk audit).
     */
    public static function findValid(string $token): ?self
    {
        return static::where('token', $token)
            ->where('is_active', true)
            ->where('valid_until', '>', now())
            ->first();
    }
}
