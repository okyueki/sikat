<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MasterStempel extends Model
{
    protected $table = 'master_stempel';

    protected $fillable = [
        'nama',
        'file_path',
        'keterangan',
    ];

    /**
     * Hanya satu stempel perusahaan (singleton).
     */
    public static function getPerusahaan(): ?self
    {
        return self::first();
    }

    /**
     * URL publik untuk gambar stempel (untuk tampil di UI).
     */
    public function getUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return Storage::disk('public')->url($this->file_path);
    }
}
