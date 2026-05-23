<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WhatsAppService;

class SyncWhatsappStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:sync-status {--log-id= : Sync specific log ID} {--all : Sync all pending messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync WhatsApp message delivery status from Qontak API';

    protected WhatsAppService $waService;

    public function __construct(WhatsAppService $waService)
    {
        parent::__construct();
        $this->waService = $waService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔄 Syncing WhatsApp delivery statuses...');

        if ($logId = $this->option('log-id')) {
            // Sync specific log
            $log = \App\Models\WhatsappLog::find($logId);
            if (!$log) {
                $this->error("❌ Log ID {$logId} not found");
                return 1;
            }

            $this->info("Checking status for message ID: {$log->qontak_message_id}");

            if ($this->waService->updateDeliveryStatus($log)) {
                $log->refresh();
                $this->info("✅ Status updated: {$log->delivery_status}");
                if ($log->delivered_at) {
                    $this->info("📬 Delivered at: {$log->delivered_at}");
                }
                if ($log->read_at) {
                    $this->info("👁️ Read at: {$log->read_at}");
                }
            } else {
                $this->error("❌ Failed to update status");
                return 1;
            }
        } else {
            // Sync all pending
            $results = $this->waService->syncAllPendingStatuses();

            $this->info("📊 Results:");
            $this->info("   Total checked: {$results['total']}");
            $this->info("   ✅ Updated: {$results['updated']}");
            $this->info("   ❌ Failed: {$results['failed']}");
        }

        $this->info('✨ Sync completed!');
        return 0;
    }
}
