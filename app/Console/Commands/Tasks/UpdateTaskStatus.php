<?php

namespace App\Console\Commands\Tasks;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UpdateTaskStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:update-status
                            {--due-warning : Mark tasks as at-risk that are due soon}
                            {--auto-complete : Auto-complete tasks with 100% progress}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update task statuses based on configured rules';

    /**
     * Path to tasks file
     */
    protected $tasksFile;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->tasksFile = storage_path('app/tasks.json');
        
        // Check if tasks file exists
        if (!File::exists($this->tasksFile)) {
            $this->error('Tasks file not found. Please initialize tasks first.');
            return 1;
        }
        
        $tasksData = json_decode(File::get($this->tasksFile), true);
        
        if (empty($tasksData['tasks'])) {
            $this->info('No tasks found to update.');
            return 0;
        }
        
        $updated = 0;
        
        // Process each task
        foreach ($tasksData['tasks'] as $key => $task) {
            $isUpdated = false;
            
            // Auto-complete tasks with 100% progress
            if ($this->option('auto-complete') && $task['progress'] == 100 && $task['status'] != 'completed') {
                $tasksData['tasks'][$key]['status'] = 'completed';
                $tasksData['tasks'][$key]['updated_at'] = date('Y-m-d H:i:s');
                
                // Add a note about automatic completion
                $tasksData['tasks'][$key]['notes'][] = [
                    'content' => 'Task automatically marked as completed due to 100% progress.',
                    'added_at' => date('Y-m-d H:i:s'),
                    'added_by' => 'System'
                ];
                
                $isUpdated = true;
                $this->line("Task {$task['id']} automatically marked as completed.");
            }
            
            // Mark tasks as at-risk if due date is approaching
            if ($this->option('due-warning') && isset($task['due_date']) && $task['status'] != 'completed') {
                $dueDate = strtotime($task['due_date']);
                $today = strtotime('today');
                $twoDaysFromNow = strtotime('+2 days');
                
                // If due date is within the next 2 days and task is not at-risk
                if ($dueDate > $today && $dueDate <= $twoDaysFromNow && !in_array('at-risk', $task['tags'] ?? [])) {
                    $tasksData['tasks'][$key]['tags'][] = 'at-risk';
                    $tasksData['tasks'][$key]['updated_at'] = date('Y-m-d H:i:s');
                    
                    // Add a note about at-risk status
                    $tasksData['tasks'][$key]['notes'][] = [
                        'content' => 'Task automatically marked as at-risk due to approaching due date.',
                        'added_at' => date('Y-m-d H:i:s'),
                        'added_by' => 'System'
                    ];
                    
                    $isUpdated = true;
                    $this->line("Task {$task['id']} marked as at-risk (due soon).");
                }
                
                // If due date has passed and task is not overdue
                if ($dueDate < $today && !in_array('overdue', $task['tags'] ?? [])) {
                    $tasksData['tasks'][$key]['tags'][] = 'overdue';
                    $tasksData['tasks'][$key]['updated_at'] = date('Y-m-d H:i:s');
                    
                    // Add a note about overdue status
                    $tasksData['tasks'][$key]['notes'][] = [
                        'content' => 'Task automatically marked as overdue.',
                        'added_at' => date('Y-m-d H:i:s'),
                        'added_by' => 'System'
                    ];
                    
                    $isUpdated = true;
                    $this->line("Task {$task['id']} marked as overdue.");
                }
            }
            
            if ($isUpdated) {
                $updated++;
            }
        }
        
        if ($updated > 0) {
            // Update metadata
            $completedTasks = count(array_filter($tasksData['tasks'], function($task) {
                return $task['status'] === 'completed';
            }));
            
            $tasksData['metadata']['completed_tasks'] = $completedTasks;
            $tasksData['metadata']['last_updated'] = date('Y-m-d H:i:s');
            
            // Save updated tasks data
            File::put($this->tasksFile, json_encode($tasksData, JSON_PRETTY_PRINT));
            
            $this->info("Updated status for $updated tasks.");
        } else {
            $this->info('No task statuses needed updating.');
        }
        
        return 0;
    }
} 