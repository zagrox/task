<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;

class TaskManager extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:manage 
                            {action : The action to perform (add|list|show|update|note|delete|report)}
                            {--id= : Task ID for show, update, note, delete actions}
                            {--title= : Title for new task}
                            {--description= : Description for new task}
                            {--assignee=user : Assignee for new task (user|ai)}
                            {--status=pending : Status for task (pending|in-progress|completed)}
                            {--priority=medium : Priority for task (low|medium|high)}
                            {--due= : Due date for task (YYYY-MM-DD)}
                            {--feature= : Related feature}
                            {--phase= : Related phase}
                            {--tags= : Comma-separated tags}
                            {--progress= : Progress percentage (0-100)}
                            {--est-hours= : Estimated hours}
                            {--actual-hours= : Actual hours spent}
                            {--field= : Field name for update action}
                            {--value= : New value for update action}
                            {--note= : Note content for note action}
                            {--report-type=summary : Report type (summary|progress|feature|phase|due|time)}
                            {--format=json : Export format (json|csv|md)}
                            {--output= : Output file path for export}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage tasks within the MailZila project';

    /**
     * Task file path
     */
    protected $tasksFile;

    /**
     * Backup directory path
     */
    protected $backupDir;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->tasksFile = base_path('project-management/tasks.json');
        $this->backupDir = base_path('project-management/backups');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Ensure tasks directory exists
        if (!File::exists(dirname($this->tasksFile))) {
            File::makeDirectory(dirname($this->tasksFile), 0755, true);
        }

        // Ensure backup directory exists
        if (!File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);
        }

        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
            $this->createTasksFile();
        }

        // Get action
        $action = $this->argument('action');

        // Execute requested action
        switch ($action) {
            case 'add':
                return $this->addTask();
            case 'list':
                return $this->listTasks();
            case 'show':
                return $this->showTask();
            case 'update':
                return $this->updateTask();
            case 'note':
                return $this->addNote();
            case 'delete':
                return $this->deleteTask();
            case 'report':
                return $this->generateReport();
            case 'export':
                return $this->exportTasks();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    /**
     * Create initial tasks file
     */
    protected function createTasksFile()
    {
        $initialContent = [
            'metadata' => [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'user_tasks' => 0,
                'ai_tasks' => 0,
                'last_updated' => Carbon::now()->toIso8601String()
            ],
            'next_id' => 1,
            'tasks' => []
        ];

        File::put($this->tasksFile, json_encode($initialContent, JSON_PRETTY_PRINT));
        $this->info('Created new tasks file at ' . $this->tasksFile);
    }

    /**
     * Create a backup of the tasks file
     */
    protected function createBackup()
    {
        $backupFile = $this->backupDir . '/tasks_' . Carbon::now()->format('Ymd_His') . '.json';
        File::copy($this->tasksFile, $backupFile);
        $this->info('Backup created: ' . $backupFile);
    }

    /**
     * Update metadata in the tasks file
     */
    protected function updateMetadata()
    {
        $tasks = $this->loadTasks();
        
        $tasks['metadata'] = [
            'total_tasks' => count($tasks['tasks']),
            'completed_tasks' => count(array_filter($tasks['tasks'], function($task) {
                return $task['status'] === 'completed';
            })),
            'user_tasks' => count(array_filter($tasks['tasks'], function($task) {
                return $task['assignee'] === 'user';
            })),
            'ai_tasks' => count(array_filter($tasks['tasks'], function($task) {
                return $task['assignee'] === 'ai';
            })),
            'last_updated' => Carbon::now()->toIso8601String()
        ];

        $this->saveTasks($tasks);
    }

    /**
     * Load tasks from file
     */
    protected function loadTasks()
    {
        return json_decode(File::get($this->tasksFile), true);
    }

    /**
     * Save tasks to file
     */
    protected function saveTasks($tasks)
    {
        File::put($this->tasksFile, json_encode($tasks, JSON_PRETTY_PRINT));
    }

    /**
     * Add a new task
     */
    protected function addTask()
    {
        // Validate required parameters
        if (!$this->option('title') || !$this->option('description')) {
            $this->error('Title and description are required');
            return 1;
        }

        // Create backup before modifying
        $this->createBackup();

        // Get tasks
        $tasks = $this->loadTasks();
        $nextId = $tasks['next_id'];

        // Process tags
        $tags = [];
        if ($this->option('tags')) {
            $tags = array_map('trim', explode(',', $this->option('tags')));
        }

        // Add new task
        $tasks['tasks'][] = [
            'id' => $nextId,
            'title' => $this->option('title'),
            'description' => $this->option('description'),
            'assignee' => $this->option('assignee'),
            'status' => $this->option('status'),
            'priority' => $this->option('priority'),
            'created_at' => Carbon::now()->toIso8601String(),
            'updated_at' => Carbon::now()->toIso8601String(),
            'due_date' => $this->option('due') ?: null,
            'related_feature' => $this->option('feature') ?: null,
            'related_phase' => $this->option('phase') ?: null,
            'dependencies' => [],
            'progress' => (int)$this->option('progress') ?: 0,
            'notes' => [],
            'tags' => $tags,
            'estimated_hours' => (float)$this->option('est-hours') ?: 0,
            'actual_hours' => (float)$this->option('actual-hours') ?: 0,
        ];

        // Increment next ID
        $tasks['next_id'] = $nextId + 1;

        // Save tasks
        $this->saveTasks($tasks);

        // Update metadata
        $this->updateMetadata();

        $this->info("Task #{$nextId} added successfully");
        return 0;
    }

    /**
     * List tasks with optional filtering
     */
    protected function listTasks()
    {
        $tasks = $this->loadTasks();
        $filteredTasks = $tasks['tasks'];

        // Apply filters if provided
        if ($this->option('id')) {
            $id = (int)$this->option('id');
            $filteredTasks = array_filter($filteredTasks, function($task) use ($id) {
                return $task['id'] === $id;
            });
        }

        if ($this->option('status')) {
            $status = $this->option('status');
            $filteredTasks = array_filter($filteredTasks, function($task) use ($status) {
                return $task['status'] === $status;
            });
        }

        if ($this->option('assignee')) {
            $assignee = $this->option('assignee');
            $filteredTasks = array_filter($filteredTasks, function($task) use ($assignee) {
                return $task['assignee'] === $assignee;
            });
        }

        if ($this->option('priority')) {
            $priority = $this->option('priority');
            $filteredTasks = array_filter($filteredTasks, function($task) use ($priority) {
                return $task['priority'] === $priority;
            });
        }

        if ($this->option('feature')) {
            $feature = $this->option('feature');
            $filteredTasks = array_filter($filteredTasks, function($task) use ($feature) {
                return $task['related_feature'] === $feature;
            });
        }

        if ($this->option('phase')) {
            $phase = $this->option('phase');
            $filteredTasks = array_filter($filteredTasks, function($task) use ($phase) {
                return $task['related_phase'] === $phase;
            });
        }

        if ($this->option('due')) {
            $due = $this->option('due');
            $filteredTasks = array_filter($filteredTasks, function($task) use ($due) {
                return $task['due_date'] === $due;
            });
        }

        // Sort by ID
        usort($filteredTasks, function($a, $b) {
            return $a['id'] - $b['id'];
        });

        // Display tasks
        $this->info("Task List (" . count($filteredTasks) . " tasks):");
        $headers = ['ID', 'Title', 'Status', 'Priority', 'Assignee', 'Due Date', 'Progress'];
        
        $rows = array_map(function($task) {
            return [
                $task['id'],
                $task['title'],
                $task['status'],
                $task['priority'],
                $task['assignee'],
                $task['due_date'] ?: 'Not set',
                $task['progress'] . '%'
            ];
        }, $filteredTasks);

        $this->table($headers, $rows);
        return 0;
    }

    /**
     * Show detailed information about a specific task
     */
    protected function showTask()
    {
        if (!$this->option('id')) {
            $this->error('Task ID is required');
            return 1;
        }

        $id = (int)$this->option('id');
        $tasks = $this->loadTasks();
        
        // Find the task
        $task = null;
        foreach ($tasks['tasks'] as $t) {
            if ($t['id'] === $id) {
                $task = $t;
                break;
            }
        }

        if (!$task) {
            $this->error("Task #{$id} not found");
            return 1;
        }

        // Display task details
        $this->info("============ Task #{$id} ============");
        $this->line("Title: " . $task['title']);
        $this->line("Description: " . $task['description']);
        $this->line("Status: " . $task['status']);
        $this->line("Priority: " . $task['priority']);
        $this->line("Assignee: " . $task['assignee']);
        $this->line("Progress: " . $task['progress'] . "%");
        $this->line("Created: " . $task['created_at']);
        $this->line("Updated: " . $task['updated_at']);
        $this->line("Due Date: " . ($task['due_date'] ?: 'Not set'));
        $this->line("Feature: " . ($task['related_feature'] ?: 'Not set'));
        $this->line("Phase: " . ($task['related_phase'] ?: 'Not set'));
        $this->line("Estimated Hours: " . $task['estimated_hours']);
        $this->line("Actual Hours: " . $task['actual_hours']);
        $this->line("Tags: " . implode(', ', $task['tags']));
        $this->line("Dependencies: " . (empty($task['dependencies']) ? 'None' : implode(', ', $task['dependencies'])));

        // Display notes
        if (!empty($task['notes'])) {
            $this->line("\nNotes:");
            foreach ($task['notes'] as $note) {
                $this->line("[" . $note['timestamp'] . "] " . $note['content']);
            }
        } else {
            $this->line("\nNotes: None");
        }
        
        $this->line("====================================");
        return 0;
    }

    /**
     * Update a task
     */
    protected function updateTask()
    {
        if (!$this->option('id') || !$this->option('field') || !$this->hasOption('value')) {
            $this->error('Task ID, field, and value are required');
            return 1;
        }

        $id = (int)$this->option('id');
        $field = $this->option('field');
        $value = $this->option('value');

        $tasks = $this->loadTasks();
        
        // Find the task
        $taskIndex = null;
        foreach ($tasks['tasks'] as $index => $task) {
            if ($task['id'] === $id) {
                $taskIndex = $index;
                break;
            }
        }

        if ($taskIndex === null) {
            $this->error("Task #{$id} not found");
            return 1;
        }

        // Create backup before modifying
        $this->createBackup();
        
        // Update field based on type
        switch ($field) {
            case 'title':
            case 'description':
            case 'status':
            case 'priority':
            case 'assignee':
            case 'due_date':
            case 'related_feature':
            case 'related_phase':
                $tasks['tasks'][$taskIndex][$field] = $value;
                break;
                
            case 'progress':
            case 'estimated_hours':
            case 'actual_hours':
                if (!is_numeric($value)) {
                    $this->error("$field must be a number");
                    return 1;
                }
                $tasks['tasks'][$taskIndex][$field] = (float)$value;
                break;
                
            case 'tags':
                $tasks['tasks'][$taskIndex][$field] = array_map('trim', explode(',', $value));
                break;
                
            case 'dependencies':
                $tasks['tasks'][$taskIndex][$field] = array_map('intval', array_filter(
                    array_map('trim', explode(',', $value)),
                    function($v) { return is_numeric($v); }
                ));
                break;
                
            default:
                $this->error("Unknown field: $field");
                return 1;
        }

        // Update timestamp
        $tasks['tasks'][$taskIndex]['updated_at'] = Carbon::now()->toIso8601String();

        // Save tasks
        $this->saveTasks($tasks);

        // Update metadata if status was changed
        if ($field === 'status' || $field === 'assignee') {
            $this->updateMetadata();
        }

        $this->info("Task #{$id} updated: $field set to $value");
        return 0;
    }

    /**
     * Add a note to a task
     */
    protected function addNote()
    {
        if (!$this->option('id') || !$this->option('note')) {
            $this->error('Task ID and note content are required');
            return 1;
        }

        $id = (int)$this->option('id');
        $noteContent = $this->option('note');

        $tasks = $this->loadTasks();
        
        // Find the task
        $taskIndex = null;
        foreach ($tasks['tasks'] as $index => $task) {
            if ($task['id'] === $id) {
                $taskIndex = $index;
                break;
            }
        }

        if ($taskIndex === null) {
            $this->error("Task #{$id} not found");
            return 1;
        }

        // Create backup before modifying
        $this->createBackup();
        
        // Add note
        $tasks['tasks'][$taskIndex]['notes'][] = [
            'content' => $noteContent,
            'timestamp' => Carbon::now()->toIso8601String()
        ];
        
        // Update timestamp
        $tasks['tasks'][$taskIndex]['updated_at'] = Carbon::now()->toIso8601String();

        // Save tasks
        $this->saveTasks($tasks);

        $this->info("Note added to Task #{$id}");
        return 0;
    }

    /**
     * Delete a task
     */
    protected function deleteTask()
    {
        if (!$this->option('id')) {
            $this->error('Task ID is required');
            return 1;
        }

        $id = (int)$this->option('id');
        $tasks = $this->loadTasks();
        
        // Find the task
        $taskExists = false;
        foreach ($tasks['tasks'] as $task) {
            if ($task['id'] === $id) {
                $taskExists = true;
                break;
            }
        }

        if (!$taskExists) {
            $this->error("Task #{$id} not found");
            return 1;
        }

        // Create backup before modifying
        $this->createBackup();
        
        // Remove task
        $tasks['tasks'] = array_values(array_filter($tasks['tasks'], function($task) use ($id) {
            return $task['id'] !== $id;
        }));

        // Save tasks
        $this->saveTasks($tasks);
        
        // Update metadata
        $this->updateMetadata();

        $this->info("Task #{$id} deleted");
        return 0;
    }

    /**
     * Generate a report
     */
    protected function generateReport()
    {
        $reportType = $this->option('report-type');
        $tasks = $this->loadTasks();
        
        switch ($reportType) {
            case 'summary':
                $this->info("========== Task Summary Report ==========");
                $this->line("Total Tasks: " . count($tasks['tasks']));
                
                $completed = count(array_filter($tasks['tasks'], function($task) {
                    return $task['status'] === 'completed';
                }));
                $this->line("Completed: " . $completed);
                
                $inProgress = count(array_filter($tasks['tasks'], function($task) {
                    return $task['status'] === 'in-progress';
                }));
                $this->line("In Progress: " . $inProgress);
                
                $pending = count(array_filter($tasks['tasks'], function($task) {
                    return $task['status'] === 'pending';
                }));
                $this->line("Pending: " . $pending);
                
                $userTasks = count(array_filter($tasks['tasks'], function($task) {
                    return $task['assignee'] === 'user';
                }));
                $this->line("Assigned to User: " . $userTasks);
                
                $aiTasks = count(array_filter($tasks['tasks'], function($task) {
                    return $task['assignee'] === 'ai';
                }));
                $this->line("Assigned to AI: " . $aiTasks);
                
                $this->line("========================================");
                break;
                
            case 'progress':
                $this->info("========== Progress Report ==========");
                
                $statuses = [];
                foreach ($tasks['tasks'] as $task) {
                    $status = $task['status'];
                    if (!isset($statuses[$status])) {
                        $statuses[$status] = 0;
                    }
                    $statuses[$status]++;
                }
                
                foreach ($statuses as $status => $count) {
                    $this->line("$status: $count task(s)");
                }
                
                // Calculate average progress
                $totalProgress = array_reduce($tasks['tasks'], function($carry, $task) {
                    return $carry + $task['progress'];
                }, 0);
                
                $avgProgress = count($tasks['tasks']) > 0 ? $totalProgress / count($tasks['tasks']) : 0;
                $this->line("Average Progress: " . number_format($avgProgress, 1) . "%");
                
                $this->line("=====================================");
                break;
                
            case 'feature':
                $this->info("========== Feature Report ==========");
                
                $features = [];
                foreach ($tasks['tasks'] as $task) {
                    $feature = $task['related_feature'] ?: 'Unassigned';
                    if (!isset($features[$feature])) {
                        $features[$feature] = 0;
                    }
                    $features[$feature]++;
                }
                
                foreach ($features as $feature => $count) {
                    $this->line("$feature: $count task(s)");
                }
                
                $this->line("=====================================");
                break;
                
            case 'phase':
                $this->info("========== Phase Report ==========");
                
                $phases = [];
                foreach ($tasks['tasks'] as $task) {
                    $phase = $task['related_phase'] ?: 'Unassigned';
                    if (!isset($phases[$phase])) {
                        $phases[$phase] = 0;
                    }
                    $phases[$phase]++;
                }
                
                foreach ($phases as $phase => $count) {
                    $this->line("$phase: $count task(s)");
                }
                
                $this->line("=====================================");
                break;
                
            case 'due':
                $this->info("========== Due Date Report ==========");
                
                $today = Carbon::today()->format('Y-m-d');
                
                // Overdue tasks
                $overdue = array_filter($tasks['tasks'], function($task) use ($today) {
                    return $task['due_date'] && $task['due_date'] < $today && $task['status'] !== 'completed';
                });
                
                $this->line("Overdue Tasks: " . count($overdue));
                
                // Tasks due today
                $dueToday = array_filter($tasks['tasks'], function($task) use ($today) {
                    return $task['due_date'] === $today;
                });
                
                $this->line("\nTasks Due Today:");
                if (count($dueToday) > 0) {
                    foreach ($dueToday as $task) {
                        $this->line("#{$task['id']}: {$task['title']} [{$task['status']}]");
                    }
                } else {
                    $this->line("None");
                }
                
                // Upcoming due dates
                $this->line("\nUpcoming Due Dates (Next 7 Days):");
                for ($i = 1; $i <= 7; $i++) {
                    $futureDate = Carbon::today()->addDays($i)->format('Y-m-d');
                    $dueTasks = array_filter($tasks['tasks'], function($task) use ($futureDate) {
                        return $task['due_date'] === $futureDate;
                    });
                    
                    if (count($dueTasks) > 0) {
                        $this->line("Due on $futureDate:");
                        foreach ($dueTasks as $task) {
                            $this->line("#{$task['id']}: {$task['title']} [{$task['status']}]");
                        }
                    }
                }
                
                $this->line("=====================================");
                break;
                
            case 'time':
                $this->info("========== Time Tracking Report ==========");
                
                $totalEstimated = array_reduce($tasks['tasks'], function($carry, $task) {
                    return $carry + $task['estimated_hours'];
                }, 0);
                
                $totalActual = array_reduce($tasks['tasks'], function($carry, $task) {
                    return $carry + $task['actual_hours'];
                }, 0);
                
                $this->line("Total Estimated Hours: " . number_format($totalEstimated, 1));
                $this->line("Total Actual Hours: " . number_format($totalActual, 1));
                
                if ($totalEstimated > 0) {
                    $ratio = $totalActual / $totalEstimated;
                    $this->line("Time Utilization Ratio: " . number_format($ratio, 2));
                }
                
                // Time per feature
                $this->line("\nTime per Feature:");
                $featureTime = [];
                
                foreach ($tasks['tasks'] as $task) {
                    $feature = $task['related_feature'] ?: 'Unassigned';
                    if (!isset($featureTime[$feature])) {
                        $featureTime[$feature] = [
                            'estimated' => 0,
                            'actual' => 0
                        ];
                    }
                    $featureTime[$feature]['estimated'] += $task['estimated_hours'];
                    $featureTime[$feature]['actual'] += $task['actual_hours'];
                }
                
                foreach ($featureTime as $feature => $time) {
                    $this->line("$feature: Estimated {$time['estimated']}h, Actual {$time['actual']}h");
                }
                
                $this->line("=======================================");
                break;
                
            default:
                $this->error("Unknown report type: $reportType");
                return 1;
        }
        
        return 0;
    }

    /**
     * Export tasks to different formats
     */
    protected function exportTasks()
    {
        $format = $this->option('format');
        $outputFile = $this->option('output');
        
        if (!$outputFile) {
            $outputFile = base_path("project-management/exported_tasks_" . Carbon::now()->format('Ymd') . ".$format");
        }
        
        $tasks = $this->loadTasks();
        
        switch ($format) {
            case 'json':
                File::put($outputFile, json_encode($tasks, JSON_PRETTY_PRINT));
                break;
                
            case 'csv':
                $csv = "id,title,description,assignee,status,priority,created_at,updated_at,due_date,related_feature,related_phase,progress,estimated_hours,actual_hours\n";
                
                foreach ($tasks['tasks'] as $task) {
                    $csv .= implode(',', [
                        $task['id'],
                        '"' . str_replace('"', '""', $task['title']) . '"',
                        '"' . str_replace('"', '""', $task['description']) . '"',
                        $task['assignee'],
                        $task['status'],
                        $task['priority'],
                        $task['created_at'],
                        $task['updated_at'],
                        $task['due_date'] ?: '',
                        $task['related_feature'] ?: '',
                        $task['related_phase'] ?: '',
                        $task['progress'],
                        $task['estimated_hours'],
                        $task['actual_hours']
                    ]) . "\n";
                }
                
                File::put($outputFile, $csv);
                break;
                
            case 'md':
                $md = "# MailZila Task Management Export\n";
                $md .= "Generated on: " . Carbon::now()->format('Y-m-d H:i:s') . "\n\n";
                
                // Add summary
                $md .= "## Summary\n";
                $completed = count(array_filter($tasks['tasks'], function($task) {
                    return $task['status'] === 'completed';
                }));
                
                $md .= "- Total Tasks: " . count($tasks['tasks']) . "\n";
                $md .= "- Completed Tasks: " . $completed . "\n";
                $md .= "- Completion Rate: " . (count($tasks['tasks']) > 0 ? round(($completed * 100) / count($tasks['tasks'])) : 0) . "%\n\n";
                
                // Add task details
                $md .= "## Tasks\n\n";
                
                foreach ($tasks['tasks'] as $task) {
                    $md .= "### #{$task['id']}: {$task['title']}\n\n";
                    $md .= "**Status:** {$task['status']}  \n";
                    $md .= "**Priority:** {$task['priority']}  \n";
                    $md .= "**Assignee:** {$task['assignee']}  \n";
                    $md .= "**Progress:** {$task['progress']}%  \n";
                    $md .= "**Due Date:** " . ($task['due_date'] ?: "Not set") . "  \n\n";
                    $md .= $task['description'] . "\n\n";
                    $md .= "**Created:** {$task['created_at']}  \n";
                    $md .= "**Last Updated:** {$task['updated_at']}  \n";
                    $md .= "**Feature:** " . ($task['related_feature'] ?: "Not set") . "  \n";
                    $md .= "**Phase:** " . ($task['related_phase'] ?: "Not set") . "  \n";
                    $md .= "**Estimated Hours:** {$task['estimated_hours']}  \n";
                    $md .= "**Actual Hours:** {$task['actual_hours']}  \n\n";
                    $md .= "**Tags:** " . implode(', ', $task['tags']) . "  \n\n";
                    $md .= "**Dependencies:** " . (empty($task['dependencies']) ? "None" : implode(', ', $task['dependencies'])) . "\n\n";
                    
                    // Notes
                    $md .= "#### Notes:\n";
                    if (!empty($task['notes'])) {
                        foreach ($task['notes'] as $note) {
                            $md .= "- [{$note['timestamp']}] {$note['content']}\n";
                        }
                    } else {
                        $md .= "No notes.\n";
                    }
                    
                    $md .= "\n---\n\n";
                }
                
                File::put($outputFile, $md);
                break;
                
            default:
                $this->error("Unsupported export format: $format");
                return 1;
        }
        
        $this->info("Tasks exported to $outputFile");
        return 0;
    }
} 