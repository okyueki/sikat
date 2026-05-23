<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik',
        'nama',
        'phone',
        'message',
        'template_id',
        'qontak_message_id',
        'status',
        'delivery_status',
        'response',
        'delivery_response',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected $casts = [
        'response' => 'array',
        'delivery_response' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public $timestamps = true;
}
