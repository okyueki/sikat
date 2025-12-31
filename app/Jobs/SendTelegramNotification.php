<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendTelegramNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chatId;
    protected $message;

    /**
     * Create a new job instance.
     */
    public function __construct($chatId, $message)
    {
        $this->chatId = $chatId;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $token = config('telegram.bot_token');

        if (!$token) {
            Log::error("Telegram bot token tidak ditemukan. Cek config/telegram.php atau .env");
            return;
        }

        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $response = Http::post($url, [
            'chat_id' => $this->chatId,
            'text'    => $this->message,
            'parse_mode' => 'HTML', // bisa HTML atau Markdown
            'disable_web_page_preview' => true,
        ]);

        if ($response->failed()) {
            Log::error("Telegram send failed", [
                'chat_id' => $this->chatId,
                'response' => $response->body(),
            ]);
        } else {
            Log::info("Telegram notif sent", [
                'chat_id' => $this->chatId,
                'response' => $response->json(),
            ]);
        }
    }
}