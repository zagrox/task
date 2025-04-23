<?php

namespace TaskManager\Commands;

use Illuminate\Console\Command;
use TaskManager\Services\SyncService;

class SyncTasksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taskmanager:sync 
                            {--provider=all : The provider to sync with (all, github)}
                            {--direction=both : Sync direction (pull, push, both)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize tasks with external providers';

    /**
     * The sync service instance.
     *
     * @var \TaskManager\Services\SyncService
     */
    protected $syncService;

    /**
     * Create a new command instance.
     *
     * @param  \TaskManager\Services\SyncService  $syncService
     * @return void
     */
    public function __construct(SyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $provider = $this->option('provider');
        $direction = $this->option('direction');

        if (!config('taskmanager.sync.enabled')) {
            $this->error('Task synchronization is disabled in configuration.');
            return 1;
        }

        $this->info("Starting task synchronization with direction: {$direction}");

        try {
            $result = $this->syncService->sync($provider, $direction);
            
            $this->info("Successfully synchronized tasks:");
            $this->table(
                ['Provider', 'Direction', 'Tasks Synced'],
                collect($result)->map(function ($count, $key) {
                    [$provider, $direction] = explode(':', $key);
                    return [
                        'provider' => $provider,
                        'direction' => $direction,
                        'count' => $count,
                    ];
                })->toArray()
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Error during synchronization: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
} 