<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GenerateAiTasks extends Command
{
    protected $signature = 'tasks:generate-ai
                            {--days=7 : Number of days to analyze}
                            {--min-changes=5 : Minimum number of changes to trigger task generation}';

    protected $description = 'Generate AI tasks based on git commit analysis';

    protected $taskFile;
    protected $tasks = [];
    protected $metadata = [];

    public function __construct()
    {
        parent::__construct();
        $this->taskFile = base_path('project-management/tasks.json');
    }

    public function handle()
    {
        $this->info('Analyzing git commits...');

        // Check if task file exists and initialize if needed
        if (!File::exists($this->taskFile)) {
            $this->initializeTasksFile();
        }

        // Load existing tasks
        $tasksData = json_decode(File::get($this->taskFile), true);
        $this->tasks = $tasksData['tasks'] ?? [];
        $this->metadata = $tasksData['metadata'] ?? [];

        // Get the number of days to analyze from options
        $days = $this->option('days');
        $minChanges = $this->option('min-changes');

        // Get recent git commits and analyze them
        $sinceDate = Carbon::now()->subDays($days)->format('Y-m-d');
        $commits = $this->getGitCommits($sinceDate);
        $fileChanges = $this->analyzeCommits($commits);

        // Generate tasks based on analysis
        $generatedTasks = $this->generateTasksFromAnalysis($fileChanges, $minChanges);
        
        if (count($generatedTasks) > 0) {
            // Add generated tasks to the task list
            foreach ($generatedTasks as $task) {
                $this->tasks[] = $task;
            }
            
            // Update metadata
            $this->updateMetadata();
            
            // Save the updated task file
            $this->saveTasksFile();
            
            $this->info('Generated ' . count($generatedTasks) . ' AI tasks.');
        } else {
            $this->info('No AI tasks were generated.');
        }

        return Command::SUCCESS;
    }

    protected function getGitCommits($since)
    {
        $command = "git log --since=\"{$since}\" --name-status --pretty=format:\"%h|%an|%ae|%ad|%s\"";
        exec($command, $output);

        $commits = [];
        $currentCommit = null;

        foreach ($output as $line) {
            if (empty($line)) {
                continue;
            }

            // If the line contains a commit hash (has the | character)
            if (Str::contains($line, '|')) {
                list($hash, $author, $email, $date, $message) = explode('|', $line, 5);
                $currentCommit = [
                    'hash' => $hash,
                    'author' => $author,
                    'email' => $email,
                    'date' => $date,
                    'message' => $message,
                    'files' => []
                ];
                $commits[] = $currentCommit;
            } elseif ($currentCommit !== null && count($commits) > 0) {
                // File changes are in the format: M path/to/file or A path/to/file
                $statusFile = preg_split('/\s+/', $line, 2);
                if (count($statusFile) == 2) {
                    list($status, $file) = $statusFile;
                    $commits[count($commits) - 1]['files'][] = [
                        'status' => $status,
                        'file' => $file
                    ];
                }
            }
        }

        return $commits;
    }

    protected function analyzeCommits($commits)
    {
        $fileChanges = [];

        foreach ($commits as $commit) {
            foreach ($commit['files'] as $fileChange) {
                $file = $fileChange['file'];
                $status = $fileChange['status'];

                // Skip deleted files
                if ($status === 'D') {
                    continue;
                }

                if (!isset($fileChanges[$file])) {
                    $fileChanges[$file] = [
                        'changes' => 0,
                        'authors' => [],
                        'type' => $this->getFileType($file),
                        'feature' => $this->guessFeature($file),
                    ];
                }

                $fileChanges[$file]['changes']++;
                
                if (!in_array($commit['author'], $fileChanges[$file]['authors'])) {
                    $fileChanges[$file]['authors'][] = $commit['author'];
                }
            }
        }

        return $fileChanges;
    }

    protected function getFileType($file)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        
        $typeMap = [
            'php' => 'Backend',
            'blade.php' => 'Frontend',
            'js' => 'Frontend',
            'css' => 'Frontend',
            'scss' => 'Frontend',
            'json' => 'Configuration',
            'md' => 'Documentation',
            'sh' => 'Script'
        ];

        if (Str::contains($file, '.blade.php')) {
            return 'Frontend';
        }

        return $typeMap[$extension] ?? 'Unknown';
    }

    protected function guessFeature($file)
    {
        $features = [
            'auth' => ['Auth', 'Authentication'],
            'user' => ['User Management'],
            'task' => ['Task Management'],
            'email' => ['Email Service'],
            'campaign' => ['Campaign Management'],
            'template' => ['Email Templates'],
            'subscriber' => ['Subscriber Management'],
            'report' => ['Reporting'],
            'dashboard' => ['Dashboard'],
            'setting' => ['Settings'],
            'config' => ['Configuration'],
            'api' => ['API'],
            'webhook' => ['Webhooks'],
            'notification' => ['Notifications'],
            'payment' => ['Payment Processing'],
            'doc' => ['Documentation']
        ];

        foreach ($features as $keyword => $featureNames) {
            if (Str::contains(strtolower($file), $keyword)) {
                return $featureNames[0];
            }
        }

        return 'Core';
    }

    protected function generateTasksFromAnalysis($fileChanges, $minChanges)
    {
        $tasks = [];
        $filesByFeature = [];

        // Group files by feature
        foreach ($fileChanges as $file => $data) {
            if ($data['changes'] >= $minChanges) {
                $feature = $data['feature'];
                if (!isset($filesByFeature[$feature])) {
                    $filesByFeature[$feature] = [];
                }
                $filesByFeature[$feature][$file] = $data;
            }
        }

        // Generate tasks for each feature
        foreach ($filesByFeature as $feature => $files) {
            // Count file types
            $types = [];
            foreach ($files as $file => $data) {
                $type = $data['type'];
                if (!isset($types[$type])) {
                    $types[$type] = 0;
                }
                $types[$type]++;
            }

            // Determine primary type
            arsort($types);
            $primaryType = key($types);
            
            // Generate task title and description
            $title = $this->generateTaskTitle($feature, $primaryType, count($files));
            $description = $this->generateTaskDescription($feature, $files);

            // Generate task
            $task = $this->createTask($title, $description, $feature, $files);
            $tasks[] = $task;

            // Generate potential sub-tasks for specific improvements
            if (count($files) > 10) {
                $subTasks = $this->generateSubTasks($feature, $files);
                $tasks = array_merge($tasks, $subTasks);
            }
        }

        return $tasks;
    }

    protected function generateTaskTitle($feature, $type, $fileCount)
    {
        $actions = [
            'Backend' => ['Refactor', 'Optimize', 'Improve', 'Standardize'],
            'Frontend' => ['Enhance UI for', 'Improve UX in', 'Modernize', 'Make responsive'],
            'Configuration' => ['Streamline', 'Consolidate', 'Organize'],
            'Documentation' => ['Update docs for', 'Document', 'Clarify'],
            'Script' => ['Automate', 'Optimize', 'Enhance'],
            'Unknown' => ['Review', 'Organize', 'Standardize']
        ];

        $action = $actions[$type][array_rand($actions[$type])];
        
        return "{$action} {$feature} components";
    }

    protected function generateTaskDescription($feature, $files)
    {
        $fileCount = count($files);
        $fileList = array_slice(array_keys($files), 0, 5);
        $additionalFilesCount = $fileCount > 5 ? $fileCount - 5 : 0;
        
        $fileListText = implode("\n- ", $fileList);
        $additionalText = $additionalFilesCount > 0 ? "\n- ...and {$additionalFilesCount} more files" : "";
        
        return "This task was auto-generated based on git commit analysis.\n\n".
               "The following files related to {$feature} have been changed frequently:\n".
               "- {$fileListText}{$additionalText}\n\n".
               "Consider reviewing these files for potential improvements, standardization, ".
               "or refactoring opportunities.";
    }

    protected function createTask($title, $description, $feature, $files)
    {
        // Calculate priority based on file changes and number of authors
        $totalChanges = 0;
        $uniqueAuthors = [];
        
        foreach ($files as $file => $data) {
            $totalChanges += $data['changes'];
            $uniqueAuthors = array_merge($uniqueAuthors, $data['authors']);
        }
        
        $uniqueAuthors = array_unique($uniqueAuthors);
        $authorCount = count($uniqueAuthors);
        
        // Calculate priority: High if many changes or many authors
        $priority = 'medium';
        if ($totalChanges > 30 || $authorCount > 3) {
            $priority = 'high';
        } elseif ($totalChanges < 10 && $authorCount < 2) {
            $priority = 'low';
        }

        // Get highest task ID from existing tasks
        $nextId = 1;
        foreach ($this->tasks as $task) {
            if (is_numeric($task['id']) && $task['id'] >= $nextId) {
                $nextId = $task['id'] + 1;
            }
        }

        // Remove [AI] prefix if exists
        $cleanTitle = $title;
        if (substr($cleanTitle, 0, 5) === '[AI] ') {
            $cleanTitle = substr($cleanTitle, 5);
        }

        return [
            'id' => $nextId,
            'title' => $cleanTitle,
            'description' => $description,
            'status' => 'pending',
            'priority' => $priority,
            'assignee' => 'ai', // Set assignee to AI by default
            'created_at' => Carbon::now()->toIso8601String(),
            'updated_at' => Carbon::now()->toIso8601String(),
            'due_date' => Carbon::now()->addDays(14)->toIso8601String(),
            'progress' => 0,
            'feature' => $feature,
            'phase' => 'planning',
            'estimated_hours' => $this->estimateHours($files),
            'actual_hours' => 0,
            'tags' => ['ai-generated', $feature, strtolower($priority)],
            'notes' => [],
            'version' => null,
            'dependencies' => [],
            'is_ai_generated' => true
        ];
    }

    protected function estimateHours($files)
    {
        $fileCount = count($files);
        $totalChanges = 0;
        
        foreach ($files as $data) {
            $totalChanges += $data['changes'];
        }
        
        // Base estimate on number of files and changes
        return min(max(ceil($fileCount / 2) + ceil($totalChanges / 20), 1), 16);
    }

    protected function generateSubTasks($feature, $files)
    {
        $tasks = [];
        $fileTypes = [];
        
        // Group files by type
        foreach ($files as $file => $data) {
            $type = $data['type'];
            if (!isset($fileTypes[$type])) {
                $fileTypes[$type] = [];
            }
            $fileTypes[$type][$file] = $data;
        }
        
        // Generate a sub-task for each type if there are enough files
        foreach ($fileTypes as $type => $typeFiles) {
            if (count($typeFiles) >= 5) {
                $title = "Optimize {$feature} {$type} components";
                $description = $this->generateTaskDescription("{$feature} {$type}", $typeFiles);
                $task = $this->createTask($title, $description, $feature, $typeFiles);
                $tasks[] = $task;
            }
        }
        
        return $tasks;
    }

    protected function initializeTasksFile()
    {
        $initialData = [
            'metadata' => [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'last_updated' => Carbon::now()->toIso8601String(),
                'version' => '1.0'
            ],
            'tasks' => []
        ];

        File::put($this->taskFile, json_encode($initialData, JSON_PRETTY_PRINT));
        $this->metadata = $initialData['metadata'];
        $this->tasks = $initialData['tasks'];
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