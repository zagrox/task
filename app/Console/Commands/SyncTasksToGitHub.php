<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Services\GitHubService;
use App\Models\GitHubIssue;

class SyncTasksToGitHub extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:sync-to-github 
                            {--task-id= : Sync a specific task by ID}
                            {--status= : Sync tasks with a specific status (pending, in-progress, etc.)}
                            {--all : Sync all tasks}
                            {--repository= : GitHub repository in format owner/repo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize tasks to GitHub issues';
    
    /**
     * The tasks JSON file path
     */
    protected $tasksFile;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->tasksFile = base_path('project-management/tasks.json');
    }

    /**
     * Execute the console command.
     */
    public function handle(GitHubService $github)
    {
        if (!File::exists($this->tasksFile)) {
            $this->error('Tasks file not found!');
            return 1;
        }
        
        // Set repository if provided
        $repository = $this->option('repository') ?: env('GITHUB_REPOSITORY');
        if (!$repository) {
            $this->error('No GitHub repository specified. Please set GITHUB_REPOSITORY in .env or provide it with --repository option.');
            return 1;
        }
        $github->setRepository($repository);
        
        // Load tasks from JSON
        $taskData = json_decode(File::get($this->tasksFile), true);
        
        // Filter tasks based on options
        $tasksToSync = [];
        
        if ($this->option('task-id')) {
            // Sync specific task
            $id = (int) $this->option('task-id');
            foreach ($taskData['tasks'] as $task) {
                if ($task['id'] == $id) {
                    $tasksToSync[] = $task;
                    break;
                }
            }
            
            if (empty($tasksToSync)) {
                $this->error("Task #$id not found!");
                return 1;
            }
        } elseif ($this->option('status')) {
            // Sync tasks with specific status
            $status = $this->option('status');
            foreach ($taskData['tasks'] as $task) {
                if ($task['status'] == $status) {
                    $tasksToSync[] = $task;
                }
            }
            
            if (empty($tasksToSync)) {
                $this->info("No tasks found with status '$status'.");
                return 0;
            }
        } elseif ($this->option('all')) {
            // Sync all tasks
            $tasksToSync = $taskData['tasks'];
        } else {
            $this->error('Please specify which tasks to sync using --task-id, --status, or --all options.');
            return 1;
        }
        
        // Create progress bar
        $progressBar = $this->output->createProgressBar(count($tasksToSync));
        $progressBar->start();
        
        $this->info("Starting synchronization of " . count($tasksToSync) . " tasks to GitHub...");
        
        $successCount = 0;
        $failCount = 0;
        
        foreach ($tasksToSync as $task) {
            $this->line("\nSyncing Task #{$task['id']}: {$task['title']}");
            
            $githubIssue = $github->syncTaskToGitHub($task['id']);
            
            if ($githubIssue) {
                $this->info("  ✓ Synced to GitHub issue #{$githubIssue->issue_number} ({$githubIssue->issue_url})");
                $successCount++;
            } else {
                $this->error("  ✗ Failed to sync");
                $failCount++;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        
        $this->newLine(2);
        $this->info("Synchronization complete: $successCount succeeded, $failCount failed.");
        
        return 0;
    }
}
