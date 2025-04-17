<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

class ProjectDashboard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:dashboard 
                            {--user= : Filter tasks by user (user/ai)}
                            {--status= : Filter tasks by status (pending/in-progress/completed/blocked)}
                            {--feature= : Filter tasks by feature name}
                            {--phase= : Filter tasks by phase ID}
                            {--due= : Show tasks due within days (e.g., 7 for next week)}
                            {--sort=priority : Sort tasks by field (priority/due/progress/status)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display project dashboard with tasks and progress';

    /**
     * Path to tasks JSON file
     *
     * @var string
     */
    protected $tasksFile;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->tasksFile = base_path('project-management/tasks.json');
        
        // Check if tasks file exists
        if (!File::exists($this->tasksFile)) {
            $this->error('Task data not found. Initialize with: scripts/task-manager.sh add "Task title" "Description" user high 2023-08-20 project-management P1-SETUP');
            return 1;
        }
        
        // Load task data
        $tasksData = json_decode(File::get($this->tasksFile), true);
        
        $this->displayHeader();
        $this->displaySummary($tasksData);
        $this->displayTasks($tasksData);
        
        return 0;
    }
    
    /**
     * Display dashboard header
     */
    protected function displayHeader()
    {
        $this->newLine();
        $this->line('<fg=blue;options=bold>╔══════════════════════════════════════════════════════════╗</>');
        $this->line('<fg=blue;options=bold>║               MAILZILA PROJECT DASHBOARD                 ║</>');
        $this->line('<fg=blue;options=bold>╚══════════════════════════════════════════════════════════╝</>');
        $this->newLine();
    }
    
    /**
     * Display project summary
     */
    protected function displaySummary($tasksData)
    {
        $metadata = $tasksData['metadata'];
        $completion = $metadata['total_tasks'] > 0 
            ? round(($metadata['completed_tasks'] / $metadata['total_tasks']) * 100) 
            : 0;
        
        $this->line('<fg=yellow;options=bold>PROJECT SUMMARY</>');
        $this->line('─────────────────────────────────────────────────────────');
        
        // Build progress bar
        $progressBar = $this->getProgressBar($completion);
        
        $this->line(" 📊 <fg=white>Overall Progress:</> $progressBar <fg=white>$completion%</>");
        $this->line(" 📋 <fg=white>Total Tasks:</> {$metadata['total_tasks']} ({$metadata['completed_tasks']} completed)");
        $this->line(" 👤 <fg=white>User Tasks:</> {$metadata['user_tasks']}");
        $this->line(" 🤖 <fg=white>AI Tasks:</> {$metadata['ai_tasks']}");
        $this->line(" 📅 <fg=white>Last Updated:</> {$metadata['last_updated']}");
        
        $this->newLine();
        
        // Also display feature progress
        $this->displayFeatureSummary($tasksData);
    }
    
    /**
     * Display feature progress summary
     */
    protected function displayFeatureSummary($tasksData)
    {
        // Group tasks by feature
        $features = [];
        foreach ($tasksData['tasks'] as $task) {
            $feature = $task['related_feature'];
            if (!isset($features[$feature])) {
                $features[$feature] = [
                    'total' => 0,
                    'completed' => 0
                ];
            }
            
            $features[$feature]['total']++;
            if ($task['status'] === 'completed') {
                $features[$feature]['completed']++;
            }
        }
        
        if (count($features) > 0) {
            $this->line('<fg=yellow;options=bold>FEATURE PROGRESS</>');
            $this->line('─────────────────────────────────────────────────────────');
            
            foreach ($features as $feature => $stats) {
                $completion = $stats['total'] > 0 
                    ? round(($stats['completed'] / $stats['total']) * 100) 
                    : 0;
                $progressBar = $this->getProgressBar($completion);
                
                $this->line(" <fg=white>$feature:</> $progressBar <fg=white>{$stats['completed']}/{$stats['total']} ($completion%)</>");
            }
            
            $this->newLine();
        }
    }
    
    /**
     * Generate progress bar string
     */
    protected function getProgressBar($percentage)
    {
        $barLength = 20;
        $completedLength = round(($percentage / 100) * $barLength);
        $remainingLength = $barLength - $completedLength;
        
        $completedColor = 'green';
        if ($percentage < 30) {
            $completedColor = 'red';
        } elseif ($percentage < 70) {
            $completedColor = 'yellow';
        }
        
        return '<fg=' . $completedColor . '>' . str_repeat('█', $completedLength) . '</>' . 
               '<fg=white>' . str_repeat('░', $remainingLength) . '</>';
    }
    
    /**
     * Display filtered tasks
     */
    protected function displayTasks($tasksData)
    {
        $tasks = $tasksData['tasks'];
        
        // Apply filters
        $tasks = $this->filterTasks($tasks);
        
        if (empty($tasks)) {
            $this->warn('No tasks match the specified criteria.');
            return;
        }
        
        // Sort tasks
        $tasks = $this->sortTasks($tasks);
        
        $this->line('<fg=yellow;options=bold>TASKS</>');
        $this->line('─────────────────────────────────────────────────────────');
        
        $headers = ['ID', 'Title', 'Assignee', 'Status', 'Priority', 'Progress', 'Due Date'];
        $rows = [];
        
        foreach ($tasks as $task) {
            // Format status with color
            $status = $task['status'];
            $statusColor = match($status) {
                'pending' => '<fg=yellow>',
                'in-progress' => '<fg=blue>',
                'completed' => '<fg=green>',
                'blocked' => '<fg=red>',
                default => '<fg=white>'
            };
            $status = $statusColor . ucfirst($status) . '</>';
            
            // Format priority
            $priorityColor = match($task['priority']) {
                'high' => '<fg=red>',
                'medium' => '<fg=yellow>',
                'low' => '<fg=green>',
                default => '<fg=white>'
            };
            $priority = $priorityColor . ucfirst($task['priority']) . '</>';
            
            // Format assignee
            $assignee = $task['assignee'] === 'user' ? '👤 User' : '🤖 AI';
            
            // Get progress bar for progress
            $progressBar = $this->getProgressBar($task['progress']) . " {$task['progress']}%";
            
            // Format due date with warning if close
            $dueDate = $task['due_date'];
            if ($task['status'] !== 'completed') {
                $daysToDue = Carbon::parse($dueDate)->diffInDays(Carbon::now());
                if (Carbon::parse($dueDate)->isPast()) {
                    $dueDate = "<fg=red>$dueDate</> (overdue)";
                } elseif ($daysToDue <= 2) {
                    $dueDate = "<fg=red>$dueDate</> (soon)";
                } elseif ($daysToDue <= 7) {
                    $dueDate = "<fg=yellow>$dueDate</>";
                }
            }
            
            $rows[] = [
                $task['id'], 
                $task['title'],
                $assignee,
                $status,
                $priority,
                $progressBar,
                $dueDate
            ];
        }
        
        $this->table($headers, $rows);
    }
    
    /**
     * Filter tasks based on command options
     */
    protected function filterTasks($tasks)
    {
        $user = $this->option('user');
        $status = $this->option('status');
        $feature = $this->option('feature');
        $phase = $this->option('phase');
        $due = $this->option('due');
        
        if ($user) {
            $tasks = array_filter($tasks, function($task) use ($user) {
                return $task['assignee'] == $user;
            });
        }
        
        if ($status) {
            $tasks = array_filter($tasks, function($task) use ($status) {
                return $task['status'] == $status;
            });
        }
        
        if ($feature) {
            $tasks = array_filter($tasks, function($task) use ($feature) {
                return stripos($task['related_feature'], $feature) !== false;
            });
        }
        
        if ($phase) {
            $tasks = array_filter($tasks, function($task) use ($phase) {
                return $task['related_phase'] == $phase;
            });
        }
        
        if ($due) {
            $dueDate = Carbon::now()->addDays(intval($due));
            $tasks = array_filter($tasks, function($task) use ($dueDate) {
                return $task['status'] !== 'completed' && 
                       Carbon::parse($task['due_date'])->lte($dueDate);
            });
        }
        
        return $tasks;
    }
    
    /**
     * Sort tasks based on command options
     */
    protected function sortTasks($tasks)
    {
        $sort = $this->option('sort');
        
        usort($tasks, function($a, $b) use ($sort) {
            switch ($sort) {
                case 'priority':
                    $priorities = ['high' => 0, 'medium' => 1, 'low' => 2];
                    return $priorities[$a['priority']] <=> $priorities[$b['priority']];
                
                case 'due':
                    return Carbon::parse($a['due_date'])->getTimestamp() <=> 
                           Carbon::parse($b['due_date'])->getTimestamp();
                
                case 'progress':
                    return $b['progress'] <=> $a['progress'];
                    
                case 'status':
                    $statuses = ['blocked' => 0, 'pending' => 1, 'in-progress' => 2, 'completed' => 3];
                    return $statuses[$a['status']] <=> $statuses[$b['status']];
                    
                default:
                    return $a['id'] <=> $b['id'];
            }
        });
        
        return $tasks;
    }
} 