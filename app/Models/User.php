<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'password',
        'level'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'username_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function masterTandaTangan(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\MasterTandaTangan::class);
    }

    /**
     * Cek izin berdasarkan config/access.php (map ability → daftar level).
     */
    public function canAccess(string $ability): bool
    {
        $abilityConfig = config('access.map', [])[$ability] ?? null;
        if (!is_array($abilityConfig)) {
            return false;
        }

        // Backward compatible:
        // - Lama: ability => ['Direktur', 'Programmer', ...] (berarti level yang diizinkan)
        // - Baru: ability => ['levels' => [...], 'niks' => [...]] (izin berdasarkan level atau NIK/username)
        $allowedLevels = [];
        $allowedNiks = [];

        $hasKeyLevels = array_key_exists('levels', $abilityConfig);
        $hasKeyNiks = array_key_exists('niks', $abilityConfig);

        if ($hasKeyLevels || $hasKeyNiks) {
            $allowedLevels = is_array($abilityConfig['levels'] ?? null) ? $abilityConfig['levels'] : [];
            $allowedNiks = is_array($abilityConfig['niks'] ?? null) ? $abilityConfig['niks'] : [];
        } else {
            // Jika config-nya berupa list string, anggap itu daftar level (lama).
            $allowedLevels = $abilityConfig;
        }

        $isAllowedByLevel = in_array($this->level, $allowedLevels, true);
        $isAllowedByNik = in_array($this->username, $allowedNiks, true);

        return $isAllowedByLevel || $isAllowedByNik;
    }
}

