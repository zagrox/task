<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;

class VersionController extends Controller
{
    /**
     * Path to version file
     */
    protected $versionFile;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->versionFile = base_path('version.json');
    }

    /**
     * Display version information
     */
    public function index()
    {
        // Load version data
        $versionData = $this->getVersionData();
        
        // Check for uncommitted changes
        $gitStatus = $this->getGitStatus();
        
        return view('tasks.versions', [
            'versionData' => $versionData,
            'gitStatus' => $gitStatus,
            'canPush' => !empty($gitStatus['unpushedCommits']),
            'uncommittedChanges' => !empty($gitStatus['uncommittedFiles'])
        ]);
    }

    /**
     * Push current version to repository
     */
    public function pushToRepository(Request $request)
    {
        try {
            // Validate user has appropriate permissions
            if (!$this->canPushToRepository()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to push to the repository'
                ], 403);
            }
            
            // Run git push command
            $command = 'git push origin main --tags 2>&1';
            $output = [];
            $returnCode = 0;
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to push to repository: ' . implode("\n", $output),
                    'output' => $output
                ], 500);
            }
            
            // Create an automated task for the push
            $this->createPushTask();
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully pushed to repository',
                'output' => $output
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get version data from version.json
     */
    protected function getVersionData()
    {
        if (!File::exists($this->versionFile)) {
            return [
                'current' => [
                    'version' => '0.0.0',
                    'major' => 0,
                    'minor' => 0,
                    'patch' => 0,
                    'date' => Carbon::now()->format('Y-m-d'),
                    'notes' => 'No version information available'
                ],
                'history' => []
            ];
        }
        
        $data = json_decode(File::get($this->versionFile), true);
        
        // Format for the view
        $formattedData = [
            'current' => [
                'version' => "{$data['major']}.{$data['minor']}.{$data['patch']}",
                'major' => $data['major'],
                'minor' => $data['minor'],
                'patch' => $data['patch'],
                'date' => $data['history'][0]['date'] ?? Carbon::now()->format('Y-m-d'),
                'notes' => $data['history'][0]['notes'] ?? 'No release notes available'
            ],
            'history' => $data['history'] ?? []
        ];
        
        return $formattedData;
    }
    
    /**
     * Get git status information
     */
    protected function getGitStatus()
    {
        $result = [
            'branch' => '',
            'uncommittedFiles' => [],
            'unpushedCommits' => []
        ];
        
        // Get current branch
        $branchCommand = 'git rev-parse --abbrev-ref HEAD';
        exec($branchCommand, $branchOutput, $returnCode);
        
        if ($returnCode === 0 && !empty($branchOutput)) {
            $result['branch'] = $branchOutput[0];
        }
        
        // Get uncommitted changes
        $statusCommand = 'git status --porcelain';
        exec($statusCommand, $statusOutput, $returnCode);
        
        if ($returnCode === 0) {
            foreach ($statusOutput as $line) {
                if (!empty(trim($line))) {
                    $status = substr($line, 0, 2);
                    $file = trim(substr($line, 3));
                    $result['uncommittedFiles'][] = [
                        'status' => $status,
                        'file' => $file
                    ];
                }
            }
        }
        
        // Get unpushed commits
        $unpushedCommand = 'git log origin/main..HEAD --oneline 2>/dev/null';
        exec($unpushedCommand, $unpushedOutput, $returnCode);
        
        if ($returnCode === 0) {
            foreach ($unpushedOutput as $line) {
                if (!empty(trim($line))) {
                    $parts = explode(' ', trim($line), 2);
                    if (count($parts) === 2) {
                        $result['unpushedCommits'][] = [
                            'hash' => $parts[0],
                            'message' => $parts[1]
                        ];
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Check if user can push to repository
     */
    protected function canPushToRepository()
    {
        // In a real app, this would check user permissions
        // For now, we'll just return true
        return true;
    }
    
    /**
     * Create a task for the version push
     */
    protected function createPushTask()
    {
        $tasksFile = base_path('project-management/tasks.json');
        
        if (!File::exists($tasksFile)) {
            return;
        }
        
        $tasksData = json_decode(File::get($tasksFile), true);
        
        // Get the version information
        $versionData = $this->getVersionData();
        $version = $versionData['current']['version'];
        
        // Get next ID
        $nextId = $tasksData['next_id'] ?? 1;
        
        // Create task
        $newTask = [
            'id' => $nextId,
            'title' => "Version {$version} pushed to repository",
            'description' => "Pushed version {$version} to the git repository.\n\nRelease notes: {$versionData['current']['notes']}",
            'assignee' => 'user',
            'status' => 'completed',
            'priority' => 'medium',
            'created_at' => Carbon::now()->toIso8601String(),
            'updated_at' => Carbon::now()->toIso8601String(),
            'due_date' => Carbon::now()->format('Y-m-d'),
            'related_feature' => 'Version Control',
            'related_phase' => 'P3-ENHANCED',
            'dependencies' => [],
            'progress' => 100,
            'tags' => ['version-control', 'deployment', 'system'],
            'estimated_hours' => 1,
            'actual_hours' => 1,
            'version' => $version,
            'notes' => [
                [
                    'content' => "Version {$version} automatically pushed to repository",
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
    }
} 