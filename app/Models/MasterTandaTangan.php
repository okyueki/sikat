<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MasterTandaTangan extends Model
{
    protected $table = 'master_tanda_tangan';

    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';

    protected $fillable = [
        'user_id',
        'type',
        'nama_lengkap',
        'inisial',
        'font_style',
        'color',
        'file_path',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected $appends = ['url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * URL publik untuk gambar tanda tangan (jika type = image).
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->type !== self::TYPE_IMAGE || !$this->file_path) {
            return null;
        }
        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Cek apakah tipe gambar
     */
    public function isImage(): bool
    {
        return $this->type === self::TYPE_IMAGE && $this->file_path;
    }

    /**
     * Font style 1-4 map ke gaya di UI (Dancing Script, Pacifico, Great Vibes, Sacramento).
     * Simpan sebagai '1','2','3','4' agar konsisten dengan form.
     */
    public static function fontStyleLabel(string $value): string
    {
        return match ($value) {
            '2' => 'Pacifico',
            '3' => 'Great Vibes',
            '4' => 'Sacramento',
            default => 'Dancing Script',
        };
    }
}
