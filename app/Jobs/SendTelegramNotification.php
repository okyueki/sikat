<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\TelegramUser;
use App\Models\TelegramNotification;
use Exception;

class SendTelegramNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Jumlah maksimal retry jika gagal
     */
    public $tries = 3;

    /**
     * Delay retry (dalam detik): 1 menit, 5 menit, 15 menit
     */
    public $backoff = [60, 300, 900];

    /**
     * Rate limiting: Telegram API limit adalah 30 messages per second per bot
     * Kita set lebih konservatif: 25 messages per second untuk safety margin
     */
    public $rateLimitPerSecond = 25;

    protected $chatId;
    protected $message;
    protected $notificationId;

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
        // Validasi chat_id
        if (!$this->chatId) {
            Log::error("SendTelegramNotification: chat_id kosong");
            return;
        }

        // Rate limiting: Tunggu jika sudah mencapai limit
        $rateLimitKey = 'telegram_api_rate_limit';
        $maxAttempts = $this->rateLimitPerSecond;
        $decaySeconds = 1; // Per detik

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            Log::info("Telegram rate limit reached, releasing job back to queue", [
                'chat_id' => $this->chatId,
                'wait_seconds' => $seconds,
            ]);
            
            // Release job kembali ke queue dengan delay
            $this->release($seconds);
            return;
        }

        // Hit rate limiter
        RateLimiter::hit($rateLimitKey, $decaySeconds);

        // Cari TelegramUser berdasarkan chat_id untuk tracking
        $telegramUser = TelegramUser::where('chat_id', $this->chatId)->first();
        
        // Buat record tracking (status: pending)
        if ($telegramUser) {
            $notification = TelegramNotification::create([
                'telegram_user_id' => $telegramUser->id,
                'message' => $this->message,
                'status' => 'pending',
            ]);
            $this->notificationId = $notification->id;
        }

        $token = config('telegram.bot_token');

        if (!$token) {
            Log::error("Telegram bot token tidak ditemukan. Cek config/telegram.php atau .env");
            
            // Update tracking status: failed
            if ($this->notificationId) {
                TelegramNotification::where('id', $this->notificationId)->update([
                    'status' => 'failed',
                ]);
            }
            
            $this->fail(new Exception("Telegram bot token tidak ditemukan"));
            return;
        }

        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        try {
            $response = Http::timeout(10)->post($url, [
                'chat_id' => $this->chatId,
                'text'    => $this->message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Cek apakah user block bot (error 403 Forbidden)
                if (isset($responseData['ok']) && $responseData['ok'] === false) {
                    $errorCode = $responseData['error_code'] ?? null;
                    $description = $responseData['description'] ?? '';
                    
                    // Jika user block bot atau chat tidak ditemukan, jangan retry
                    if (in_array($errorCode, [403, 400])) {
                        Log::warning("Telegram send failed - User block bot or invalid chat", [
                            'chat_id' => $this->chatId,
                            'error_code' => $errorCode,
                            'description' => $description,
                        ]);
                        
                        // Update tracking status: failed
                        if ($this->notificationId) {
                            TelegramNotification::where('id', $this->notificationId)->update([
                                'status' => 'failed',
                            ]);
                        }
                        
                        // Jangan retry untuk error ini
                        $this->delete();
                        return;
                    }
                }

                // Update tracking status: sent
                if ($this->notificationId) {
                    TelegramNotification::where('id', $this->notificationId)->update([
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);
                }

                Log::info("Telegram notif sent successfully", [
                    'chat_id' => $this->chatId,
                    'notification_id' => $this->notificationId,
                    'response' => $responseData,
                ]);
            } else {
                // Handle HTTP error
                $errorBody = $response->body();
                $statusCode = $response->status();
                
                Log::error("Telegram send failed", [
                    'chat_id' => $this->chatId,
                    'status_code' => $statusCode,
                    'response' => $errorBody,
                ]);

                // Update tracking status: failed (jika ini attempt terakhir)
                if ($this->notificationId && $this->attempts() >= $this->tries) {
                    TelegramNotification::where('id', $this->notificationId)->update([
                        'status' => 'failed',
                    ]);
                }

                // Throw exception untuk trigger retry
                throw new Exception("Telegram API error: HTTP {$statusCode} - {$errorBody}");
            }
        } catch (Exception $e) {
            Log::error("Telegram send exception", [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Update tracking status: failed (jika ini attempt terakhir)
            if ($this->notificationId && $this->attempts() >= $this->tries) {
                TelegramNotification::where('id', $this->notificationId)->update([
                    'status' => 'failed',
                ]);
            }

            // Re-throw untuk trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception)
    {
        // Update tracking status: failed
        if ($this->notificationId) {
            TelegramNotification::where('id', $this->notificationId)->update([
                'status' => 'failed',
            ]);
        }

        Log::error("SendTelegramNotification job failed after all retries", [
            'chat_id' => $this->chatId,
            'notification_id' => $this->notificationId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}