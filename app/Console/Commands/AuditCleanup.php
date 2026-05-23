<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AuditCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:cleanup {--days=90 : Number of days to keep audit logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup old audit trail logs older than specified days (default: 90 days)';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = $this->option('days');
        
        $this->info("Starting audit trail cleanup for logs older than {$days} days...");
        
        $cutoffDate = now()->subDays($days);
        $count = \App\Models\AuditTrail::where('created_at', '<', $cutoffDate)->count();
        
        if ($count === 0) {
            $this->info('No old audit logs found to cleanup.');
            return 0;
        }
        
        if ($this->confirm("Found {$count} audit log(s) older than {$days} days. Delete them?", true)) {
            \App\Models\AuditTrail::where('created_at', '<', $cutoffDate)->delete();
            $this->info("Successfully deleted {$count} old audit log(s).");
        } else {
            $this->info('Cleanup cancelled.');
        }
        
        return 0;
    }
}
