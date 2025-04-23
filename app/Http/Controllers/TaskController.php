<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;
use App\Services\GitHubService;
use App\Models\GitHubIssue;
use Illuminate\Support\Facades\Artisan;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Repository;

class TaskController extends Controller
{
    protected $tasksFile;
    
    public function __construct()
    {
        $this->tasksFile = base_path('project-management/tasks.json');
    }
    
    /**
     * Display a listing of the tasks.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
            return view('tasks.index', [
                'tasks' => [],
                'versions' => [],
                'features' => [],
                'phases' => []
            ]);
        }
        
        // Load tasks
        $tasksData = json_decode(File::get($this->tasksFile), true);
        $tasks = $tasksData['tasks'] ?? [];
        
        // Apply filters based on request parameters
        $status = $request->input('status');
        $priority = $request->input('priority');
        $assignee = $request->input('assignee');
        $sort = $request->input('sort', 'newest'); // Default sort by newest

        // Filter by status if specified
        if ($status) {
            $tasks = array_filter($tasks, function($task) use ($status) {
                return $task['status'] === $status;
            });
        }
        
        // Filter by priority if specified
        if ($priority) {
            $tasks = array_filter($tasks, function($task) use ($priority) {
                return $task['priority'] === $priority;
            });
        }
        
        // Filter by assignee if specified
        if ($assignee) {
            $tasks = array_filter($tasks, function($task) use ($assignee) {
                return $task['assignee'] === $assignee;
            });
        }
        
        // Sort tasks by recency
        if ($sort === 'newest') {
            // Sort by created_at (newest first)
            usort($tasks, function($a, $b) {
                $aDate = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
                $bDate = isset($b['created_at']) ? strtotime($b['created_at']) : 0;
                return $bDate - $aDate; // Descending order
            });
        } elseif ($sort === 'updated') {
            // Sort by updated_at (recently updated first)
            usort($tasks, function($a, $b) {
                $aDate = isset($a['updated_at']) ? strtotime($a['updated_at']) : 0;
                $bDate = isset($b['updated_at']) ? strtotime($b['updated_at']) : 0;
                return $bDate - $aDate; // Descending order
            });
        }

        // Get versions and sort them in descending order
        $versions = $this->getVersions();
        
        // Extract features and phases
        $features = [];
        $phases = [];
        foreach ($tasks as $task) {
            if (!empty($task['related_feature']) && !in_array($task['related_feature'], $features)) {
                $features[] = $task['related_feature'];
            }
            if (!empty($task['related_phase']) && !in_array($task['related_phase'], $phases)) {
                $phases[] = $task['related_phase'];
            }
        }
        
        // Calculate task statistics
        $totalTasks = count($tasks);
        $completedTasks = count(array_filter($tasks, function($task) {
            return $task['status'] === 'completed';
        }));
        $inProgressTasks = count(array_filter($tasks, function($task) {
            return $task['status'] === 'in-progress';
        }));
        $pendingTasks = count(array_filter($tasks, function($task) {
            return $task['status'] === 'pending';
        }));
        $blockedTasks = count(array_filter($tasks, function($task) {
            return $task['status'] === 'blocked';
        }));
        $reviewTasks = count(array_filter($tasks, function($task) {
            return $task['status'] === 'review';
        }));
        
        $highPriorityTasks = count(array_filter($tasks, function($task) {
            return $task['priority'] === 'high';
        }));
        $mediumPriorityTasks = count(array_filter($tasks, function($task) {
            return $task['priority'] === 'medium';
        }));
        $lowPriorityTasks = count(array_filter($tasks, function($task) {
            return $task['priority'] === 'low';
        }));
        
        // Calculate percentages
        $inProgressPercentage = $totalTasks > 0 ? ($inProgressTasks / $totalTasks) * 100 : 0;
        $highPriorityPercentage = $totalTasks > 0 ? ($highPriorityTasks / $totalTasks) * 100 : 0;
        $mediumPriorityPercentage = $totalTasks > 0 ? ($mediumPriorityTasks / $totalTasks) * 100 : 0;
        $lowPriorityPercentage = $totalTasks > 0 ? ($lowPriorityTasks / $totalTasks) * 100 : 0;
        
        // Find upcoming tasks (due within 7 days)
        $today = Carbon::now()->startOfDay();
        $nextWeek = Carbon::now()->addDays(7)->endOfDay();
        $upcomingTasks = array_filter($tasks, function($task) use ($today, $nextWeek) {
            if (empty($task['due_date']) || $task['status'] === 'completed') {
                return false;
            }
            
            $dueDate = Carbon::parse($task['due_date']);
            return $dueDate->greaterThanOrEqualTo($today) && $dueDate->lessThanOrEqualTo($nextWeek);
        });
        
        // Pagination
        $currentPage = $request->input('page', 1);
        $perPage = 20; // Set pagination to 20 items per page
        $total = count($tasks);
        $totalPages = ceil($total / $perPage);
        
        // Get tasks for current page
        $offset = ($currentPage - 1) * $perPage;
        $paginatedTasks = array_slice($tasks, $offset, $perPage);
        
        // Load repository data for each task
        $paginatedTasks = $this->loadRepositoriesForTasks($paginatedTasks);
        
        $pagination = [
            'current_page' => (int)$currentPage,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages
        ];
        
        return view('tasks.index', [
            'tasks' => $paginatedTasks,
            'versions' => $versions,
            'features' => $features,
            'phases' => $phases,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'inProgressTasks' => $inProgressTasks,
            'pendingTasks' => $pendingTasks,
            'blockedTasks' => $blockedTasks,
            'reviewTasks' => $reviewTasks,
            'highPriorityTasks' => $highPriorityTasks,
            'mediumPriorityTasks' => $mediumPriorityTasks,
            'lowPriorityTasks' => $lowPriorityTasks,
            'inProgressPercentage' => $inProgressPercentage,
            'highPriorityPercentage' => $highPriorityPercentage,
            'mediumPriorityPercentage' => $mediumPriorityPercentage,
            'lowPriorityPercentage' => $lowPriorityPercentage,
            'upcomingTasks' => $upcomingTasks,
            'pagination' => $pagination
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
        $tasksData = json_decode(File::get($this->tasksFile), true);
        
        // Find the task
        $task = null;
        foreach ($tasksData['tasks'] as $t) {
            if ($t['id'] == $id) {
                $task = $t;
                break;
            }
        }
        
        if (!$task) {
            return redirect()->route('tasks.index')->with('error', "Task #{$id} not found");
        }
        
        // Load repository data if task has repository_id
        if (!empty($task['repository_id'])) {
            $repository = \App\Models\Repository::find($task['repository_id']);
            if ($repository) {
                $task['repository'] = [
                    'id' => $repository->id,
                    'name' => $repository->name,
                    'color' => $repository->color,
                    'github_repo' => $repository->github_repo
                ];
            }
        }
        
        // Get dependent tasks
        $dependencies = [];
        foreach ($tasksData['tasks'] as $t) {
            if (isset($t['dependencies']) && in_array($id, $t['dependencies'])) {
                $dependencies[] = $t;
            }
        }
        
        return view('tasks.show', [
            'task' => $task,
            'dependencies' => $dependencies
        ]);
    }
    
    /**
     * Show the form for creating a new task
     */
    public function create()
    {
        // Get versions and sort them in descending order
        $versions = $this->getVersions();
        
        // Get all features, phases, and tags
        $features = [];
        $phases = [];
        $tags = [];
        
        // Only try to extract if the tasks file exists
        if (File::exists($this->tasksFile)) {
            $tasksData = json_decode(File::get($this->tasksFile), true);
            $tasks = $tasksData['tasks'] ?? [];
            
            foreach ($tasks as $task) {
                if (!empty($task['related_feature']) && !in_array($task['related_feature'], $features)) {
                    $features[] = $task['related_feature'];
                }
                if (!empty($task['related_phase']) && !in_array($task['related_phase'], $phases)) {
                    $phases[] = $task['related_phase'];
                }
                if (!empty($task['tags']) && is_array($task['tags'])) {
                    foreach ($task['tags'] as $tag) {
                        if (!in_array($tag, $tags)) {
                            $tags[] = $tag;
                        }
                    }
                }
            }
        }
        
        return view('tasks.create', [
            'versions' => $versions,
            'features' => $features,
            'phases' => $phases,
            'tags' => $tags
        ]);
    }
    
    /**
     * Store a newly created task
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee' => 'required|in:user,ai',
            'status' => 'required|in:pending,in-progress,completed,blocked,review',
            'priority' => 'required|in:low,medium,high,critical',
            'due_date' => 'nullable|date',
            'related_feature' => 'nullable|string|max:255',
            'related_phase' => 'nullable|string|max:255',
            'progress' => 'nullable|integer|min:0|max:100',
            'estimated_hours' => 'nullable|numeric|min:0',
            'version' => 'nullable|string|max:255',
            'repository_id' => 'nullable|exists:repositories,id',
        ]);

        // Ensure tasks file exists, create if it doesn't
        if (!File::exists($this->tasksFile)) {
            $tasksData = [
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
        } else {
            $tasksData = json_decode(File::get($this->tasksFile), true);
        }
        
        // Get next ID
        $nextId = $tasksData['next_id'] ?? 1;
        
        // Process tags
        $tags = [];
        $tagsInput = $request->input('tags');
        if ($tagsInput) {
            $tags = array_map('trim', explode(',', $tagsInput));
            $tags = array_filter($tags, function($tag) {
                return !empty($tag);
            });
        }
        
        // Create new task
        $newTask = [
            'id' => $nextId,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'assignee' => $request->input('assignee'),
            'status' => $request->input('status'),
            'priority' => $request->input('priority'),
            'created_at' => Carbon::now()->toIso8601String(),
            'updated_at' => Carbon::now()->toIso8601String(),
            'due_date' => $request->input('due_date'),
            'related_feature' => $request->input('related_feature'),
            'related_phase' => $request->input('related_phase'),
            'dependencies' => $request->input('dependencies', []),
            'progress' => $request->input('progress', 0),
            'tags' => $tags,
            'estimated_hours' => $request->input('estimated_hours'),
            'actual_hours' => $request->input('actual_hours', 0),
            'version' => $request->input('version'),
            'repository_id' => $request->input('repository_id'),
            'notes' => []
        ];
        
        // Add the task
        $tasksData['tasks'][] = $newTask;
        $tasksData['next_id'] = $nextId + 1;
        
        // Update metadata
        $completedTasks = count(array_filter($tasksData['tasks'], function($task) {
            return ($task['status'] ?? '') === 'completed';
        }));
        
        $userTasks = count(array_filter($tasksData['tasks'], function($task) {
            return ($task['assignee'] ?? '') === 'user';
        }));
        
        $aiTasks = count(array_filter($tasksData['tasks'], function($task) {
            return ($task['assignee'] ?? '') === 'ai';
        }));
        
        $tasksData['metadata']['total_tasks'] = count($tasksData['tasks']);
        $tasksData['metadata']['completed_tasks'] = $completedTasks;
        $tasksData['metadata']['user_tasks'] = $userTasks;
        $tasksData['metadata']['ai_tasks'] = $aiTasks;
        $tasksData['metadata']['last_updated'] = Carbon::now()->toIso8601String();
        
        // Save the file
        File::put($this->tasksFile, json_encode($tasksData, JSON_PRETTY_PRINT));
        
        return redirect()->route('tasks.index')
            ->with('success', 'Task created successfully.');
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
        $tasksData = json_decode(File::get($this->tasksFile), true);
        
        // Find the task
        $task = null;
        foreach ($tasksData['tasks'] as $t) {
            if ($t['id'] == $id) {
                $task = $t;
                break;
            }
        }
        
        if (!$task) {
            return redirect()->route('tasks.index')->with('error', "Task #{$id} not found");
        }
        
        // Get versions and sort them in descending order
        $versions = $this->getVersions();
        
        // Get all features, phases, and tags
        $features = [];
        $phases = [];
        $tags = [];
        
        foreach ($tasksData['tasks'] as $t) {
            if (!empty($t['related_feature']) && !in_array($t['related_feature'], $features)) {
                $features[] = $t['related_feature'];
            }
            if (!empty($t['related_phase']) && !in_array($t['related_phase'], $phases)) {
                $phases[] = $t['related_phase'];
            }
            if (!empty($t['tags']) && is_array($t['tags'])) {
                foreach ($t['tags'] as $tag) {
                    if (!in_array($tag, $tags)) {
                        $tags[] = $tag;
                    }
                }
            }
        }
        
        // Get list of all tasks for dependencies
        $allTasks = array_filter($tasksData['tasks'], function($t) use ($id) {
            return $t['id'] != $id;
        });
        
        $taskTags = isset($task['tags']) ? implode(', ', $task['tags']) : '';
        
        return view('tasks.edit', [
            'task' => $task,
            'versions' => $versions,
            'features' => $features,
            'phases' => $phases,
            'allTags' => $tags,
            'taskTags' => $taskTags,
            'allTasks' => $allTasks,
            'taskDependencies' => $task['dependencies'] ?? []
        ]);
    }
    
    /**
     * Update the specified task
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee' => 'required|in:user,ai',
            'status' => 'required|in:pending,in-progress,completed,blocked,review',
            'priority' => 'required|in:low,medium,high,critical',
            'due_date' => 'nullable|date',
            'related_feature' => 'nullable|string|max:255',
            'related_phase' => 'nullable|string|max:255',
            'progress' => 'nullable|integer|min:0|max:100',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'version' => 'nullable|string|max:255',
            'repository_id' => 'nullable|exists:repositories,id',
        ]);
        
        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
            return redirect()->route('tasks.index')->with('error', 'Tasks file not found');
        }
        
        // Load tasks
        $tasksData = json_decode(File::get($this->tasksFile), true);
        
        // Find the task
        $taskIndex = null;
        foreach ($tasksData['tasks'] as $index => $task) {
            if ($task['id'] == $id) {
                $taskIndex = $index;
                break;
            }
        }
        
        if ($taskIndex === null) {
            return redirect()->route('tasks.index')->with('error', "Task #{$id} not found");
        }
        
        // Process tags
        $tags = [];
        $tagsInput = $request->input('tags');
        if ($tagsInput) {
            $tags = array_map('trim', explode(',', $tagsInput));
            $tags = array_filter($tags, function($tag) {
                return !empty($tag);
            });
        }
        
        // Update the task
        $tasksData['tasks'][$taskIndex]['title'] = $request->input('title');
        $tasksData['tasks'][$taskIndex]['description'] = $request->input('description');
        $tasksData['tasks'][$taskIndex]['assignee'] = $request->input('assignee');
        $tasksData['tasks'][$taskIndex]['status'] = $request->input('status');
        $tasksData['tasks'][$taskIndex]['priority'] = $request->input('priority');
        $tasksData['tasks'][$taskIndex]['updated_at'] = Carbon::now()->toIso8601String();
        $tasksData['tasks'][$taskIndex]['due_date'] = $request->input('due_date');
        $tasksData['tasks'][$taskIndex]['related_feature'] = $request->input('related_feature');
        $tasksData['tasks'][$taskIndex]['related_phase'] = $request->input('related_phase');
        $tasksData['tasks'][$taskIndex]['dependencies'] = $request->input('dependencies', []);
        $tasksData['tasks'][$taskIndex]['progress'] = $request->input('progress', 0);
        $tasksData['tasks'][$taskIndex]['tags'] = $tags;
        $tasksData['tasks'][$taskIndex]['estimated_hours'] = $request->input('estimated_hours');
        $tasksData['tasks'][$taskIndex]['actual_hours'] = $request->input('actual_hours', 0);
        $tasksData['tasks'][$taskIndex]['version'] = $request->input('version');
        $tasksData['tasks'][$taskIndex]['repository_id'] = $request->input('repository_id');
        
        // Update metadata
        $completedTasks = count(array_filter($tasksData['tasks'], function($task) {
            return ($task['status'] ?? '') === 'completed';
        }));
        
        $userTasks = count(array_filter($tasksData['tasks'], function($task) {
            return ($task['assignee'] ?? '') === 'user';
        }));
        
        $aiTasks = count(array_filter($tasksData['tasks'], function($task) {
            return ($task['assignee'] ?? '') === 'ai';
        }));
        
        $tasksData['metadata']['total_tasks'] = count($tasksData['tasks']);
        $tasksData['metadata']['completed_tasks'] = $completedTasks;
        $tasksData['metadata']['user_tasks'] = $userTasks;
        $tasksData['metadata']['ai_tasks'] = $aiTasks;
        $tasksData['metadata']['last_updated'] = Carbon::now()->toIso8601String();
        
        // Save the file
        File::put($this->tasksFile, json_encode($tasksData, JSON_PRETTY_PRINT));
        
        return redirect()->route('tasks.show', $id)
            ->with('success', 'Task updated successfully.');
    }
    
    /**
     * Show confirmation page before deleting a task.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function confirmDelete($id)
    {
        $task = Task::findOrFail($id);
        return view('tasks.delete', ['task' => $task]);
    }
    
    /**
     * Delete the specified task
     */
    public function destroy($id)
    {
        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
            return redirect()->route('tasks.index')->with('error', 'Tasks file not found');
        }
        
        // Load tasks
        $tasksData = json_decode(File::get($this->tasksFile), true);
        
        // Find and remove the task
        $found = false;
        foreach ($tasksData['tasks'] as $index => $task) {
            if ($task['id'] == $id) {
                array_splice($tasksData['tasks'], $index, 1);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            return redirect()->route('tasks.index')->with('error', "Task #{$id} not found");
        }
        
        // Update metadata
        $completedTasks = count(array_filter($tasksData['tasks'], function($task) {
            return ($task['status'] ?? '') === 'completed';
        }));
        
        $userTasks = count(array_filter($tasksData['tasks'], function($task) {
            return ($task['assignee'] ?? '') === 'user';
        }));
        
        $aiTasks = count(array_filter($tasksData['tasks'], function($task) {
            return ($task['assignee'] ?? '') === 'ai';
        }));
        
        $tasksData['metadata']['total_tasks'] = count($tasksData['tasks']);
        $tasksData['metadata']['completed_tasks'] = $completedTasks;
        $tasksData['metadata']['user_tasks'] = $userTasks;
        $tasksData['metadata']['ai_tasks'] = $aiTasks;
        $tasksData['metadata']['last_updated'] = Carbon::now()->toIso8601String();
        
        // Clean up dependencies in other tasks
        foreach ($tasksData['tasks'] as &$task) {
            if (isset($task['dependencies']) && is_array($task['dependencies'])) {
                $task['dependencies'] = array_values(array_filter($task['dependencies'], function($depId) use ($id) {
                    return $depId != $id;
                }));
            }
        }
        
        // Save the file
        File::put($this->tasksFile, json_encode($tasksData, JSON_PRETTY_PRINT));
        
        return redirect()->route('tasks.index')
            ->with('success', "Task #{$id} deleted successfully.");
    }
    
    /**
     * Generate task reports
     */
    public function report(Request $request)
    {
        $selectedFeature = $request->input('feature', 'All');
        $selectedPhase = $request->input('phase', 'All');
        $selectedVersion = $request->input('version', 'All');
        
        // Get all tasks
        $tasksQuery = Task::query();
        
        // Apply filters if selected
        if ($selectedFeature !== 'All') {
            $tasksQuery->where('feature', $selectedFeature);
        }
        
        if ($selectedPhase !== 'All') {
            $tasksQuery->where('phase', $selectedPhase);
        }
        
        if ($selectedVersion !== 'All') {
            $tasksQuery->where('version', $selectedVersion);
        }
        
        $tasks = $tasksQuery->get();
        
        // Prepare statistics
        $stats = [
            'total' => $tasks->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'inProgress' => $tasks->where('status', 'in-progress')->count(),
            'pending' => $tasks->where('status', 'pending')->count(),
            'blocked' => $tasks->where('status', 'blocked')->count(),
            'user' => $tasks->where('assigned_to', '!=', 'ai')->count(),
            'ai' => $tasks->where('assigned_to', 'ai')->count(),
        ];
        
        // Distribution by status
        $byStatus = [
            'pending' => $tasks->where('status', 'pending')->count(),
            'in-progress' => $tasks->where('status', 'in-progress')->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'blocked' => $tasks->where('status', 'blocked')->count(),
            'review' => $tasks->where('status', 'review')->count(),
        ];
        
        // Distribution by priority
        $byPriority = [
            'low' => $tasks->where('priority', 'low')->count(),
            'medium' => $tasks->where('priority', 'medium')->count(),
            'high' => $tasks->where('priority', 'high')->count(),
            'critical' => $tasks->where('priority', 'critical')->count(),
        ];
        
        // Distribution by feature
        $byFeature = [];
        foreach ($tasks->groupBy('feature') as $feature => $featureTasks) {
            $byFeature[$feature] = $featureTasks->count();
        }
        
        // Distribution by phase
        $byPhase = [];
        foreach ($tasks->groupBy('phase') as $phase => $phaseTasks) {
            $byPhase[$phase] = $phaseTasks->count();
        }
        
        // Distribution by version
        $byVersion = [];
        foreach ($tasks->groupBy('version') as $version => $versionTasks) {
            $byVersion[$version] = $versionTasks->count();
        }
        
        // Get overdue tasks
        $today = Carbon::today()->format('Y-m-d');
        $overdue = $tasks->filter(function ($task) use ($today) {
            return $task->due_date < $today && $task->status !== 'completed';
        })->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'due_date' => $task->due_date,
                'priority' => $task->priority,
            ];
        })->values()->toArray();
        
        // Get tasks due today
        $dueToday = $tasks->filter(function ($task) use ($today) {
            return $task->due_date == $today && $task->status !== 'completed';
        })->map(function ($task) {
            return [
                'id' => $task->id,
                'title' => $task->title,
                'priority' => $task->priority,
                'status' => $task->status,
            ];
        })->values()->toArray();
        
        // Get upcoming deadlines for the next 7 days
        $comingSoon = [];
        for ($i = 1; $i <= 7; $i++) {
            $date = Carbon::today()->addDays($i)->format('Y-m-d');
            $dateTasks = $tasks->filter(function ($task) use ($date) {
                return $task->due_date == $date && $task->status !== 'completed';
            })->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'priority' => $task->priority,
                    'status' => $task->status,
                ];
            })->values()->toArray();
            
            if (count($dateTasks) > 0) {
                $comingSoon[$date] = $dateTasks;
            }
        }
        
        // Get all unique features, phases, and versions for filter dropdowns
        $features = ['All'];
        $phases = ['All'];
        $versions = ['All'];
        
        $features = array_merge($features, Task::distinct('feature')->pluck('feature')->toArray());
        $phases = array_merge($phases, Task::distinct('phase')->pluck('phase')->toArray());
        $versions = array_merge($versions, Task::distinct('version')->pluck('version')->toArray());
        
        return view('tasks.report', compact(
            'stats', 'byStatus', 'byPriority', 'byFeature', 'byPhase', 'byVersion',
            'overdue', 'dueToday', 'comingSoon', 'features', 'phases', 'versions',
            'selectedFeature', 'selectedPhase', 'selectedVersion'
        ));
    }
    
    /**
     * Get sorted versions from files and tasks
     */
    protected function getVersions()
    {
        $versions = [];
        
        // Get versions from version.json if it exists
        $versionFile = base_path('project-management/version.json');
        if (File::exists($versionFile)) {
            $versionData = json_decode(File::get($versionFile), true);
            if (isset($versionData['versions']) && is_array($versionData['versions'])) {
                foreach ($versionData['versions'] as $version) {
                    if (!empty($version)) {
                        $versions[$version] = true;
                    }
                }
            }
        }
        
        // Add versions from existing tasks
        if (File::exists($this->tasksFile)) {
            $tasksData = json_decode(File::get($this->tasksFile), true);
            foreach ($tasksData['tasks'] as $task) {
                if (!empty($task['version'])) {
                    $versions[$task['version']] = true;
                }
            }
        }
        
        $versions = array_keys($versions);
        
        // Sort versions in descending order
        usort($versions, function($a, $b) {
            return version_compare($b, $a);
        });
        
        return $versions;
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

    /**
     * Sync all tasks to GitHub
     */
    public function syncAllToGitHub(GitHubService $github)
    {
        try {
            // Execute the command to sync all tasks to GitHub
            Artisan::call('tasks:sync-to-github', [
                '--all' => true
            ]);
            
            $output = Artisan::output();
            
            // Extract information from command output
            if (preg_match('/(\d+) succeeded, (\d+) failed/', $output, $matches)) {
                $successCount = (int)$matches[1];
                $failCount = (int)$matches[2];
                
                if ($failCount > 0) {
                    return redirect()->back()->with('error', "GitHub sync completed with issues: {$successCount} succeeded, {$failCount} failed.");
                } else {
                    return redirect()->back()->with('success', "Successfully synced {$successCount} tasks to GitHub.");
                }
            }
            
            return redirect()->back()->with('success', 'Tasks synchronized to GitHub.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error syncing tasks to GitHub: ' . $e->getMessage());
        }
    }

    /**
     * Process AI tasks
     */
    public function processAiTasks(Request $request)
    {
        try {
            $taskId = $request->input('task_id');
            $limit = $request->input('limit', 5);
            
            if ($taskId) {
                // Process specific task
                Artisan::call('tasks:process-ai', [
                    '--task-id' => $taskId
                ]);
            } else {
                // Process multiple tasks
                Artisan::call('tasks:process-ai', [
                    '--limit' => (int)$limit
                ]);
            }
            
            $output = Artisan::output();
            
            // Extract result information from output
            $successCount = 0;
            if (preg_match('/(\d+) tasks processed/', $output, $matches)) {
                $successCount = (int)$matches[1];
            }
            
            if ($successCount > 0) {
                return redirect()->back()->with('success', "Successfully processed {$successCount} AI tasks.");
            } else {
                return redirect()->back()->with('info', "No AI tasks were processed. Ensure there are pending tasks assigned to AI.");
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error processing AI tasks: ' . $e->getMessage());
        }
    }

    /**
     * Display a list of tasks assigned to AI.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function aiTasks(Request $request)
    {
        $tasks = Task::where('assignee', 'ai')->get();
        
        // Calculate statistics
        $totalTasks = $tasks->count();
        $pendingTasks = $tasks->where('status', 'pending')->count();
        $inProgressTasks = $tasks->where('status', 'in-progress')->count();
        $completedTasks = $tasks->where('status', 'completed')->count();
        
        return view('tasks.ai-tasks', [
            'tasks' => $tasks,
            'totalTasks' => $totalTasks,
            'pendingTasks' => $pendingTasks,
            'inProgressTasks' => $inProgressTasks,
            'completedTasks' => $completedTasks
        ]);
    }

    /**
     * Load repository data for tasks with repository_id
     */
    protected function loadRepositoriesForTasks($tasks)
    {
        $repositoryIds = [];
        
        // Collect all repository IDs from tasks
        foreach ($tasks as $task) {
            if (!empty($task['repository_id'])) {
                $repositoryIds[] = $task['repository_id'];
            }
        }
        
        // If no repositories to load, return tasks as is
        if (empty($repositoryIds)) {
            return $tasks;
        }
        
        // Load repositories in one query
        $repositories = \App\Models\Repository::whereIn('id', $repositoryIds)->get()->keyBy('id');
        
        // Add repository data to each task
        foreach ($tasks as &$task) {
            if (!empty($task['repository_id']) && isset($repositories[$task['repository_id']])) {
                $repo = $repositories[$task['repository_id']];
                $task['repository'] = [
                    'id' => $repo->id,
                    'name' => $repo->name,
                    'color' => $repo->color,
                    'github_repo' => $repo->github_repo
                ];
            }
        }
        
        return $tasks;
    }
} 