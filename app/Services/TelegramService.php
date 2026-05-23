<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class TelegramService
{
    protected $client;
    protected $token;

    public function __construct()
    {
        $this->client = new Client();
        $this->token = config('telegram.bot_token');
    }

    public function sendMessage($chatId, $message)
    {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";

        try {
            $response = $this->client->post($url, [
                'form_params' => [
                    'chat_id'    => $chatId,
                    'text'       => $message,
                    'parse_mode' => 'HTML',
                ],
                'http_errors' => false, // biar gak langsung throw exception
            ]);

            $body = json_decode((string) $response->getBody(), true);

            // Tambahin log sukses/gagal
            Log::info('Telegram sendMessage', [
                'chat_id' => $chatId,
                'message' => $message,
                'response' => $body,
            ]);

            return $body;
        } catch (\Exception $e) {
            Log::error('Telegram sendMessage failed', [
                'chat_id' => $chatId,
                'message' => $message,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}