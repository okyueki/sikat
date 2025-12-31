<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramNotification extends Model
{
    protected $table = 'telegram_notifications';

    protected $fillable = [
        'telegram_user_id',
        'message',
        'status',
        'sent_at',
        // created_at dan updated_at otomatis dari Eloquent
    ];

    // HAPUS baris ini, karena tabel punya created_at & updated_at
    // public $timestamps = false;

    // Jika ingin relasi ke TelegramUser
    public function telegramUser()
    {
        return $this->belongsTo(TelegramUser::class);
    }
}