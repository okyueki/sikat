<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\WhatsAppService;
use App\Models\WhatsappLog;
use Illuminate\Support\Facades\Log;
use Exception;

class SendWhatsAppNotification implements ShouldQueue
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
     * Rate limiting: Qontak API rate limit
     * Kita set konservatif: 1 message per second untuk safety
     */
    public $rateLimitPerSecond = 1;

    protected $target;
    protected $variables;
    protected $templateId;

    /**
     * Create a new job instance.
     *
     * @param array $target ['nik', 'nama', 'nomor']
     * @param array $variables Array untuk {{1}}, {{2}}, etc.
     * @param string $templateId UUID template Qontak
     */
    public function __construct(array $target, array $variables, string $templateId)
    {
        $this->target = $target;
        $this->variables = $variables;
        $this->templateId = $templateId;
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $waService): void
    {
        try {
            Log::info("Processing WhatsApp notification job", [
                'target' => $this->target['nama'],
                'phone' => $this->target['nomor'],
            ]);

            // Kirim pesan WhatsApp
            $result = $waService->sendMessageWithVariables(
                $this->target,
                $this->variables,
                $this->templateId
            );

            if ($result['status']) {
                Log::info("✅ WhatsApp job completed successfully", [
                    'nama' => $this->target['nama'],
                    'phone' => $this->target['nomor'],
                ]);
            } else {
                Log::error("❌ WhatsApp job failed", [
                    'nama' => $this->target['nama'],
                    'error' => $result['message'],
                ]);
                
                // Update log status ke failed
                WhatsappLog::where('phone', $this->target['nomor'])
                    ->where('template_id', $this->templateId)
                    ->latest()
                    ->first()
                    ?->update([
                        'delivery_status' => 'failed',
                        'response' => json_encode(['error' => $result['message']]),
                    ]);
                
                throw new Exception($result['message']);
            }

        } catch (Exception $e) {
            Log::error("❌ WhatsApp job exception", [
                'error' => $e->getMessage(),
                'target' => $this->target,
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("SendWhatsAppNotification job failed after all retries", [
            'target' => $this->target,
            'template_id' => $this->templateId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
