<?php

namespace TaskApp\TaskManager\Console\Commands;

use Illuminate\Console\Command;
use TaskApp\TaskManager\Services\OfflineService;

class CheckConnectivityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taskmanager:connectivity
                            {--refresh : Force refresh the feature detection cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check connectivity and feature availability in the current environment';

    /**
     * Execute the console command.
     *
     * @param OfflineService $offlineService
     * @return int
     */
    public function handle(OfflineService $offlineService)
    {
        $this->info('Checking system connectivity and feature availability...');
        
        // Force refresh if requested
        if ($this->option('refresh')) {
            $this->info('Refreshing feature detection...');
            $offlineService->detectAvailableFeatures();
        }
        
        // Check features
        $features = [
            'online' => 'Network connectivity',
            'database' => 'Database connectivity',
            'local_storage' => 'Local storage availability',
            'hub_service' => 'Hub service availability', 
            'indexed_db' => 'IndexedDB availability (for web clients)'
        ];
        
        $rows = [];
        foreach ($features as $feature => $description) {
            $available = $offlineService->hasFeature($feature);
            $rows[] = [
                $feature,
                $description,
                $available ? '<fg=green>Available</>' : '<fg=red>Unavailable</>'
            ];
        }
        
        $this->table(
            ['Feature', 'Description', 'Status'],
            $rows
        );
        
        // Provide additional information based on features
        if (!$offlineService->hasFeature('online')) {
            $this->warn('Your system is currently offline. Tasks will be stored locally and synchronized when connectivity is restored.');
        }
        
        if (!$offlineService->hasFeature('database')) {
            $this->warn('Database is not available. System will use file storage as a fallback.');
        }
        
        if (!$offlineService->hasFeature('hub_service')) {
            $this->warn('Hub service is not reachable. Tasks will be queued for synchronization when hub becomes available.');
            
            // Check sync queue
            $queueCount = 0;
            try {
                // Try to get queue count from database
                $queueCount = \TaskApp\TaskManager\Models\SyncQueue::where('status', 'pending')->count();
            } catch (\Exception $e) {
                // If database not available, check file queue
                $queueFile = 'taskmanager/sync_queue/queue.json';
                if (\Illuminate\Support\Facades\Storage::exists($queueFile)) {
                    $queue = json_decode(\Illuminate\Support\Facades\Storage::get($queueFile), true);
                    if (isset($queue['operations']) && is_array($queue['operations'])) {
                        $queueCount = count(array_filter($queue['operations'], function ($item) {
                            return ($item['status'] ?? '') === 'pending';
                        }));
                    }
                }
            }
            
            if ($queueCount > 0) {
                $this->info("There are {$queueCount} operations pending in the sync queue.");
            }
        }
        
        $mode = config('taskmanager.mode', 'standalone');
        $this->info("System is running in {$mode} mode.");
        
        return 0;
    }
} 