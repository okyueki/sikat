<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event broadcast ke frontend saat ada surat masuk baru (verifikasi atau disposisi).
 * Frontend bisa subscribe ke private channel surat-notifikasi.{userId} dan refresh badge / tampilkan notifikasi.
 */
class SuratMasukNotifikasi implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public string $type,
        public string $message,
        public ?int $id_surat = null,
        public ?string $kode_surat = null
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('surat-notifikasi.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'surat-masuk.notifikasi';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'message' => $this->message,
            'id_surat' => $this->id_surat,
            'kode_surat' => $this->kode_surat,
        ];
    }
}
