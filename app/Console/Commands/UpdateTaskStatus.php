<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class UpdateTaskStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:update-status 
                            {--commit-message= : The commit message to analyze}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update task status based on git commit messages';

    protected $taskFile;
    protected $tasks = [];
    protected $metadata = [];

    public function __construct()
    {
        parent::__construct();
        $this->taskFile = base_path('project-management/tasks.json');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Check if task file exists
        if (!File::exists($this->taskFile)) {
            $this->error('Tasks file not found. Run php artisan tasks:generate-ai first.');
            return Command::FAILURE;
        }

        // Get the commit message
        $commitMessage = $this->option('commit-message');
        if (empty($commitMessage)) {
            $this->error('No commit message provided.');
            return Command::FAILURE;
        }

        $this->info('Analyzing commit message: ' . $commitMessage);

        // Load tasks
        $tasksData = json_decode(File::get($this->taskFile), true);
        $this->tasks = $tasksData['tasks'] ?? [];
        $this->metadata = $tasksData['metadata'] ?? [];

        if (empty($this->tasks)) {
            $this->info('No tasks found to update.');
            return Command::SUCCESS;
        }

        // Process commit message for task reference
        $updatedTasks = $this->processCommitMessage($commitMessage);

        if ($updatedTasks > 0) {
            // Update metadata
            $this->updateMetadata();
            
            // Save the updated task file
            $this->saveTasksFile();
            
            $this->info("Updated $updatedTasks task(s) based on commit message.");
        } else {
            $this->info('No tasks were updated.');
        }

        return Command::SUCCESS;
    }

    protected function processCommitMessage($message)
    {
        $updatedCount = 0;
        
        // Common task reference patterns in commit messages
        $patterns = [
            '/\b(?:fixes|closes|resolves)\s+(?:task|issue)?\s*#?([a-zA-Z0-9_]+)\b/i',  // fixes task #123
            '/\b(?:task|issue)\s*#?([a-zA-Z0-9_]+)\s*(?:fixed|closed|resolved|completed)\b/i', // task #123 fixed
            '/\b(?:completes|implements|finishes)\s+(?:task|issue)?\s*#?([a-zA-Z0-9_]+)\b/i',  // completes task #123
            '/\[(?:task|issue)\s*#?([a-zA-Z0-9_]+)\]/' // [task #123]
        ];
        
        $taskIds = [];
        
        // Extract task IDs from commit message
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $message, $matches)) {
                foreach ($matches[1] as $match) {
                    $taskIds[] = trim($match);
                }
            }
        }
        
        // Remove duplicates
        $taskIds = array_unique($taskIds);
        
        if (empty($taskIds)) {
            // If no specific task references found, check for AI-task updates
            return $this->updateAiTasksFromCommit($message);
        }
        
        // Update referenced tasks
        foreach ($this->tasks as $index => $task) {
            if (in_array($task['id'], $taskIds)) {
                // Update the task
                $this->tasks[$index]['status'] = 'completed';
                $this->tasks[$index]['progress'] = 100;
                $this->tasks[$index]['updated_at'] = Carbon::now()->toIso8601String();
                
                // Add note about the completion
                $this->tasks[$index]['notes'][] = [
                    'content' => "Task auto-marked as completed based on git commit: " . substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''),
                    'timestamp' => Carbon::now()->toIso8601String(),
                    'author' => 'system'
                ];
                
                $updatedCount++;
            }
        }
        
        return $updatedCount;
    }

    protected function updateAiTasksFromCommit($message)
    {
        $updatedCount = 0;
        $changedFiles = $this->getChangedFilesFromLastCommit();
        
        if (empty($changedFiles)) {
            return 0;
        }
        
        // First, try to match by file paths
        foreach ($this->tasks as $index => $task) {
            // Skip if task is already completed or not AI-generated
            if ($task['status'] === 'completed' || !isset($task['is_ai_generated']) || !$task['is_ai_generated']) {
                continue;
            }
            
            // Get task description
            $description = $task['description'] ?? '';
            
            // Extract file paths from task description (typically found in AI-generated tasks)
            $filePathsInTask = [];
            if (preg_match_all('/- (.+\.(?:php|js|css|html|vue|blade\.php))/', $description, $matches)) {
                $filePathsInTask = $matches[1];
            }
            
            // Check if any of the files mentioned in the task were changed in this commit
            $fileMatch = false;
            foreach ($filePathsInTask as $file) {
                foreach ($changedFiles as $changedFile) {
                    if (Str::endsWith($changedFile, $file) || Str::endsWith($file, $changedFile)) {
                        $fileMatch = true;
                        break 2;
                    }
                }
            }
            
            // Update if file match and keyword match
            if ($fileMatch && $this->commitAddressesTask($message, $task)) {
                // Update task progress
                if ($this->tasks[$index]['progress'] < 100) {
                    $this->tasks[$index]['progress'] += 25; // Add 25% progress
                    if ($this->tasks[$index]['progress'] > 75) {
                        $this->tasks[$index]['status'] = 'in-progress';
                    }
                    if ($this->tasks[$index]['progress'] >= 100) {
                        $this->tasks[$index]['progress'] = 100;
                        $this->tasks[$index]['status'] = 'completed';
                    }
                }
                
                $this->tasks[$index]['updated_at'] = Carbon::now()->toIso8601String();
                
                // Add note about the progress update
                $this->tasks[$index]['notes'][] = [
                    'content' => "Task progress auto-updated based on git commit: " . substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''),
                    'timestamp' => Carbon::now()->toIso8601String(),
                    'author' => 'system'
                ];
                
                $updatedCount++;
            }
        }
        
        return $updatedCount;
    }

    protected function getChangedFilesFromLastCommit()
    {
        $command = "git diff-tree --no-commit-id --name-only -r HEAD";
        exec($command, $output);
        return $output;
    }

    protected function commitAddressesTask($message, $task)
    {
        // Get keywords from task title
        $title = $task['title'] ?? '';
        $feature = $task['feature'] ?? '';
        
        // Common action words in commit messages
        $actionWords = ['fix', 'improve', 'update', 'refactor', 'clean', 'enhance', 'optimize', 'standardize'];
        
        // Extract significant words from task title (excluding common words like AI, the, etc.)
        $titleWords = preg_split('/\s+/', strtolower($title));
        $titleWords = array_filter($titleWords, function($word) {
            return !in_array($word, ['[ai]', 'the', 'a', 'and', 'or', 'in', 'for', 'to', 'of', 'on', 'with', 'by']);
        });
        
        // Check if any significant word from title or feature appears in the commit message
        $messageLower = strtolower($message);
        
        foreach ($titleWords as $word) {
            if (strlen($word) < 4) continue; // Skip short words
            
            if (Str::contains($messageLower, $word)) {
                return true;
            }
        }
        
        // Check for the feature name
        if (strlen($feature) >= 3 && Str::contains($messageLower, strtolower($feature))) {
            return true;
        }
        
        // Check for common action words along with feature
        foreach ($actionWords as $action) {
            if (Str::contains($messageLower, $action) && Str::contains($messageLower, strtolower($feature))) {
                return true;
            }
        }
        
        return false;
    }

    protected function updateMetadata()
    {
        $completedTasks = 0;
        
        foreach ($this->tasks as $task) {
            if ($task['status'] === 'completed') {
                $completedTasks++;
            }
        }
        
        $this->metadata['total_tasks'] = count($this->tasks);
        $this->metadata['completed_tasks'] = $completedTasks;
        $this->metadata['last_updated'] = Carbon::now()->toIso8601String();
    }

    protected function saveTasksFile()
    {
        $data = [
            'metadata' => $this->metadata,
            'tasks' => $this->tasks
        ];
        
        File::put($this->taskFile, json_encode($data, JSON_PRETTY_PRINT));
    }
} 