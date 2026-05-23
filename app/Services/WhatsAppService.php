<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsappLog;
use Carbon\Carbon;

class WhatsAppService
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $channelId;
    protected string $baseUrl = 'https://api.mekari.com';

    public function __construct()
    {
        $this->clientId = config('services.qontak.client_id');
        $this->clientSecret = config('services.qontak.client_secret');
        $this->channelId = config('services.qontak.channel_id');
    }

    /**
     * Generate HMAC Authorization header for Qontak API
     */
    protected function generateHmacHeader(string $method, string $path): array
    {
        $date = gmdate('D, d M Y H:i:s') . ' GMT';
        $requestLine = "{$method} {$path} HTTP/1.1";
        $signatureRaw = "date: {$date}\n{$requestLine}";
        
        $hash = hash_hmac('sha256', $signatureRaw, $this->clientSecret, true);
        $signature = base64_encode($hash);

        $authorization = 'hmac username="' . $this->clientId .
            '", algorithm="hmac-sha256", headers="date request-line", signature="' .
            $signature . '"';

        return [
            'Authorization' => $authorization,
            'Date' => $date,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Send WhatsApp message using Qontak API
     */
    public function sendMessage(array $target, string $pesan, string $templateId = null): array
    {
        try {
            // Validate phone number format
            $nomor = $this->formatPhoneNumber($target['nomor']);
            if (!$nomor) {
                return [
                    'target' => $target['nomor'],
                    'status' => false,
                    'message' => 'Invalid phone number format'
                ];
            }

            $templateId = $templateId ?? config('services.qontak.template_id');
            $path = '/qontak/chat/v1/broadcasts/whatsapp/direct';
            $url = $this->baseUrl . $path;

            $headers = $this->generateHmacHeader('POST', $path);

            $payload = [
                'to_name' => $target['nama'],
                'to_number' => $nomor,
                'message_template_id' => $templateId,
                'channel_integration_id' => $this->channelId,
                'language' => ['code' => 'id'],
                'recipient_type' => 'individual',
                'parameters' => [
                    'body' => [
                        [
                            'key' => '1',
                            'value' => 'pesan',
                            'value_text' => $pesan
                        ]
                    ]
                ]
            ];

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($url, $payload);

            $result = $response->json();

            $qontakMessageId = $result['data']['id'] ?? null;
            $executeStatus = $result['data']['execute_status'] ?? 'todo';

            // Log the attempt
            WhatsappLog::create([
                'nik' => $target['nik'] ?? null,
                'nama' => $target['nama'],
                'phone' => $nomor,
                'message' => $pesan,
                'template_id' => $templateId,
                'qontak_message_id' => $qontakMessageId,
                'status' => $response->successful() ? 'success' : 'failed',
                'delivery_status' => $executeStatus,
                'response' => json_encode($result),
                'sent_at' => now(),
            ]);

            if ($response->successful()) {
                Log::info("✅ Qontak berhasil ke {$nomor}: {$target['nama']}");
                return [
                    'target' => $nomor,
                    'status' => true,
                    'message' => 'Berhasil',
                    'response' => $result
                ];
            }

            Log::error("❌ Qontak gagal ke {$nomor}", [
                'response' => $result,
                'status' => $response->status()
            ]);

            return [
                'target' => $nomor,
                'status' => false,
                'message' => $result['message'] ?? 'Unknown error',
                'response' => $result
            ];

        } catch (\Exception $e) {
            Log::error("❌ Qontak exception: " . $e->getMessage(), [
                'target' => $target,
                'trace' => $e->getTraceAsString()
            ]);

            // Log failed attempt
            WhatsappLog::create([
                'nik' => $target['nik'] ?? null,
                'nama' => $target['nama'],
                'phone' => $target['nomor'],
                'message' => $pesan,
                'template_id' => $templateId,
                'qontak_message_id' => null,
                'status' => 'error',
                'delivery_status' => 'failed',
                'response' => json_encode(['error' => $e->getMessage()]),
                'sent_at' => now(),
            ]);

            return [
                'target' => $target['nomor'],
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send bulk messages with rate limiting
     */
    public function sendBulk(array $recipients, string $templateId = null): array
    {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $result = $this->sendMessage($recipient, $recipient['pesan'], $templateId);
            $results[] = $result;
            
            // Rate limiting: sleep 1 second between requests
            sleep(1);
        }

        return $results;
    }

    /**
     * Send WhatsApp message with multiple template variables ({{1}}, {{2}}, etc.)
     * 
     * @param array $target ['nik', 'nama', 'nomor']
     * @param array $variables Array of values for {{1}}, {{2}}, {{3}}, {{4}}
     * @param string $templateId UUID of the template with multiple variables
     */
    public function sendMessageWithVariables(array $target, array $variables, string $templateId): array
    {
        try {
            $nomor = $this->formatPhoneNumber($target['nomor']);
            if (!$nomor) {
                return [
                    'target' => $target['nomor'],
                    'status' => false,
                    'message' => 'Invalid phone number format'
                ];
            }

            $path = '/qontak/chat/v1/broadcasts/whatsapp/direct';
            $url = $this->baseUrl . $path;

            $headers = $this->generateHmacHeader('POST', $path);

            // Build parameters array for multiple variables {{1}}, {{2}}, etc.
            $bodyParams = [];
            foreach ($variables as $index => $value) {
                $bodyParams[] = [
                    'key' => (string)($index + 1),
                    'value' => 'var' . ($index + 1),
                    'value_text' => $value
                ];
            }

            $payload = [
                'to_name' => $target['nama'],
                'to_number' => $nomor,
                'message_template_id' => $templateId,
                'channel_integration_id' => $this->channelId,
                'language' => ['code' => 'id'],
                'recipient_type' => 'individual',
                'parameters' => [
                    'body' => $bodyParams
                ]
            ];

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($url, $payload);

            $result = $response->json();

            $qontakMessageId = $result['data']['id'] ?? null;
            $executeStatus = $result['data']['execute_status'] ?? 'todo';

            // Build message text for logging
            $messageText = implode(' | ', $variables);

            // Log the attempt
            WhatsappLog::create([
                'nik' => $target['nik'] ?? null,
                'nama' => $target['nama'],
                'phone' => $nomor,
                'message' => $messageText,
                'template_id' => $templateId,
                'qontak_message_id' => $qontakMessageId,
                'status' => $response->successful() ? 'success' : 'failed',
                'delivery_status' => $executeStatus,
                'response' => json_encode($result),
                'sent_at' => now(),
            ]);

            if ($response->successful()) {
                Log::info("✅ Qontak berhasil ke {$nomor}: {$target['nama']}");
                return [
                    'target' => $nomor,
                    'status' => true,
                    'message' => 'Berhasil',
                    'response' => $result
                ];
            }

            Log::error("❌ Qontak gagal ke {$nomor}", [
                'response' => $result,
                'status' => $response->status()
            ]);

            return [
                'target' => $nomor,
                'status' => false,
                'message' => $result['message'] ?? 'Unknown error',
                'response' => $result
            ];

        } catch (\Exception $e) {
            Log::error("❌ Qontak exception: " . $e->getMessage());

            WhatsappLog::create([
                'nik' => $target['nik'] ?? null,
                'nama' => $target['nama'],
                'phone' => $target['nomor'],
                'message' => implode(' | ', $variables),
                'template_id' => $templateId,
                'qontak_message_id' => null,
                'status' => 'error',
                'delivery_status' => 'failed',
                'response' => json_encode(['error' => $e->getMessage()]),
                'sent_at' => now(),
            ]);

            return [
                'target' => $target['nomor'],
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number to international format
     */
    protected function formatPhoneNumber(string $number): ?string
    {
        // Remove all non-numeric characters
        $number = preg_replace('/[^0-9]/', '', $number);
        
        // Check if valid Indonesian number
        if (strlen($number) < 10 || strlen($number) > 15) {
            return null;
        }
        
        // Convert to international format
        if (str_starts_with($number, '0')) {
            $number = '62' . substr($number, 1);
        }
        
        if (!str_starts_with($number, '62')) {
            $number = '62' . $number;
        }
        
        return $number;
    }

    /**
     * Check delivery status of a message from Qontak API
     */
    public function checkMessageStatus(string $qontakMessageId): ?array
    {
        try {
            $path = "/qontak/chat/v1/broadcasts/whatsapp/{$qontakMessageId}";
            $url = $this->baseUrl . $path;

            $headers = $this->generateHmacHeader('GET', $path);

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->get($url);

            if ($response->successful()) {
                $result = $response->json();
                Log::info("Qontak status check for {$qontakMessageId}", ['data' => $result['data'] ?? null]);
                return $result['data'] ?? null;
            }

            Log::error("Failed to check Qontak status for {$qontakMessageId}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error("Exception checking Qontak status: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update delivery status for a WhatsappLog entry
     */
    public function updateDeliveryStatus(WhatsappLog $log): bool
    {
        if (empty($log->qontak_message_id)) {
            return false;
        }

        $statusData = $this->checkMessageStatus($log->qontak_message_id);

        if (!$statusData) {
            return false;
        }

        $executeStatus = $statusData['execute_status'] ?? $log->delivery_status;
        $messageStatus = $statusData['message_status_count'] ?? [];

        // Determine final status
        $deliveryStatus = $executeStatus;
        if (!empty($messageStatus['failed']) && $messageStatus['failed'] > 0) {
            $deliveryStatus = 'failed';
        } elseif (!empty($messageStatus['read']) && $messageStatus['read'] > 0) {
            $deliveryStatus = 'read';
        } elseif (!empty($messageStatus['delivered']) && $messageStatus['delivered'] > 0) {
            $deliveryStatus = 'delivered';
        } elseif (!empty($messageStatus['sent']) && $messageStatus['sent'] > 0) {
            $deliveryStatus = 'sent';
        }

        // Update timestamps
        $deliveredAt = $log->delivered_at;
        $readAt = $log->read_at;

        if ($deliveryStatus === 'delivered' && !$deliveredAt) {
            $deliveredAt = now();
        }
        if ($deliveryStatus === 'read' && !$readAt) {
            $readAt = now();
            if (!$deliveredAt) {
                $deliveredAt = now();
            }
        }

        $log->update([
            'delivery_status' => $deliveryStatus,
            'delivery_response' => json_encode($statusData),
            'delivered_at' => $deliveredAt,
            'read_at' => $readAt,
        ]);

        Log::info("Updated status for message {$log->qontak_message_id}: {$deliveryStatus}");

        return true;
    }

    /**
     * Check and update status for all pending/sent messages
     */
    public function syncAllPendingStatuses(): array
    {
        $logs = WhatsappLog::whereIn('delivery_status', ['todo', 'progress', 'sent'])
            ->whereNotNull('qontak_message_id')
            ->where('sent_at', '>=', now()->subDays(2)) // Only check last 2 days
            ->get();

        $results = [
            'total' => $logs->count(),
            'updated' => 0,
            'failed' => 0,
        ];

        foreach ($logs as $log) {
            try {
                if ($this->updateDeliveryStatus($log)) {
                    $results['updated']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error("Error syncing status for log {$log->id}: " . $e->getMessage());
            }

            // Rate limiting
            sleep(1);
        }

        return $results;
    }

    /**
     * Build jadwal notification message
     */
    public function buildJadwalMessage(string $nama, string $shift, string $tanggal, string $hari): string
    {
        $shift = strtolower(trim($shift));
        
        if ($shift === 'pagi') {
            $jam_shift = '06:30';
            $emoji_shift = '🌅';
        } elseif ($shift === 'sore') {
            $jam_shift = '13:30';
            $emoji_shift = '🌇';
        } else {
            $jam_shift = '-';
            $emoji_shift = '❓';
        }

        return " *{$nama}* \\n{$hari}, {$tanggal}\\nShift: {$shift}\\n({$jam_shift})";
    }
}
