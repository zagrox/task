<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;
use App\Services\GitHubService;
use App\Models\GitHubIssue;

class TaskController extends Controller
{
    /**
     * Path to the tasks file
     */
    protected $tasksFile;
    
    /**
     * Path to the version file
     */
    protected $versionFile;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tasksFile = base_path('project-management/tasks.json');
        if (!file_exists($this->tasksFile)) {
            // Create directory if it doesn't exist
            if (!file_exists(dirname($this->tasksFile))) {
                mkdir(dirname($this->tasksFile), 0755, true);
            }
            // Initialize the tasks file if it doesn't exist
            $this->initializeTasksFile();
        }
        $this->versionFile = base_path('version.json');
    }
    
    /**
     * Display a listing of the tasks
     */
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->input('status'),
            'assignee' => $request->input('assignee'),
            'priority' => $request->input('priority'),
            'feature' => $request->input('feature'),
            'phase' => $request->input('phase'),
            'search' => $request->input('search'),
            'tag' => $request->input('tag'),
            'version' => $request->input('version'),
        ];
        
        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
            $this->initializeTasksFile();
        }
        
        // Load tasks
        $taskData = json_decode(File::get($this->tasksFile), true);
        $metadata = $taskData['metadata'] ?? [];
        $tasks = $taskData['tasks'] ?? [];
        
        // Apply filters
        if ($filters['status']) {
            $tasks = array_filter($tasks, function($task) use ($filters) {
                return $task['status'] === $filters['status'];
            });
        }
        
        if ($filters['assignee']) {
            $tasks = array_filter($tasks, function($task) use ($filters) {
                return $task['assignee'] === $filters['assignee'];
            });
        }
        
        if ($filters['priority']) {
            $tasks = array_filter($tasks, function($task) use ($filters) {
                return $task['priority'] === $filters['priority'];
            });
        }
        
        if ($filters['feature']) {
            $tasks = array_filter($tasks, function($task) use ($filters) {
                return $task['related_feature'] === $filters['feature'];
            });
        }
        
        if ($filters['phase']) {
            $tasks = array_filter($tasks, function($task) use ($filters) {
                return $task['related_phase'] === $filters['phase'];
            });
        }
        
        if ($filters['search']) {
            $searchTerm = strtolower($filters['search']);
            $tasks = array_filter($tasks, function($task) use ($searchTerm) {
                return strpos(strtolower($task['title']), $searchTerm) !== false 
                    || strpos(strtolower($task['description']), $searchTerm) !== false;
            });
        }
        
        if ($filters['tag']) {
            $tagSearch = strtolower($filters['tag']);
            $tasks = array_filter($tasks, function($task) use ($tagSearch) {
                if (empty($task['tags'])) return false;
                $tags = array_map('trim', explode(',', strtolower($task['tags'])));
                return in_array($tagSearch, $tags);
            });
        }
        
        if ($filters['version']) {
            $tasks = array_filter($tasks, function($task) use ($filters) {
                return isset($task['version']) && $task['version'] === $filters['version'];
            });
        }
        
        // Sort tasks by ID in descending order (newest first)
        usort($tasks, function($a, $b) {
            return $b['id'] <=> $a['id'];
        });
        
        // Pagination
        $page = $request->input('page', 1);
        $perPage = 20;
        $totalTasks = count($tasks);
        $totalPages = ceil($totalTasks / $perPage);
        $page = max(1, min($page, $totalPages));
        
        $offset = ($page - 1) * $perPage;
        $paginatedTasks = array_slice($tasks, $offset, $perPage);
        
        return $this->renderTasksView('tasks.index', [
            'tasks' => array_values($paginatedTasks),
            'metadata' => $metadata,
            'filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalTasks,
                'total_pages' => $totalPages
            ]
        ]);
    }
    
    /**
     * Display the specified task
     */
    public function show($id)
    {
        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
            return redirect()->route('tasks.index')->with('error', 'Tasks file not found');
        }
        
        // Load tasks
        $taskData = json_decode(File::get($this->tasksFile), true);
        
        // Find the task
        $task = null;
        foreach ($taskData['tasks'] as $t) {
            if ($t['id'] == $id) {
                $task = $t;
                break;
            }
        }
        
        if (!$task) {
            return redirect()->route('tasks.index')->with('error', "Task #{$id} not found");
        }
        
        // Get related tasks (dependencies)
        $relatedTasks = [];
        if (!empty($task['dependencies'])) {
            foreach ($taskData['tasks'] as $t) {
                if (in_array($t['id'], $task['dependencies'])) {
                    $relatedTasks[] = $t;
                }
            }
        }
        
        // Return view with task
        return $this->renderTasksView('tasks.show', [
            'task' => $task,
            'relatedTasks' => $relatedTasks
        ]);
    }
    
    /**
     * Show the form for creating a new task
     */
    public function create()
    {
        // Get unique features and phases for dropdowns
        $features = [];
        $phases = [];
        
        if (File::exists($this->tasksFile)) {
            $taskData = json_decode(File::get($this->tasksFile), true);
            
            foreach ($taskData['tasks'] as $task) {
                if (!empty($task['related_feature']) && !in_array($task['related_feature'], $features)) {
                    $features[] = $task['related_feature'];
                }
                
                if (!empty($task['related_phase']) && !in_array($task['related_phase'], $phases)) {
                    $phases[] = $task['related_phase'];
                }
            }
        }
        
        return $this->renderTasksView('tasks.create', [
            'features' => $features,
            'phases' => $phases
        ]);
    }
    
    /**
     * Store a newly created task
     */
    public function store(Request $request)
    {
        // Validate request
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'assignee' => 'required|in:user,ai',
            'status' => 'required|in:pending,in-progress,completed,blocked',
            'priority' => 'required|in:low,medium,high,critical',
            'due_date' => 'nullable|date',
            'related_feature' => 'nullable|string|max:255',
            'related_phase' => 'nullable|string|max:255',
            'tags' => 'nullable|string',
            'progress' => 'nullable|integer|min:0|max:100',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'version' => 'nullable|string|max:50',
        ]);
        
        // Create tasks file directory if it doesn't exist
        if (!File::exists(dirname($this->tasksFile))) {
            File::makeDirectory(dirname($this->tasksFile), 0755, true);
        }
        
        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
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
            $taskData = $initialContent;
        } else {
            // Load tasks
            $taskData = json_decode(File::get($this->tasksFile), true);
        }
        
        // Create task
        $nextId = $taskData['next_id'];
        $tags = [];
        
        if ($request->filled('tags')) {
            $tags = array_map('trim', explode(',', $request->input('tags')));
        }
        
        $task = [
            'id' => $nextId,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'assignee' => $request->input('assignee'),
            'status' => $request->input('status'),
            'priority' => $request->input('priority'),
            'created_at' => Carbon::now()->toIso8601String(),
            'updated_at' => Carbon::now()->toIso8601String(),
            'due_date' => $request->filled('due_date') ? $request->input('due_date') : null,
            'related_feature' => $request->filled('related_feature') ? $request->input('related_feature') : null,
            'related_phase' => $request->filled('related_phase') ? $request->input('related_phase') : null,
            'dependencies' => [],
            'progress' => $request->filled('progress') ? (int)$request->input('progress') : 0,
            'notes' => [],
            'tags' => $tags,
            'estimated_hours' => $request->filled('estimated_hours') ? (float)$request->input('estimated_hours') : 0,
            'actual_hours' => $request->filled('actual_hours') ? (float)$request->input('actual_hours') : 0,
            'version' => $request->filled('version') ? $request->input('version') : null,
        ];
        
        // Add task to tasks array
        $taskData['tasks'][] = $task;
        
        // Increment next ID
        $taskData['next_id'] = $nextId + 1;
        
        // Update metadata
        $taskData['metadata'] = [
            'total_tasks' => count($taskData['tasks']),
            'completed_tasks' => count(array_filter($taskData['tasks'], function($task) {
                return $task['status'] === 'completed';
            })),
            'user_tasks' => count(array_filter($taskData['tasks'], function($task) {
                return $task['assignee'] === 'user';
            })),
            'ai_tasks' => count(array_filter($taskData['tasks'], function($task) {
                return $task['assignee'] === 'ai';
            })),
            'last_updated' => Carbon::now()->toIso8601String()
        ];
        
        // Save tasks
        File::put($this->tasksFile, json_encode($taskData, JSON_PRETTY_PRINT));
        
        // Redirect to tasks index
        return redirect()->route('tasks.index')->with('success', "Task #{$nextId} created successfully");
    }
    
    /**
     * Show the form for editing a task
     */
    public function edit($id)
    {
        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
            return redirect()->route('tasks.index')->with('error', 'Tasks file not found');
        }
        
        // Load tasks
        $taskData = json_decode(File::get($this->tasksFile), true);
        
        // Find the task
        $task = null;
        foreach ($taskData['tasks'] as $t) {
            if ($t['id'] == $id) {
                $task = $t;
                break;
            }
        }
        
        if (!$task) {
            return redirect()->route('tasks.index')->with('error', "Task #{$id} not found");
        }
        
        // Get unique features and phases for dropdowns
        $features = [];
        $phases = [];
        
        foreach ($taskData['tasks'] as $t) {
            if (!empty($t['related_feature']) && !in_array($t['related_feature'], $features)) {
                $features[] = $t['related_feature'];
            }
            
            if (!empty($t['related_phase']) && !in_array($t['related_phase'], $phases)) {
                $phases[] = $t['related_phase'];
            }
        }
        
        // Get potential dependencies (all tasks except this one)
        $potentialDependencies = array_filter($taskData['tasks'], function($t) use ($id) {
            return $t['id'] != $id;
        });
        
        // Return view with task
        return $this->renderTasksView('tasks.edit', [
            'task' => $task,
            'features' => $features,
            'phases' => $phases,
            'potentialDependencies' => $potentialDependencies
        ]);
    }
    
    /**
     * Update the specified task
     */
    public function update(Request $request, $id)
    {
        // Validate request
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'assignee' => 'required|in:user,ai',
            'status' => 'required|in:pending,in-progress,completed,blocked',
            'priority' => 'required|in:low,medium,high,critical',
            'due_date' => 'nullable|date',
            'related_feature' => 'nullable|string|max:255',
            'related_phase' => 'nullable|string|max:255',
            'tags' => 'nullable|string',
            'progress' => 'nullable|integer|min:0|max:100',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'dependencies' => 'nullable|array',
            'version' => 'nullable|string|max:50',
        ]);
        
        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
            return redirect()->route('tasks.index')->with('error', 'Tasks file not found');
        }
        
        // Load tasks
        $taskData = json_decode(File::get($this->tasksFile), true);
        
        // Find the task
        $taskIndex = null;
        foreach ($taskData['tasks'] as $index => $task) {
            if ($task['id'] == $id) {
                $taskIndex = $index;
                break;
            }
        }
        
        if ($taskIndex === null) {
            return redirect()->route('tasks.index')->with('error', "Task #{$id} not found");
        }
        
        // Create backup
        $backupDir = base_path('project-management/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . '/tasks_' . Carbon::now()->format('Ymd_His') . '.json';
        File::copy($this->tasksFile, $backupFile);
        
        // Process tags
        $tags = [];
        if ($request->filled('tags')) {
            $tags = array_map('trim', explode(',', $request->input('tags')));
        }
        
        // Process dependencies
        $dependencies = $request->filled('dependencies') ? $request->input('dependencies') : [];
        
        // Update task
        $taskData['tasks'][$taskIndex] = [
            'id' => (int)$id,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'assignee' => $request->input('assignee'),
            'status' => $request->input('status'),
            'priority' => $request->input('priority'),
            'created_at' => $taskData['tasks'][$taskIndex]['created_at'],
            'updated_at' => Carbon::now()->toIso8601String(),
            'due_date' => $request->filled('due_date') ? $request->input('due_date') : null,
            'related_feature' => $request->filled('related_feature') ? $request->input('related_feature') : null,
            'related_phase' => $request->filled('related_phase') ? $request->input('related_phase') : null,
            'dependencies' => array_map('intval', $dependencies),
            'progress' => $request->filled('progress') ? (int)$request->input('progress') : 0,
            'notes' => $taskData['tasks'][$taskIndex]['notes'],
            'tags' => $tags,
            'estimated_hours' => $request->filled('estimated_hours') ? (float)$request->input('estimated_hours') : 0,
            'actual_hours' => $request->filled('actual_hours') ? (float)$request->input('actual_hours') : 0,
            'version' => $request->filled('version') ? $request->input('version') : null,
        ];
        
        // Update metadata
        $taskData['metadata'] = [
            'total_tasks' => count($taskData['tasks']),
            'completed_tasks' => count(array_filter($taskData['tasks'], function($task) {
                return $task['status'] === 'completed';
            })),
            'user_tasks' => count(array_filter($taskData['tasks'], function($task) {
                return $task['assignee'] === 'user';
            })),
            'ai_tasks' => count(array_filter($taskData['tasks'], function($task) {
                return $task['assignee'] === 'ai';
            })),
            'last_updated' => Carbon::now()->toIso8601String()
        ];
        
        // Save tasks
        File::put($this->tasksFile, json_encode($taskData, JSON_PRETTY_PRINT));
        
        // Redirect to task details
        return redirect()->route('tasks.show', $id)->with('success', "Task #{$id} updated successfully");
    }
    
    /**
     * Add a note to a task
     */
    public function addNote(Request $request, $id)
    {
        // Validate request
        $request->validate([
            'note' => 'required|string'
        ]);
        
        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
            return redirect()->route('tasks.index')->with('error', 'Tasks file not found');
        }
        
        // Load tasks
        $taskData = json_decode(File::get($this->tasksFile), true);
        
        // Find the task
        $taskIndex = null;
        foreach ($taskData['tasks'] as $index => $task) {
            if ($task['id'] == $id) {
                $taskIndex = $index;
                break;
            }
        }
        
        if ($taskIndex === null) {
            return redirect()->route('tasks.index')->with('error', "Task #{$id} not found");
        }
        
        // Create backup
        $backupDir = base_path('project-management/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . '/tasks_' . Carbon::now()->format('Ymd_His') . '.json';
        File::copy($this->tasksFile, $backupFile);
        
        // Add note
        $taskData['tasks'][$taskIndex]['notes'][] = [
            'content' => $request->input('note'),
            'timestamp' => Carbon::now()->toIso8601String()
        ];
        
        // Update timestamp
        $taskData['tasks'][$taskIndex]['updated_at'] = Carbon::now()->toIso8601String();
        
        // Save tasks
        File::put($this->tasksFile, json_encode($taskData, JSON_PRETTY_PRINT));
        
        // Redirect to task details
        return redirect()->route('tasks.show', $id)->with('success', "Note added to Task #{$id}");
    }
    
    /**
     * Confirm task deletion
     */
    public function confirmDelete($id)
    {
        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
            return redirect()->route('tasks.index')->with('error', 'Tasks file not found');
        }
        
        // Load tasks
        $taskData = json_decode(File::get($this->tasksFile), true);
        
        // Find the task
        $task = null;
        foreach ($taskData['tasks'] as $t) {
            if ($t['id'] == $id) {
                $task = $t;
                break;
            }
        }
        
        if (!$task) {
            return redirect()->route('tasks.index')->with('error', "Task #{$id} not found");
        }
        
        return $this->renderTasksView('tasks.delete', [
            'task' => $task
        ]);
    }
    
    /**
     * Remove the specified task
     */
    public function destroy($id)
    {
        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
            return redirect()->route('tasks.index')->with('error', 'Tasks file not found');
        }
        
        // Load tasks
        $taskData = json_decode(File::get($this->tasksFile), true);
        
        // Find the task
        $taskExists = false;
        foreach ($taskData['tasks'] as $task) {
            if ($task['id'] == $id) {
                $taskExists = true;
                break;
            }
        }
        
        if (!$taskExists) {
            return redirect()->route('tasks.index')->with('error', "Task #{$id} not found");
        }
        
        // Create backup
        $backupDir = base_path('project-management/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . '/tasks_' . Carbon::now()->format('Ymd_His') . '.json';
        File::copy($this->tasksFile, $backupFile);
        
        // Remove task
        $taskData['tasks'] = array_values(array_filter($taskData['tasks'], function($task) use ($id) {
            return $task['id'] != $id;
        }));
        
        // Update metadata
        $taskData['metadata'] = [
            'total_tasks' => count($taskData['tasks']),
            'completed_tasks' => count(array_filter($taskData['tasks'], function($task) {
                return $task['status'] === 'completed';
            })),
            'user_tasks' => count(array_filter($taskData['tasks'], function($task) {
                return $task['assignee'] === 'user';
            })),
            'ai_tasks' => count(array_filter($taskData['tasks'], function($task) {
                return $task['assignee'] === 'ai';
            })),
            'last_updated' => Carbon::now()->toIso8601String()
        ];
        
        // Save tasks
        File::put($this->tasksFile, json_encode($taskData, JSON_PRETTY_PRINT));
        
        // Redirect to tasks index
        return redirect()->route('tasks.index')->with('success', "Task #{$id} deleted successfully");
    }
    
    /**
     * Generate report view
     */
    public function report()
    {
        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
            return $this->renderTasksView('tasks.report', [
                'tasks' => [],
                'metadata' => [
                    'total_tasks' => 0,
                    'completed_tasks' => 0,
                    'user_tasks' => 0,
                    'ai_tasks' => 0
                ],
                'summary' => [],
                'byFeature' => [],
                'byPhase' => [],
                'byStatus' => [],
                'byPriority' => [],
                'overdue' => [],
                'dueToday' => [],
                'comingSoon' => []
            ]);
        }
        
        // Load tasks
        $taskData = json_decode(File::get($this->tasksFile), true);
        $tasks = $taskData['tasks'];
        $metadata = $taskData['metadata'];
        
        // Prepare data for charts and reports
        
        // Group by status
        $byStatus = [];
        foreach ($tasks as $task) {
            $status = $task['status'];
            if (!isset($byStatus[$status])) {
                $byStatus[$status] = 0;
            }
            $byStatus[$status]++;
        }
        
        // Group by priority
        $byPriority = [];
        foreach ($tasks as $task) {
            $priority = $task['priority'];
            if (!isset($byPriority[$priority])) {
                $byPriority[$priority] = 0;
            }
            $byPriority[$priority]++;
        }
        
        // Group by feature
        $byFeature = [];
        foreach ($tasks as $task) {
            $feature = $task['related_feature'] ?: 'Unassigned';
            if (!isset($byFeature[$feature])) {
                $byFeature[$feature] = 0;
            }
            $byFeature[$feature]++;
        }
        
        // Group by phase
        $byPhase = [];
        foreach ($tasks as $task) {
            $phase = $task['related_phase'] ?: 'Unassigned';
            if (!isset($byPhase[$phase])) {
                $byPhase[$phase] = 0;
            }
            $byPhase[$phase]++;
        }
        
        // Group by version
        $byVersion = [];
        foreach ($tasks as $task) {
            $version = $task['version'] ?: 'Unassigned';
            if (!isset($byVersion[$version])) {
                $byVersion[$version] = 0;
            }
            $byVersion[$version]++;
        }
        
        // Due date info
        $today = Carbon::today()->format('Y-m-d');
        
        // Overdue tasks
        $overdue = array_filter($tasks, function($task) use ($today) {
            return $task['due_date'] && $task['due_date'] < $today && $task['status'] !== 'completed';
        });
        
        // Tasks due today
        $dueToday = array_filter($tasks, function($task) use ($today) {
            return $task['due_date'] === $today && $task['status'] !== 'completed';
        });
        
        // Tasks due in next 7 days
        $comingSoon = [];
        for ($i = 1; $i <= 7; $i++) {
            $futureDate = Carbon::today()->addDays($i)->format('Y-m-d');
            $dueTasks = array_filter($tasks, function($task) use ($futureDate) {
                return $task['due_date'] === $futureDate && $task['status'] !== 'completed';
            });
            
            if (count($dueTasks) > 0) {
                $comingSoon[$futureDate] = $dueTasks;
            }
        }
        
        return $this->renderTasksView('tasks.report', [
            'tasks' => $tasks,
            'metadata' => $metadata,
            'byStatus' => $byStatus,
            'byPriority' => $byPriority,
            'byFeature' => $byFeature,
            'byPhase' => $byPhase,
            'byVersion' => $byVersion,
            'overdue' => $overdue,
            'dueToday' => $dueToday,
            'comingSoon' => $comingSoon
        ]);
    }
    
    /**
     * Get available project versions
     */
    protected function getVersions()
    {
        $versions = [];
        
        // Check for version.json file
        if (File::exists($this->versionFile)) {
            $versionData = json_decode(File::get($this->versionFile), true);
            if (isset($versionData['version'])) {
                $versions[] = $versionData['version'];
            }
            if (isset($versionData['previous_versions']) && is_array($versionData['previous_versions'])) {
                $versions = array_merge($versions, $versionData['previous_versions']);
            }
        }
        
        // Check tasks for any other version numbers
        if (File::exists($this->tasksFile)) {
            $taskData = json_decode(File::get($this->tasksFile), true);
            foreach ($taskData['tasks'] as $task) {
                if (isset($task['version']) && !in_array($task['version'], $versions)) {
                    $versions[] = $task['version'];
                }
            }
        }
        
        // Sort versions
        usort($versions, function($a, $b) {
            return version_compare($b, $a); // Newest first
        });
        
        return $versions;
    }
    
    /**
     * Return view with tasks
     */
    protected function renderTasksView($view, $data)
    {
        // Add versions to data
        $data['versions'] = $this->getVersions();
        
        // For the index view, add task statistics
        if ($view === 'tasks.index') {
            // Load all tasks for statistics
            $allTasks = [];
            if (File::exists($this->tasksFile)) {
                $taskData = json_decode(File::get($this->tasksFile), true);
                $allTasks = $taskData['tasks'] ?? [];
            }
            
            // Calculate statistics
            $totalTasks = count($allTasks);
            
            // Count tasks by status
            $completedTasks = count(array_filter($allTasks, function($task) {
                return $task['status'] === 'completed';
            }));
            
            $inProgressTasks = count(array_filter($allTasks, function($task) {
                return $task['status'] === 'in-progress';
            }));
            
            $pendingTasks = count(array_filter($allTasks, function($task) {
                return $task['status'] === 'pending';
            }));
            
            $blockedTasks = count(array_filter($allTasks, function($task) {
                return $task['status'] === 'blocked';
            }));
            
            $reviewTasks = count(array_filter($allTasks, function($task) {
                return $task['status'] === 'review';
            }));
            
            // Count tasks by priority
            $highPriorityTasks = count(array_filter($allTasks, function($task) {
                return $task['priority'] === 'high';
            }));
            
            $mediumPriorityTasks = count(array_filter($allTasks, function($task) {
                return $task['priority'] === 'medium';
            }));
            
            $lowPriorityTasks = count(array_filter($allTasks, function($task) {
                return $task['priority'] === 'low';
            }));
            
            // Calculate percentages
            $inProgressPercentage = $totalTasks > 0 ? ($inProgressTasks / $totalTasks) * 100 : 0;
            $highPriorityPercentage = $totalTasks > 0 ? ($highPriorityTasks / $totalTasks) * 100 : 0;
            $mediumPriorityPercentage = $totalTasks > 0 ? ($mediumPriorityTasks / $totalTasks) * 100 : 0;
            $lowPriorityPercentage = $totalTasks > 0 ? ($lowPriorityTasks / $totalTasks) * 100 : 0;
            
            // Find upcoming tasks (due within 7 days)
            $upcomingTasks = array_filter($allTasks, function($task) {
                if (empty($task['due_date']) || $task['status'] === 'completed') {
                    return false;
                }
                
                $dueDate = strtotime($task['due_date']);
                $today = strtotime('today');
                $sevenDaysLater = strtotime('+7 days');
                
                return $dueDate >= $today && $dueDate <= $sevenDaysLater;
            });
            
            // Add statistics to data
            $data['totalTasks'] = $totalTasks;
            $data['completedTasks'] = $completedTasks;
            $data['inProgressTasks'] = $inProgressTasks;
            $data['pendingTasks'] = $pendingTasks;
            $data['blockedTasks'] = $blockedTasks;
            $data['reviewTasks'] = $reviewTasks;
            $data['highPriorityTasks'] = $highPriorityTasks;
            $data['mediumPriorityTasks'] = $mediumPriorityTasks;
            $data['lowPriorityTasks'] = $lowPriorityTasks;
            $data['inProgressPercentage'] = $inProgressPercentage;
            $data['highPriorityPercentage'] = $highPriorityPercentage;
            $data['mediumPriorityPercentage'] = $mediumPriorityPercentage;
            $data['lowPriorityPercentage'] = $lowPriorityPercentage;
            $data['upcomingTasks'] = array_values($upcomingTasks);
        }
        
        return view($view, $data);
    }
    
    /**
     * Initialize the tasks file with default structure
     */
    protected function initializeTasksFile()
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
        
        // Create directory if it doesn't exist
        if (!File::exists(dirname($this->tasksFile))) {
            File::makeDirectory(dirname($this->tasksFile), 0755, true);
        }
        
        File::put($this->tasksFile, json_encode($initialContent, JSON_PRETTY_PRINT));
        
        return $initialContent;
    }

    /**
     * Handle AI-generated tasks
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleAiTasks(Request $request)
    {
        // Get tasks file path
        $tasksFile = base_path('project-management/tasks.json');
        
        // Check if the file exists
        if (!file_exists($tasksFile)) {
            return response()->json([
                'error' => 'Tasks file not found'
            ], 404);
        }
        
        try {
            // Read tasks data
            $tasksData = json_decode(file_get_contents($tasksFile), true);
            
            // Filter AI-assigned tasks
            $aiTasks = array_filter($tasksData['tasks'], function($task) {
                return $task['assignee'] === 'ai';
            });
            
            // Get stats
            $pendingCount = count(array_filter($aiTasks, function($task) {
                return $task['status'] === 'pending';
            }));
            
            $completedCount = count(array_filter($aiTasks, function($task) {
                return $task['status'] === 'completed';
            }));
            
            $inProgressCount = count(array_filter($aiTasks, function($task) {
                return $task['status'] === 'in-progress';
            }));
            
            // Get AI tasks ordered by priority
            usort($aiTasks, function($a, $b) {
                $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
                return $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
            });
            
            return response()->json([
                'ai_tasks' => array_values($aiTasks),
                'stats' => [
                    'total' => count($aiTasks),
                    'pending' => $pendingCount,
                    'completed' => $completedCount,
                    'in_progress' => $inProgressCount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process AI tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync task to GitHub issue
     */
    public function syncToGitHub($id, GitHubService $github)
    {
        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
            return redirect()->route('tasks.index')->with('error', 'Tasks file not found');
        }
        
        // Load tasks
        $taskData = json_decode(File::get($this->tasksFile), true);
        
        // Find the task
        $task = null;
        foreach ($taskData['tasks'] as $t) {
            if ($t['id'] == $id) {
                $task = $t;
                break;
            }
        }
        
        if (!$task) {
            return redirect()->route('tasks.index')->with('error', "Task #{$id} not found");
        }
        
        // Set repository from environment if not specified
        $repository = request('repository') ?: env('GITHUB_REPOSITORY');
        
        if (!$repository) {
            return redirect()->route('tasks.show', $id)->with('error', 'No GitHub repository specified. Please set GITHUB_REPOSITORY in .env or provide it in the request.');
        }
        
        // Create or update GitHub issue
        $github->setRepository($repository);
        $githubIssue = $github->syncTaskToGitHub($id);
        
        if (!$githubIssue) {
            return redirect()->route('tasks.show', $id)->with('error', 'Failed to sync task to GitHub.');
        }
        
        return redirect()->route('tasks.show', $id)->with('success', "Task synced to GitHub issue #{$githubIssue->issue_number}");
    }
    
    /**
     * Webhook endpoint for GitHub issue updates
     */
    public function githubWebhook(Request $request, GitHubService $github)
    {
        $payload = $request->input();
        
        if (empty($payload) || !isset($payload['issue'])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 400);
        }
        
        // Extract repository info
        $repository = $payload['repository']['full_name'];
        $issueNumber = $payload['issue']['number'];
        
        // Find GitHub issue in our database
        $githubIssue = GitHubIssue::where('repository', $repository)
            ->where('issue_number', $issueNumber)
            ->first();
            
        if (!$githubIssue) {
            return response()->json(['status' => 'error', 'message' => 'GitHub issue not found in our records'], 404);
        }
        
        // Sync the issue data to the task
        $result = $github->syncGitHubToTask($githubIssue);
        
        if (!$result) {
            return response()->json(['status' => 'error', 'message' => 'Failed to sync GitHub issue to task'], 500);
        }
        
        return response()->json(['status' => 'success', 'message' => 'GitHub issue synced to task']);
    }
} 