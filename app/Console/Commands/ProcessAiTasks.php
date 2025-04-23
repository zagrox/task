<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AiTaskProcessingService;

class ProcessAiTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:process-ai 
                            {--task-id= : Process a specific task by ID}
                            {--limit=5 : Maximum number of tasks to process}
                            {--force : Force processing even if task is not assigned to AI}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending AI tasks automatically';

    /**
     * The AI task processing service
     * 
     * @var AiTaskProcessingService
     */
    protected $aiService;

    /**
     * Create a new command instance.
     *
     * @param AiTaskProcessingService $aiService
     * @return void
     */
    public function __construct(AiTaskProcessingService $aiService)
    {
        parent::__construct();
        $this->aiService = $aiService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting AI task processing...');
        
        try {
            if ($taskId = $this->option('task-id')) {
                // Process specific task
                $this->info("Processing task #{$taskId}");
                $result = $this->aiService->processTaskById((int)$taskId);
                
                $this->info("Task #{$taskId} processed successfully");
                $this->info("Task status: {$result['status']}");
            } else {
                // Process multiple pending tasks
                $limit = (int)$this->option('limit');
                $this->info("Processing up to {$limit} pending AI tasks");
                
                $result = $this->aiService->processPendingTasks($limit);
                
                $this->info("{$result['processed']} tasks processed");
                
                if (!empty($result['results'])) {
                    $this->table(
                        ['ID', 'Title', 'Status', 'Message'],
                        array_map(function($task) {
                            return [
                                $task['id'],
                                $task['title'],
                                $task['status'],
                                $task['message']
                            ];
                        }, $result['results'])
                    );
                }
            }
            
            $this->info('AI task processing completed successfully');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error processing AI tasks: ' . $e->getMessage());
            return 1;
        }
    }
} 