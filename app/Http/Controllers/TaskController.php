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

class TaskController extends Controller
{
    protected $tasksFile;
    
    public function __construct()
    {
        $this->tasksFile = base_path('project-management/tasks.json');
    }
    
    /**
     * Display a listing of the tasks
     */
    public function index()
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
        
        return view('tasks.index', [
            'tasks' => $tasks,
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
            'upcomingTasks' => $upcomingTasks
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
        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
            return view('tasks.report', [
                'tasks' => [],
                'filters' => [],
                'stats' => [],
                'featureStats' => [],
                'phaseStats' => [],
                'versions' => [],
                'features' => [],
                'phases' => [],
                'tags' => [],
                'byFeature' => [], // Initialize empty arrays to avoid count() null errors
                'byPhase' => [],
                'byVersion' => [],
                'byStatus' => [],
                'byPriority' => [],
                'overdue' => [],
                'dueToday' => [],
                'comingSoon' => []
            ]);
        }
        
        // Load tasks
        $tasksData = json_decode(File::get($this->tasksFile), true);
        $tasks = $tasksData['tasks'] ?? [];
        
        // Get filters
        $status = $request->input('status');
        $assignee = $request->input('assignee');
        $priority = $request->input('priority');
        $feature = $request->input('feature');
        $phase = $request->input('phase');
        $tag = $request->input('tag');
        $version = $request->input('version');
        
        // Apply filters
        if ($status) {
            $tasks = array_filter($tasks, function($task) use ($status) {
                return ($task['status'] ?? '') === $status;
            });
        }
        
        if ($assignee) {
            $tasks = array_filter($tasks, function($task) use ($assignee) {
                return ($task['assignee'] ?? '') === $assignee;
            });
        }
        
        if ($priority) {
            $tasks = array_filter($tasks, function($task) use ($priority) {
                return ($task['priority'] ?? '') === $priority;
            });
        }
        
        if ($feature) {
            $tasks = array_filter($tasks, function($task) use ($feature) {
                return ($task['related_feature'] ?? '') === $feature;
            });
        }
        
        if ($phase) {
            $tasks = array_filter($tasks, function($task) use ($phase) {
                return ($task['related_phase'] ?? '') === $phase;
            });
        }
        
        if ($tag) {
            $tasks = array_filter($tasks, function($task) use ($tag) {
                return isset($task['tags']) && is_array($task['tags']) && in_array($tag, $task['tags']);
            });
        }
        
        if ($version) {
            $tasks = array_filter($tasks, function($task) use ($version) {
                return ($task['version'] ?? '') === $version;
            });
        }
        
        // Calculate statistics
        $totalTasks = count($tasks);
        
        $completedTasks = count(array_filter($tasks, function($task) {
            return ($task['status'] ?? '') === 'completed';
        }));
        
        $inProgressTasks = count(array_filter($tasks, function($task) {
            return ($task['status'] ?? '') === 'in-progress';
        }));
        
        $pendingTasks = count(array_filter($tasks, function($task) {
            return ($task['status'] ?? '') === 'pending';
        }));
        
        $blockedTasks = count(array_filter($tasks, function($task) {
            return ($task['status'] ?? '') === 'blocked';
        }));
        
        $reviewTasks = count(array_filter($tasks, function($task) {
            return ($task['status'] ?? '') === 'review';
        }));
        
        $userTasks = count(array_filter($tasks, function($task) {
            return ($task['assignee'] ?? '') === 'user';
        }));
        
        $aiTasks = count(array_filter($tasks, function($task) {
            return ($task['assignee'] ?? '') === 'ai';
        }));
        
        $criticalTasks = count(array_filter($tasks, function($task) {
            return ($task['priority'] ?? '') === 'critical';
        }));
        
        $highTasks = count(array_filter($tasks, function($task) {
            return ($task['priority'] ?? '') === 'high';
        }));
        
        $mediumTasks = count(array_filter($tasks, function($task) {
            return ($task['priority'] ?? '') === 'medium';
        }));
        
        $lowTasks = count(array_filter($tasks, function($task) {
            return ($task['priority'] ?? '') === 'low';
        }));
        
        // Calculate time statistics
        $totalEstimatedHours = array_reduce($tasks, function($carry, $task) {
            return $carry + (float)($task['estimated_hours'] ?? 0);
        }, 0);
        
        $totalActualHours = array_reduce($tasks, function($carry, $task) {
            return $carry + (float)($task['actual_hours'] ?? 0);
        }, 0);
        
        // Calculate completion rate
        $completionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
        
        // Get all features, phases and tags for filters
        $allFeatures = [];
        $allPhases = [];
        $allTags = [];
        
        foreach ($tasksData['tasks'] as $task) {
            if (!empty($task['related_feature']) && !in_array($task['related_feature'], $allFeatures)) {
                $allFeatures[] = $task['related_feature'];
            }
            
            if (!empty($task['related_phase']) && !in_array($task['related_phase'], $allPhases)) {
                $allPhases[] = $task['related_phase'];
            }
            
            if (isset($task['tags']) && is_array($task['tags'])) {
                foreach ($task['tags'] as $taskTag) {
                    if (!in_array($taskTag, $allTags)) {
                        $allTags[] = $taskTag;
                    }
                }
            }
        }
        
        // Get versions
        $versions = $this->getVersions();
        
        // Calculate feature statistics
        $featureStats = [];
        foreach ($allFeatures as $featureName) {
            $featureTasks = array_filter($tasks, function($task) use ($featureName) {
                return ($task['related_feature'] ?? '') === $featureName;
            });
            
            $featureStats[$featureName] = [
                'total' => count($featureTasks),
                'completed' => count(array_filter($featureTasks, function($task) {
                    return ($task['status'] ?? '') === 'completed';
                })),
                'progress' => array_reduce($featureTasks, function($carry, $task) {
                    return $carry + (int)($task['progress'] ?? 0);
                }, 0) / (count($featureTasks) ?: 1)
            ];
        }
        
        // Calculate phase statistics
        $phaseStats = [];
        foreach ($allPhases as $phaseName) {
            $phaseTasks = array_filter($tasks, function($task) use ($phaseName) {
                return ($task['related_phase'] ?? '') === $phaseName;
            });
            
            $phaseStats[$phaseName] = [
                'total' => count($phaseTasks),
                'completed' => count(array_filter($phaseTasks, function($task) {
                    return ($task['status'] ?? '') === 'completed';
                })),
                'progress' => array_reduce($phaseTasks, function($carry, $task) {
                    return $carry + (int)($task['progress'] ?? 0);
                }, 0) / (count($phaseTasks) ?: 1)
            ];
        }
        
        // Prepare data for charts
        $byStatus = [
            'pending' => $pendingTasks,
            'in-progress' => $inProgressTasks,
            'completed' => $completedTasks,
            'blocked' => $blockedTasks,
            'review' => $reviewTasks
        ];
        
        $byPriority = [
            'low' => $lowTasks,
            'medium' => $mediumTasks,
            'high' => $highTasks,
            'critical' => $criticalTasks
        ];
        
        // Tasks by feature for chart
        $byFeature = [];
        foreach ($allFeatures as $feature) {
            $count = count(array_filter($tasks, function($task) use ($feature) {
                return ($task['related_feature'] ?? '') === $feature;
            }));
            if ($count > 0) {
                $byFeature[$feature] = $count;
            }
        }
        
        // Tasks by phase for chart
        $byPhase = [];
        foreach ($allPhases as $phase) {
            $count = count(array_filter($tasks, function($task) use ($phase) {
                return ($task['related_phase'] ?? '') === $phase;
            }));
            if ($count > 0) {
                $byPhase[$phase] = $count;
            }
        }
        
        // Tasks by version for chart
        $byVersion = [];
        foreach ($versions as $v) {
            $count = count(array_filter($tasks, function($task) use ($v) {
                return ($task['version'] ?? '') === $v;
            }));
            if ($count > 0) {
                $byVersion[$v] = $count;
            }
        }
        
        // Find overdue, due today, and upcoming tasks
        $today = Carbon::now()->startOfDay();
        
        $overdue = array_filter($tasks, function($task) use ($today) {
            if (empty($task['due_date']) || $task['status'] === 'completed') {
                return false;
            }
            return Carbon::parse($task['due_date'])->startOfDay()->lt($today);
        });
        
        $dueToday = array_filter($tasks, function($task) use ($today) {
            if (empty($task['due_date']) || $task['status'] === 'completed') {
                return false;
            }
            return Carbon::parse($task['due_date'])->startOfDay()->eq($today);
        });
        
        // Group upcoming tasks by date
        $comingSoon = [];
        $upcoming = array_filter($tasks, function($task) use ($today) {
            if (empty($task['due_date']) || $task['status'] === 'completed') {
                return false;
            }
            $dueDate = Carbon::parse($task['due_date'])->startOfDay();
            return $dueDate->gt($today) && $dueDate->lte($today->copy()->addDays(7));
        });
        
        foreach ($upcoming as $task) {
            $dueDate = Carbon::parse($task['due_date'])->format('Y-m-d');
            if (!isset($comingSoon[$dueDate])) {
                $comingSoon[$dueDate] = [];
            }
            $comingSoon[$dueDate][] = $task;
        }
        
        return view('tasks.report', [
            'tasks' => $tasks,
            'filters' => [
                'status' => $status,
                'assignee' => $assignee,
                'priority' => $priority,
                'feature' => $feature,
                'phase' => $phase,
                'tag' => $tag,
                'version' => $version
            ],
            'stats' => [
                'total' => $totalTasks,
                'completed' => $completedTasks,
                'in_progress' => $inProgressTasks,
                'pending' => $pendingTasks,
                'blocked' => $blockedTasks,
                'review' => $reviewTasks,
                'user' => $userTasks,
                'ai' => $aiTasks,
                'critical' => $criticalTasks,
                'high' => $highTasks,
                'medium' => $mediumTasks,
                'low' => $lowTasks,
                'estimated_hours' => $totalEstimatedHours,
                'actual_hours' => $totalActualHours,
                'completion_rate' => $completionRate
            ],
            'featureStats' => $featureStats,
            'phaseStats' => $phaseStats,
            'versions' => $versions,
            'features' => $allFeatures,
            'phases' => $allPhases,
            'tags' => $allTags,
            'byFeature' => $byFeature,
            'byPhase' => $byPhase,
            'byVersion' => $byVersion,
            'byStatus' => $byStatus,
            'byPriority' => $byPriority,
            'overdue' => $overdue,
            'dueToday' => $dueToday,
            'comingSoon' => $comingSoon
        ]);
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
} 