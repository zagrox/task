<?php

namespace App\Console\Commands\Tasks;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GenerateAiTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:generate-ai 
                            {--days=7 : Number of days to analyze}
                            {--analyze-git : Analyze git history for task generation}
                            {--limit=5 : Maximum number of tasks to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate AI tasks based on code changes';

    /**
     * Tasks file path
     */
    protected $tasksFile;

    /**
     * File extensions to analyze
     */
    protected $fileExtensions = ['php', 'js', 'vue', 'blade.php', 'scss', 'css'];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->tasksFile = base_path('project-management/tasks.json');
        
        // Initialize tasks file if it doesn't exist
        if (!File::exists($this->tasksFile)) {
            $this->initializeTasksFile();
        }
        
        // Check if git analysis is requested
        if ($this->option('analyze-git')) {
            $days = $this->option('days');
            $limit = $this->option('limit');
            
            $this->info("Analyzing git history for the last {$days} days...");
            $changes = $this->getRecentGitChanges($days);
            
            if (empty($changes)) {
                $this->warn("No significant code changes found in the last {$days} days.");
                return 0;
            }
            
            $this->info("Found " . count($changes) . " significant changes");
            $generatedTasks = $this->generateTasksFromChanges($changes, $limit);
            
            $this->info("Generated " . count($generatedTasks) . " AI tasks");
            $this->displayGeneratedTasks($generatedTasks);
            
            return 0;
        }
        
        $this->warn("No action specified. Use --analyze-git to generate tasks from git history.");
        return 1;
    }
    
    /**
     * Initialize tasks file with default structure
     */
    protected function initializeTasksFile()
    {
        $defaultContent = [
            'metadata' => [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'last_updated' => Carbon::now()->toIso8601String()
            ],
            'tasks' => [],
            'next_id' => 1
        ];
        
        File::put($this->tasksFile, json_encode($defaultContent, JSON_PRETTY_PRINT));
        $this->info("Tasks file initialized at {$this->tasksFile}");
    }
    
    /**
     * Get recent git changes
     */
    protected function getRecentGitChanges($days)
    {
        $since = Carbon::now()->subDays($days)->format('Y-m-d');
        $command = "git log --since='{$since}' --name-status --pretty=format:'%H|%an|%ad|%s'";
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            $this->error("Failed to execute git command");
            return [];
        }
        
        return $this->parseGitOutput($output);
    }
    
    /**
     * Parse git output to extract meaningful changes
     */
    protected function parseGitOutput(array $output)
    {
        $changes = [];
        $currentCommit = null;
        
        foreach ($output as $line) {
            if (empty($line)) continue;
            
            // This is a commit line
            if (Str::contains($line, '|')) {
                $parts = explode('|', $line);
                if (count($parts) === 4) {
                    $currentCommit = [
                        'hash' => $parts[0],
                        'author' => $parts[1],
                        'date' => $parts[2],
                        'message' => $parts[3],
                        'files' => []
                    ];
                }
            } 
            // This is a file change line
            elseif ($currentCommit !== null && preg_match('/^([AMD])\s+(.+)$/', $line, $matches)) {
                $changeType = $matches[1];
                $filePath = $matches[2];
                
                // Only include changes to specific file types
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                if (in_array($extension, $this->fileExtensions) || in_array(Str::afterLast($filePath, '.'), $this->fileExtensions)) {
                    $currentCommit['files'][] = [
                        'type' => $changeType,
                        'path' => $filePath
                    ];
                }
            }
            
            // If we have a commit with files, add it to changes
            if ($currentCommit !== null && !empty($currentCommit['files'])) {
                $changes[] = $currentCommit;
                $currentCommit = null;
            }
        }
        
        return $this->filterSignificantChanges($changes);
    }
    
    /**
     * Filter to include only significant changes
     */
    protected function filterSignificantChanges(array $changes)
    {
        $significantChanges = [];
        
        foreach ($changes as $commit) {
            // Skip commits with common trivial messages
            if (preg_match('/(typo|fix whitespace|update version|bump version)/i', $commit['message'])) {
                continue;
            }
            
            // Skip if only contains trivial files
            $containsSignificantFile = false;
            foreach ($commit['files'] as $file) {
                if (!Str::contains($file['path'], ['vendor/', 'node_modules/', '.gitignore', 'README.md'])) {
                    $containsSignificantFile = true;
                    break;
                }
            }
            
            if ($containsSignificantFile) {
                $significantChanges[] = $commit;
            }
        }
        
        return $significantChanges;
    }
    
    /**
     * Generate tasks from the git changes
     */
    protected function generateTasksFromChanges(array $changes, $limit)
    {
        // Group changes by component/section
        $componentChanges = $this->groupChangesByComponent($changes);
        
        // Load existing tasks to avoid duplicates
        $existingTasks = $this->loadExistingTasks();
        $existingTitles = array_map(function($task) {
            return $task['title'] ?? '';
        }, $existingTasks);
        
        // Get task data including next_id
        $tasksData = json_decode(File::get($this->tasksFile), true);
        $nextId = $tasksData['next_id'] ?? 1;
        
        $generatedTasks = [];
        
        foreach ($componentChanges as $component => $componentData) {
            // Skip if we've reached the limit
            if (count($generatedTasks) >= $limit) {
                break;
            }
            
            $taskTitle = $this->generateTaskTitle($component, $componentData);
            
            // Check if similar task already exists
            if ($this->isSimilarTaskExists($taskTitle, $existingTitles)) {
                continue;
            }
            
            $taskDescription = $this->generateTaskDescription($component, $componentData);
            
            $task = [
                'id' => $nextId,
                'title' => $taskTitle,
                'description' => $taskDescription,
                'assignee' => 'ai',
                'status' => 'pending',
                'priority' => $this->determineTaskPriority($componentData),
                'created_at' => Carbon::now()->toIso8601String(),
                'updated_at' => Carbon::now()->toIso8601String(),
                'due_date' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'tags' => $this->generateTaskTags($component, $componentData),
                'feature' => $this->determineFeature($component),
                'related_feature' => $this->determineFeature($component),
                'related_phase' => 'P3-ENHANCED',
                'progress' => 0,
                'dependencies' => [],
                'estimated_hours' => mt_rand(1, 8),
                'actual_hours' => 0,
                'version' => '1.0.6',
                'notes' => [
                    [
                        'content' => "Auto-generated task based on recent code changes.",
                        'timestamp' => Carbon::now()->toIso8601String()
                    ]
                ]
            ];
            
            $generatedTasks[] = $task;
            $nextId++;
        }
        
        // Save the generated tasks
        if (!empty($generatedTasks)) {
            $this->saveGeneratedTasks($existingTasks, $generatedTasks, $nextId);
        }
        
        return $generatedTasks;
    }
    
    /**
     * Group changes by component or section of the application
     */
    protected function groupChangesByComponent(array $changes)
    {
        $componentChanges = [];
        
        foreach ($changes as $commit) {
            foreach ($commit['files'] as $file) {
                $component = $this->classifyFile($file['path']);
                
                if (!isset($componentChanges[$component])) {
                    $componentChanges[$component] = [
                        'commits' => [],
                        'files' => []
                    ];
                }
                
                // Add commit if not already added
                $commitHash = $commit['hash'];
                if (!in_array($commitHash, array_column($componentChanges[$component]['commits'], 'hash'))) {
                    $componentChanges[$component]['commits'][] = $commit;
                }
                
                // Add file if not already added
                if (!in_array($file['path'], $componentChanges[$component]['files'])) {
                    $componentChanges[$component]['files'][] = $file['path'];
                }
            }
        }
        
        return $componentChanges;
    }
    
    /**
     * Classify a file into a component or section
     */
    protected function classifyFile($filePath)
    {
        if (Str::contains($filePath, 'app/Http/Controllers')) {
            if (Str::contains($filePath, 'TaskController')) {
                return 'Task Management';
            }
            return 'Controller Logic';
        }
        
        if (Str::contains($filePath, 'app/Models')) {
            return 'Data Models';
        }
        
        if (Str::contains($filePath, 'resources/views')) {
            if (Str::contains($filePath, 'tasks')) {
                return 'Task Interface';
            }
            return 'User Interface';
        }
        
        if (Str::contains($filePath, 'app/Console/Commands')) {
            return 'Command Line Tools';
        }
        
        if (Str::contains($filePath, 'resources/js') || Str::contains($filePath, 'resources/css')) {
            return 'Frontend Assets';
        }
        
        if (Str::contains($filePath, 'routes')) {
            return 'Routing';
        }
        
        if (Str::contains($filePath, 'tests')) {
            return 'Testing';
        }
        
        if (Str::contains($filePath, 'database/migrations')) {
            return 'Database Schema';
        }
        
        if (Str::contains($filePath, 'config')) {
            return 'Configuration';
        }
        
        return 'Other Components';
    }
    
    /**
     * Generate a title for a task based on component and changes
     */
    protected function generateTaskTitle($component, $componentData)
    {
        $commitMessages = array_column($componentData['commits'], 'message');
        $fileCount = count($componentData['files']);
        
        // Extract common themes from commit messages
        $keywords = $this->extractKeywords($commitMessages);
        
        // Generate title based on component and keywords
        if (in_array('add', $keywords)) {
            return "Enhance {$component} with new features";
        } elseif (in_array('fix', $keywords) || in_array('bug', $keywords)) {
            return "Fix issues in {$component}";
        } elseif (in_array('update', $keywords) || in_array('improve', $keywords)) {
            return "Improve {$component} functionality";
        } elseif (in_array('refactor', $keywords)) {
            return "Refactor {$component} for better performance";
        } elseif ($fileCount > 3) {
            return "Major updates to {$component}";
        } else {
            return "Review and optimize {$component}";
        }
    }
    
    /**
     * Generate a description for a task
     */
    protected function generateTaskDescription($component, $componentData)
    {
        $fileTypes = $this->categorizeFileTypes($componentData['files']);
        $commitMessages = array_column($componentData['commits'], 'message');
        
        $description = "Based on recent changes to the {$component}, this task involves:\n\n";
        
        // Add details based on file types
        if (!empty($fileTypes['controllers'])) {
            $description .= "- Review and optimize controller logic in: " . implode(', ', $fileTypes['controllers']) . "\n";
        }
        
        if (!empty($fileTypes['models'])) {
            $description .= "- Enhance data models in: " . implode(', ', $fileTypes['models']) . "\n";
        }
        
        if (!empty($fileTypes['views'])) {
            $description .= "- Improve user interface elements in: " . implode(', ', $fileTypes['views']) . "\n";
        }
        
        if (!empty($fileTypes['js'])) {
            $description .= "- Optimize JavaScript functionality in: " . implode(', ', $fileTypes['js']) . "\n";
        }
        
        // Add relevant commit messages
        $description .= "\nRecent changes included:\n";
        foreach (array_slice($commitMessages, 0, 3) as $message) {
            $description .= "- " . $message . "\n";
        }
        
        $description .= "\nThis task is AI-generated based on code analysis and may require further refinement.";
        
        return $description;
    }
    
    /**
     * Categorize files by type
     */
    protected function categorizeFileTypes(array $files)
    {
        $categories = [
            'controllers' => [],
            'models' => [],
            'views' => [],
            'js' => [],
            'css' => [],
            'other' => []
        ];
        
        foreach ($files as $file) {
            $fileName = basename($file);
            
            if (Str::contains($file, 'Controllers')) {
                $categories['controllers'][] = $fileName;
            } elseif (Str::contains($file, 'Models')) {
                $categories['models'][] = $fileName;
            } elseif (Str::contains($file, 'views')) {
                $categories['views'][] = $fileName;
            } elseif (Str::endsWith($file, '.js') || Str::endsWith($file, '.vue')) {
                $categories['js'][] = $fileName;
            } elseif (Str::endsWith($file, '.css') || Str::endsWith($file, '.scss')) {
                $categories['css'][] = $fileName;
            } else {
                $categories['other'][] = $fileName;
            }
        }
        
        return $categories;
    }
    
    /**
     * Extract keywords from commit messages
     */
    protected function extractKeywords(array $messages)
    {
        $keywords = [];
        $commonKeywords = ['add', 'fix', 'update', 'improve', 'refactor', 'bug', 'feature', 'enhance'];
        
        foreach ($messages as $message) {
            $messageLower = strtolower($message);
            foreach ($commonKeywords as $keyword) {
                if (Str::contains($messageLower, $keyword) && !in_array($keyword, $keywords)) {
                    $keywords[] = $keyword;
                }
            }
        }
        
        return $keywords;
    }
    
    /**
     * Generate tags for a task
     */
    protected function generateTaskTags($component, $componentData)
    {
        $tags = [str_replace(' ', '-', strtolower($component))];
        
        $commitMessages = array_column($componentData['commits'], 'message');
        $keywords = $this->extractKeywords($commitMessages);
        
        foreach ($keywords as $keyword) {
            $tags[] = $keyword;
        }
        
        // Add tags based on file types
        $fileTypes = $this->categorizeFileTypes($componentData['files']);
        if (!empty($fileTypes['controllers'])) {
            $tags[] = 'backend';
        }
        
        if (!empty($fileTypes['views']) || !empty($fileTypes['js']) || !empty($fileTypes['css'])) {
            $tags[] = 'frontend';
        }
        
        // Add 'ai-generated' tag
        $tags[] = 'ai-generated';
        
        return array_unique($tags);
    }
    
    /**
     * Determine the feature for a task
     */
    protected function determineFeature($component)
    {
        $featureMappings = [
            'Task Management' => 'Task System',
            'Task Interface' => 'Task UI',
            'Controller Logic' => 'Backend',
            'Data Models' => 'Data Layer',
            'User Interface' => 'UI/UX',
            'Command Line Tools' => 'CLI Tools',
            'Frontend Assets' => 'Frontend',
            'Routing' => 'API/Routing',
            'Testing' => 'QA',
            'Database Schema' => 'Database',
            'Configuration' => 'System Config'
        ];
        
        return $featureMappings[$component] ?? 'General';
    }
    
    /**
     * Determine priority for a task
     */
    protected function determineTaskPriority($componentData)
    {
        $commitCount = count($componentData['commits']);
        $fileCount = count($componentData['files']);
        
        // More changes might indicate higher priority
        if ($commitCount > 5 || $fileCount > 10) {
            return 'high';
        } elseif ($commitCount > 2 || $fileCount > 5) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Check if a similar task already exists
     */
    protected function isSimilarTaskExists($taskTitle, array $existingTitles)
    {
        foreach ($existingTitles as $existingTitle) {
            $similarity = similar_text($taskTitle, $existingTitle, $percent);
            if ($percent > 70) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Load existing tasks
     */
    protected function loadExistingTasks()
    {
        $tasksData = json_decode(File::get($this->tasksFile), true);
        return $tasksData['tasks'] ?? [];
    }
    
    /**
     * Save generated tasks to the tasks file
     */
    protected function saveGeneratedTasks(array $existingTasks, array $newTasks, $nextId)
    {
        $tasksData = json_decode(File::get($this->tasksFile), true);
        
        // Add new tasks
        $tasksData['tasks'] = array_merge($existingTasks, $newTasks);
        
        // Update metadata
        $tasksData['metadata']['total_tasks'] = count($tasksData['tasks']);
        $tasksData['metadata']['completed_tasks'] = count(array_filter($tasksData['tasks'], function($task) {
            return ($task['status'] ?? '') === 'completed';
        }));
        $tasksData['metadata']['last_updated'] = Carbon::now()->toIso8601String();
        
        // Update next_id
        $tasksData['next_id'] = $nextId;
        
        // Save to file
        File::put($this->tasksFile, json_encode($tasksData, JSON_PRETTY_PRINT));
    }
    
    /**
     * Display the generated tasks
     */
    protected function displayGeneratedTasks(array $tasks)
    {
        $this->info("\nGenerated AI Tasks:");
        
        foreach ($tasks as $index => $task) {
            $this->line("\n" . ($index + 1) . ". <fg=green>{$task['title']}</>");
            $this->line("   Priority: <fg=yellow>{$task['priority']}</> | Feature: {$task['feature']}");
            $this->line("   Tags: " . implode(', ', $task['tags']));
            $this->line("   Due: " . Carbon::parse($task['due_date'])->format('Y-m-d'));
        }
    }
} 