<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version:update 
                           {type=patch : Type of version increment (major, minor, patch)}
                           {--notes= : Release notes for this version}
                           {--no-git : Skip git commit and tag operations}
                           {--force : Force version update even if it would be a downgrade}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the project version number and create git tag';

    /**
     * Path to version file
     */
    protected $versionFile;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->versionFile = base_path('version.json');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->argument('type');
        $notes = $this->option('notes');
        $skipGit = $this->option('no-git');
        $force = $this->option('force');

        if (!in_array($type, ['major', 'minor', 'patch'])) {
            $this->error("Invalid version type. Must be one of: major, minor, patch");
            return 1;
        }

        // Load current version
        $versionData = $this->loadVersionData();
        $oldVersion = "{$versionData['major']}.{$versionData['minor']}.{$versionData['patch']}";
        
        // Update version number
        $originalMajor = $versionData['major'];
        $originalMinor = $versionData['minor'];
        $originalPatch = $versionData['patch'];
        
        switch ($type) {
            case 'major':
                $versionData['major']++;
                $versionData['minor'] = 0;
                $versionData['patch'] = 0;
                break;
            case 'minor':
                $versionData['minor']++;
                $versionData['patch'] = 0;
                break;
            case 'patch':
                $versionData['patch']++;
                break;
        }
        
        // Get new version string
        $newVersion = "{$versionData['major']}.{$versionData['minor']}.{$versionData['patch']}";
        
        // Safety check: Prevent version downgrade
        if (!$force && version_compare($newVersion, $oldVersion, '<')) {
            $this->error("Error: New version ($newVersion) would be a downgrade from current version ($oldVersion)");
            $this->line("If you're sure you want to downgrade, use the --force option");
            return 1;
        }
        
        // Prompt for release notes if not provided
        if (empty($notes)) {
            $notes = $this->ask("Enter release notes for version $newVersion:");
        }
        
        // Double-confirm major or potentially disruptive changes
        if ($type === 'major' || ($force && version_compare($newVersion, $oldVersion, '<'))) {
            $confirmed = $this->confirm("WARNING: You are about to perform a $type version change from $oldVersion to $newVersion. Are you sure?");
            if (!$confirmed) {
                $this->info("Version update cancelled");
                return 0;
            }
        }
        
        // Add to history
        if (!isset($versionData['history'])) {
            $versionData['history'] = [];
        }
        
        array_unshift($versionData['history'], [
            'version' => $newVersion,
            'date' => Carbon::now()->format('Y-m-d'),
            'notes' => $notes
        ]);
        
        // Store previous versions
        if (!isset($versionData['previous_versions'])) {
            $versionData['previous_versions'] = [];
        }
        
        if (!in_array($oldVersion, $versionData['previous_versions'])) {
            array_unshift($versionData['previous_versions'], $oldVersion);
        }
        
        // Limit history to last 10 versions
        if (count($versionData['previous_versions']) > 10) {
            $versionData['previous_versions'] = array_slice($versionData['previous_versions'], 0, 10);
        }
        
        // Save version file
        $this->saveVersionData($versionData);
        
        // Update Laravel version in config/app.php
        $this->updateLaravelVersion($newVersion);
        
        // Git operations
        if (!$skipGit) {
            $this->performGitOperations($newVersion, $notes);
        }
        
        // Create a task recording this version update
        $this->createVersionTask($newVersion, $oldVersion, $notes);
        
        $this->info("Version successfully updated from $oldVersion to $newVersion");
        return 0;
    }
    
    /**
     * Load the version data from file
     */
    protected function loadVersionData()
    {
        if (!File::exists($this->versionFile)) {
            return [
                'major' => 0,
                'minor' => 0,
                'patch' => 0,
                'history' => [],
                'previous_versions' => []
            ];
        }
        
        return json_decode(File::get($this->versionFile), true);
    }
    
    /**
     * Save the version data to file
     */
    protected function saveVersionData($data)
    {
        File::put($this->versionFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    /**
     * Update Laravel version in config/app.php
     */
    protected function updateLaravelVersion($version)
    {
        $configPath = config_path('app.php');
        if (!File::exists($configPath)) {
            $this->warn("Could not find config/app.php to update version");
            return;
        }
        
        $content = File::get($configPath);
        $content = preg_replace(
            "/'version' => '.*?'/",
            "'version' => '$version'",
            $content
        );
        
        File::put($configPath, $content);
        $this->info("Updated version in config/app.php");
    }
    
    /**
     * Perform Git operations: commit changes and create tag
     */
    protected function performGitOperations($version, $notes)
    {
        try {
            // Check if git is available
            exec('git --version', $output, $returnCode);
            if ($returnCode !== 0) {
                $this->warn("Git not available, skipping git operations");
                return;
            }
            
            // Add version file
            exec('git add ' . $this->versionFile, $output, $returnCode);
            
            // Add config file
            exec('git add ' . config_path('app.php'), $output, $returnCode);
            
            // Commit changes
            $commitMessage = "Version bump to $version\n\n$notes";
            exec("git commit -m " . escapeshellarg($commitMessage), $output, $returnCode);
            
            if ($returnCode !== 0) {
                $this->warn("Failed to commit version changes");
                return;
            }
            
            // Create tag
            exec("git tag -a v$version -m " . escapeshellarg($notes), $output, $returnCode);
            
            if ($returnCode !== 0) {
                $this->warn("Failed to create git tag");
                return;
            }
            
            $this->info("Git commit and tag created successfully");
            
        } catch (\Exception $e) {
            $this->error("Git operations failed: " . $e->getMessage());
            Log::error("Version update git operations failed: " . $e->getMessage());
        }
    }
    
    /**
     * Create a task for the version update
     */
    protected function createVersionTask($newVersion, $oldVersion, $notes)
    {
        $tasksFile = base_path('project-management/tasks.json');
        
        if (!File::exists($tasksFile)) {
            $this->warn("Tasks file not found, skipping task creation");
            return;
        }
        
        $tasksData = json_decode(File::get($tasksFile), true);
        
        // Get next ID
        $nextId = $tasksData['next_id'] ?? 1;
        
        // Create task
        $newTask = [
            'id' => $nextId,
            'title' => "Version update: $oldVersion → $newVersion",
            'description' => "Updated project version from $oldVersion to $newVersion.\n\nRelease notes: $notes",
            'assignee' => 'system',
            'status' => 'completed',
            'priority' => 'medium',
            'created_at' => Carbon::now()->toIso8601String(),
            'updated_at' => Carbon::now()->toIso8601String(),
            'due_date' => Carbon::now()->format('Y-m-d'),
            'related_feature' => 'Version Control',
            'related_phase' => 'P3-ENHANCED',
            'dependencies' => [],
            'progress' => 100,
            'tags' => ['version-control', 'system'],
            'estimated_hours' => 0.5,
            'actual_hours' => 0.5,
            'version' => $newVersion,
            'notes' => [
                [
                    'content' => "Automatic version update via command line",
                    'timestamp' => Carbon::now()->toIso8601String()
                ]
            ]
        ];
        
        // Add the task
        $tasksData['tasks'][] = $newTask;
        $tasksData['next_id'] = $nextId + 1;
        
        // Update metadata
        $completedTasks = count(array_filter($tasksData['tasks'], function($task) {
            return ($task['status'] ?? '') === 'completed';
        }));
        
        $tasksData['metadata']['total_tasks'] = count($tasksData['tasks']);
        $tasksData['metadata']['completed_tasks'] = $completedTasks;
        $tasksData['metadata']['last_updated'] = Carbon::now()->toIso8601String();
        
        // Save the file
        File::put($tasksFile, json_encode($tasksData, JSON_PRETTY_PRINT));
        $this->info("Created task record for version update");
    }
} 