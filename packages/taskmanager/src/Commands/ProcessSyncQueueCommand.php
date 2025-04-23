<?php

namespace TaskApp\TaskManager\Commands;

use Illuminate\Console\Command;
use TaskApp\TaskManager\Services\OfflineService;
use TaskApp\TaskManager\Models\SyncQueue;

class ProcessSyncQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taskmanager:process-sync-queue
                            {--limit=50 : Maximum number of items to process in one run}
                            {--retry-failed : Reset failed items to be retried}
                            {--cleanup : Clean up old completed items}
                            {--cleanup-days=7 : Number of days to keep completed items before cleanup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending sync queue items to synchronize with hub';

    /**
     * Execute the console command.
     *
     * @param OfflineService $offlineService
     * @return int
     */
    public function handle(OfflineService $offlineService)
    {
        $this->info('Starting sync queue processing...');
        
        // Retry failed items if requested
        if ($this->option('retry-failed')) {
            $resetCount = SyncQueue::resetFailed();
            $this->info("Reset {$resetCount} failed items for retry.");
        }
        
        // Process pending items
        $limit = $this->option('limit');
        $result = $offlineService->processSyncQueue($limit);
        
        if ($result['status'] === 'offline') {
            $this->warn('System is currently offline or hub service is unavailable. Synchronization skipped.');
            return 1;
        }
        
        if ($result['status'] === 'no_pending_items') {
            $this->info('No pending sync items to process.');
        } else {
            $this->info("Processed {$result['processed']} items successfully, {$result['failed']} items failed.");
        }
        
        // Clean up old items if requested
        if ($this->option('cleanup')) {
            $cleanupDays = $this->option('cleanup-days');
            $cleanedCount = SyncQueue::cleanupOld($cleanupDays);
            $this->info("Cleaned up {$cleanedCount} completed items older than {$cleanupDays} days.");
        }
        
        // Report remaining pending items
        $pendingCount = SyncQueue::where('status', 'pending')->count();
        $failedCount = SyncQueue::where('status', 'failed')->count();
        
        if ($pendingCount > 0 || $failedCount > 0) {
            $this->info("Remaining: {$pendingCount} pending items, {$failedCount} failed items.");
        }
        
        return 0;
    }
} 