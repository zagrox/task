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
    /**
     * Display a listing of the tasks
     */
    public function index()
    {
        $tasks = Task::with('tags')->get();
        
        // Get versions and sort them in descending order
        $versions = $this->getVersions();
        
        // Get features and phases from existing tasks
        $features = Task::distinct('related_feature')->whereNotNull('related_feature')->pluck('related_feature')->toArray();
        $phases = Task::distinct('related_phase')->whereNotNull('related_phase')->pluck('related_phase')->toArray();
        
        // Calculate task statistics
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'completed')->count();
        $inProgressTasks = $tasks->where('status', 'in-progress')->count();
        $pendingTasks = $tasks->where('status', 'pending')->count();
        $blockedTasks = $tasks->where('status', 'blocked')->count();
        $reviewTasks = $tasks->where('status', 'review')->count();
        
        $highPriorityTasks = $tasks->where('priority', 'high')->count();
        $mediumPriorityTasks = $tasks->where('priority', 'medium')->count();
        $lowPriorityTasks = $tasks->where('priority', 'low')->count();
        
        // Calculate percentages
        $inProgressPercentage = $totalTasks > 0 ? ($inProgressTasks / $totalTasks) * 100 : 0;
        $highPriorityPercentage = $totalTasks > 0 ? ($highPriorityTasks / $totalTasks) * 100 : 0;
        $mediumPriorityPercentage = $totalTasks > 0 ? ($mediumPriorityTasks / $totalTasks) * 100 : 0;
        $lowPriorityPercentage = $totalTasks > 0 ? ($lowPriorityTasks / $totalTasks) * 100 : 0;
        
        // Find upcoming tasks (due within 7 days)
        $today = now()->startOfDay();
        $nextWeek = now()->addDays(7)->endOfDay();
        $upcomingTasks = $tasks->filter(function($task) use ($today, $nextWeek) {
            return $task->due_date 
                && $task->due_date->greaterThanOrEqualTo($today) 
                && $task->due_date->lessThanOrEqualTo($nextWeek)
                && $task->status !== 'completed';
        })->values();
        
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
        $task = Task::with(['tags', 'dependsOn', 'dependents'])->findOrFail($id);
        
        return view('tasks.show', [
            'task' => $task,
        ]);
    }
    
    /**
     * Show the form for creating a new task
     */
    public function create()
    {
        // Get versions and sort them in descending order
        $versions = $this->getVersions();
        
        // Get all features and phases
        $features = Task::distinct('related_feature')->whereNotNull('related_feature')->pluck('related_feature')->toArray();
        $phases = Task::distinct('related_phase')->whereNotNull('related_phase')->pluck('related_phase')->toArray();
        
        // Get all tags
        $tags = Tag::pluck('name')->toArray();
        
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
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee' => ['required', Rule::in(['user', 'ai'])],
            'status' => ['required', Rule::in(['pending', 'in-progress', 'completed', 'blocked', 'review'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'due_date' => 'nullable|date',
            'related_feature' => 'nullable|string|max:255',
            'related_phase' => 'nullable|string|max:255',
            'progress' => 'nullable|integer|min:0|max:100',
            'estimated_hours' => 'nullable|numeric|min:0',
            'version' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Create new task
        $task = new Task();
        $task->title = $request->input('title');
        $task->description = $request->input('description');
        $task->assignee = $request->input('assignee');
        $task->status = $request->input('status');
        $task->priority = $request->input('priority');
        $task->due_date = $request->input('due_date');
        $task->related_feature = $request->input('related_feature');
        $task->related_phase = $request->input('related_phase');
        $task->progress = $request->input('progress', 0);
        $task->estimated_hours = $request->input('estimated_hours');
        $task->actual_hours = $request->input('actual_hours', 0);
        $task->version = $request->input('version') ?: null;
        $task->notes = [];
        $task->save();

        // Handle tags
        $tagsInput = $request->input('tags');
        if ($tagsInput) {
            $tagNames = array_map('trim', explode(',', $tagsInput));
            $tagIds = [];
            
            foreach ($tagNames as $tagName) {
                if (!empty($tagName)) {
                    $tag = Tag::firstOrCreate(['name' => $tagName]);
                    $tagIds[] = $tag->id;
                }
            }
            
            $task->tags()->sync($tagIds);
        }

        // Handle dependencies
        $dependencyIds = $request->input('dependencies', []);
        if (!empty($dependencyIds)) {
            $task->dependsOn()->sync($dependencyIds);
        }

        return redirect()->route('tasks.index')
            ->with('success', 'Task created successfully.');
    }
    
    /**
     * Show the form for editing a task
     */
    public function edit($id)
    {
        $task = Task::with(['tags', 'dependsOn'])->findOrFail($id);
        
        // Get versions and sort them in descending order
        $versions = $this->getVersions();
        
        // Get all features and phases
        $features = Task::distinct('related_feature')->whereNotNull('related_feature')->pluck('related_feature')->toArray();
        $phases = Task::distinct('related_phase')->whereNotNull('related_phase')->pluck('related_phase')->toArray();
        
        // Get all tags
        $allTags = Tag::pluck('name')->toArray();
        $taskTags = $task->tags->pluck('name')->implode(', ');
        
        // Get list of all tasks for dependencies
        $allTasks = Task::where('id', '!=', $id)->get(['id', 'title']);
        $taskDependencies = $task->dependsOn->pluck('id')->toArray();
        
        return view('tasks.edit', [
            'task' => $task,
            'versions' => $versions,
            'features' => $features,
            'phases' => $phases,
            'allTags' => $allTags,
            'taskTags' => $taskTags,
            'allTasks' => $allTasks,
            'taskDependencies' => $taskDependencies
        ]);
    }
    
    /**
     * Update the specified task
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee' => ['required', Rule::in(['user', 'ai'])],
            'status' => ['required', Rule::in(['pending', 'in-progress', 'completed', 'blocked', 'review'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'due_date' => 'nullable|date',
            'related_feature' => 'nullable|string|max:255',
            'related_phase' => 'nullable|string|max:255',
            'progress' => 'nullable|integer|min:0|max:100',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'version' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Find and update task
        $task = Task::findOrFail($id);
        $task->title = $request->input('title');
        $task->description = $request->input('description');
        $task->assignee = $request->input('assignee');
        $task->status = $request->input('status');
        $task->priority = $request->input('priority');
        $task->due_date = $request->input('due_date');
        $task->related_feature = $request->input('related_feature');
        $task->related_phase = $request->input('related_phase');
        $task->progress = $request->input('progress', 0);
        $task->estimated_hours = $request->input('estimated_hours');
        $task->actual_hours = $request->input('actual_hours', 0);
        $task->version = $request->input('version') ?: null;
        
        // Add note if provided
        if ($request->filled('note')) {
            $task->addNote($request->input('note'));
        }
        
        $task->save();

        // Handle tags
        $tagsInput = $request->input('tags');
        if ($tagsInput !== null) {
            $tagNames = array_map('trim', explode(',', $tagsInput));
            $tagIds = [];
            
            foreach ($tagNames as $tagName) {
                if (!empty($tagName)) {
                    $tag = Tag::firstOrCreate(['name' => $tagName]);
                    $tagIds[] = $tag->id;
                }
            }
            
            $task->tags()->sync($tagIds);
        }

        // Handle dependencies
        $dependencyIds = $request->input('dependencies', []);
        $task->dependsOn()->sync($dependencyIds);

        return redirect()->route('tasks.show', $task->id)
            ->with('success', 'Task updated successfully.');
    }
    
    /**
     * Remove the specified task
     */
    public function destroy($id)
    {
        $task = Task::findOrFail($id);
        $task->delete();

        return redirect()->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }
    
    /**
     * Generate a tasks report
     */
    public function report(Request $request)
    {
        // Get filter options from the request
        $status = $request->query('status');
        $assignee = $request->query('assignee');
        $priority = $request->query('priority');
        $feature = $request->query('feature');
        $phase = $request->query('phase');
        $tag = $request->query('tag');
        $version = $request->query('version');
        
        // Start building the query
        $tasksQuery = Task::query();
        
        // Apply filters
        if ($status) {
            $tasksQuery->where('status', $status);
        }
        
        if ($assignee) {
            $tasksQuery->where('assignee', $assignee);
        }
        
        if ($priority) {
            $tasksQuery->where('priority', $priority);
        }
        
        if ($feature) {
            $tasksQuery->where('related_feature', $feature);
        }
        
        if ($phase) {
            $tasksQuery->where('related_phase', $phase);
        }
        
        if ($tag) {
            $tasksQuery->whereHas('tags', function ($query) use ($tag) {
                $query->where('name', $tag);
            });
        }
        
        if ($version) {
            $tasksQuery->where('version', $version);
        }
        
        // Get tasks
        $tasks = $tasksQuery->with('tags')->get();
        
        // Get versions and sort them in descending order
        $versions = $this->getVersions();
        
        // Get features and phases from existing tasks
        $features = Task::distinct('related_feature')->whereNotNull('related_feature')->pluck('related_feature')->toArray();
        $phases = Task::distinct('related_phase')->whereNotNull('related_phase')->pluck('related_phase')->toArray();
        
        // Get all tags
        $tags = Tag::pluck('name')->toArray();
        
        // Generate report statistics
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'completed')->count();
        $inProgressTasks = $tasks->where('status', 'in-progress')->count();
        $pendingTasks = $tasks->where('status', 'pending')->count();
        $blockedTasks = $tasks->where('status', 'blocked')->count();
        $reviewTasks = $tasks->where('status', 'review')->count();
        
        $userTasks = $tasks->where('assignee', 'user')->count();
        $aiTasks = $tasks->where('assignee', 'ai')->count();
        
        $criticalTasks = $tasks->where('priority', 'critical')->count();
        $highTasks = $tasks->where('priority', 'high')->count();
        $mediumTasks = $tasks->where('priority', 'medium')->count();
        $lowTasks = $tasks->where('priority', 'low')->count();
        
        $totalEstimatedHours = $tasks->sum('estimated_hours');
        $totalActualHours = $tasks->sum('actual_hours');
        
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;
        
        // Calculate statistics by feature
        $featureStats = [];
        foreach ($features as $feature) {
            $featureTasks = $tasks->where('related_feature', $feature);
            $featureStats[$feature] = [
                'total' => $featureTasks->count(),
                'completed' => $featureTasks->where('status', 'completed')->count(),
                'progress' => $featureTasks->avg('progress') ?: 0
            ];
        }
        
        // Calculate statistics by phase
        $phaseStats = [];
        foreach ($phases as $phase) {
            $phaseTasks = $tasks->where('related_phase', $phase);
            $phaseStats[$phase] = [
                'total' => $phaseTasks->count(),
                'completed' => $phaseTasks->where('status', 'completed')->count(),
                'progress' => $phaseTasks->avg('progress') ?: 0
            ];
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
            'features' => $features,
            'phases' => $phases,
            'tags' => $tags
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
        $taskVersions = Task::distinct('version')
            ->whereNotNull('version')
            ->where('version', '!=', '')
            ->pluck('version')
            ->toArray();
            
        foreach ($taskVersions as $version) {
            if (!empty($version)) {
                $versions[$version] = true;
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