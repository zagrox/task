<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Repository;
use App\Services\GitHubService;

class RepositoryController extends Controller
{
    /**
     * Display a repository dashboard with stats
     */
    public function index(GitHubService $githubService)
    {
        // Get all repositories
        $repositories = Repository::all();
        
        // Initialize statistics arrays
        $repoStats = [];
        $totalStats = [
            'repo_count' => 0,
            'task_count' => 0,
            'pending_count' => 0,
            'in_progress_count' => 0,
            'completed_count' => 0,
            'completion_rate' => 0
        ];
        
        // Calculate stats for each repository
        foreach ($repositories as $repo) {
            $tasks = $repo->tasks;
            
            $stats = [
                'task_count' => $tasks->count(),
                'pending_count' => $tasks->where('status', Task::STATUS_PENDING)->count(),
                'in_progress_count' => $tasks->where('status', Task::STATUS_IN_PROGRESS)->count(),
                'completed_count' => $tasks->where('status', Task::STATUS_COMPLETED)->count(),
                'completion_rate' => $tasks->count() > 0 
                    ? round(($tasks->where('status', Task::STATUS_COMPLETED)->count() / $tasks->count()) * 100) 
                    : 0
            ];
            
            $repoStats[$repo->id] = $stats;
            
            // Add to totals
            $totalStats['repo_count']++;
            $totalStats['task_count'] += $stats['task_count'];
            $totalStats['pending_count'] += $stats['pending_count'];
            $totalStats['in_progress_count'] += $stats['in_progress_count'];
            $totalStats['completed_count'] += $stats['completed_count'];
        }
        
        // Calculate overall completion rate
        $totalStats['completion_rate'] = $totalStats['task_count'] > 0
            ? round(($totalStats['completed_count'] / $totalStats['task_count']) * 100)
            : 0;
        
        // Get tasks with no repository
        $untaggedTasks = Task::whereNull('repository_id')->get();
        
        // Calculate stats for untagged tasks
        $untaggedStats = [
            'task_count' => $untaggedTasks->count(),
            'pending_count' => $untaggedTasks->where('status', Task::STATUS_PENDING)->count(),
            'in_progress_count' => $untaggedTasks->where('status', Task::STATUS_IN_PROGRESS)->count(),
            'completed_count' => $untaggedTasks->where('status', Task::STATUS_COMPLETED)->count(),
            'completion_rate' => $untaggedTasks->count() > 0 
                ? round(($untaggedTasks->where('status', Task::STATUS_COMPLETED)->count() / $untaggedTasks->count()) * 100) 
                : 0
        ];
        
        return view('repositories.index', [
            'repositories' => $repositories,
            'repoStats' => $repoStats,
            'totalStats' => $totalStats,
            'untaggedStats' => $untaggedStats
        ]);
    }
    
    /**
     * Show details for a specific repository
     */
    public function show(Repository $repository, GitHubService $githubService)
    {
        // Get all tasks with this repository
        $tasks = $repository->tasks;
        
        // Get GitHub issues for this repository if possible
        $issues = [];
        if ($repository->github_repo) {
            try {
                $issues = $githubService->getRepositoryIssues($repository->github_repo);
            } catch (\Exception $e) {
                // GitHub connection failed, continue without issues
            }
        }
        
        // Calculate stats
        $stats = [
            'task_count' => $tasks->count(),
            'pending_count' => $tasks->where('status', Task::STATUS_PENDING)->count(),
            'in_progress_count' => $tasks->where('status', Task::STATUS_IN_PROGRESS)->count(),
            'completed_count' => $tasks->where('status', Task::STATUS_COMPLETED)->count(),
            'completion_rate' => $tasks->count() > 0 
                ? round(($tasks->where('status', Task::STATUS_COMPLETED)->count() / $tasks->count()) * 100) 
                : 0,
            'issue_count' => count($issues),
            'priority_high' => $tasks->where('priority', Task::PRIORITY_HIGH)->count(),
            'priority_medium' => $tasks->where('priority', Task::PRIORITY_MEDIUM)->count(),
            'priority_low' => $tasks->where('priority', Task::PRIORITY_LOW)->count(),
            'priority_none' => $tasks->filter(function($task) {
                return is_null($task->priority) || $task->priority === '';
            })->count()
        ];
        
        return view('repositories.show', [
            'repository' => $repository,
            'tasks' => $tasks,
            'stats' => $stats,
            'issues' => $issues
        ]);
    }
    
    /**
     * Show the form for creating a new repository
     */
    public function create()
    {
        return view('repositories.create');
    }
    
    /**
     * Store a newly created repository in the database
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:repositories,name',
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|max:7',
            'github_repo' => 'nullable|string|max:255',
        ]);
        
        $repository = Repository::create([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'github_repo' => $request->github_repo,
        ]);
        
        return redirect()->route('repositories.show', $repository->id)
            ->with('success', 'Repository created successfully');
    }
    
    /**
     * Show the form for editing the specified repository
     */
    public function edit(Repository $repository)
    {
        return view('repositories.edit', compact('repository'));
    }
    
    /**
     * Update the specified repository in the database
     */
    public function update(Request $request, Repository $repository)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:repositories,name,' . $repository->id,
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|max:7',
            'github_repo' => 'nullable|string|max:255',
        ]);
        
        $repository->update([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'github_repo' => $request->github_repo,
        ]);
        
        return redirect()->route('repositories.show', $repository->id)
            ->with('success', 'Repository updated successfully');
    }
    
    /**
     * Remove the specified repository from storage
     */
    public function destroy(Repository $repository)
    {
        // Check if there are tasks associated with this repository
        $taskCount = $repository->tasks()->count();
        
        if ($taskCount > 0) {
            return redirect()->route('repositories.show', $repository->id)
                ->with('error', 'Cannot remove a repository that has tasks. Remove tasks first or reassign them to another repository.');
        }
        
        $repository->delete();
        
        return redirect()->route('repositories.index')
            ->with('success', 'Repository has been removed from Task Manager successfully. The actual Git repository was not affected.');
    }
    
    /**
     * Connect a repository to GitHub
     */
    public function connectToGitHub(Request $request, Repository $repository)
    {
        $request->validate([
            'github_repo' => 'required|string|max:255',
        ]);
        
        $repository->update([
            'github_repo' => $request->github_repo,
        ]);
        
        return redirect()->route('repositories.show', $repository->id)
            ->with('success', 'Repository connected to GitHub successfully');
    }
    
    /**
     * Sync repository tasks with GitHub issues
     */
    public function syncWithGitHub(Repository $repository, GitHubService $githubService)
    {
        if (!$repository->github_repo) {
            return redirect()->route('repositories.show', $repository->id)
                ->with('error', 'Repository is not connected to GitHub');
        }
        
        try {
            // Get all tasks for this repository
            $tasks = $repository->tasks;
            
            // For each task, try to sync it to GitHub
            $syncedCount = 0;
            foreach ($tasks as $task) {
                if (!$task->github_issue_url) {
                    $githubService->syncTaskToGitHub($task);
                    $syncedCount++;
                }
            }
            
            return redirect()->route('repositories.show', $repository->id)
                ->with('success', "Synced {$syncedCount} tasks to GitHub");
        } catch (\Exception $e) {
            return redirect()->route('repositories.show', $repository->id)
                ->with('error', 'Failed to sync with GitHub: ' . $e->getMessage());
        }
    }
    
    /**
     * Sync all repositories from GitHub
     */
    public function syncAllRepositories(GitHubService $githubService)
    {
        try {
            // Get organization name from config
            $organization = env('GITHUB_ORGANIZATION', '');
            
            if (empty($organization)) {
                return redirect()->route('repositories.index')
                    ->with('error', 'GitHub organization not specified. Please set GITHUB_ORGANIZATION in your .env file.');
            }
            
            // Check access token
            if (empty(env('GITHUB_ACCESS_TOKEN'))) {
                return redirect()->route('repositories.index')
                    ->with('error', 'GitHub access token not specified. Please set GITHUB_ACCESS_TOKEN in your .env file.');
            }
            
            // Fetch all repositories from GitHub
            $githubRepos = $githubService->getAllRepositories($organization);
            
            if (empty($githubRepos)) {
                // If no repositories were found, suggest manual creation
                return redirect()->route('repositories.index')
                    ->with('error', 'No repositories found in the GitHub organization or unable to fetch repositories. Check your GitHub token permissions and organization name. You can still create repositories manually.');
            }
            
            $stats = [
                'created' => 0,
                'updated' => 0,
                'unchanged' => 0,
                'total' => count($githubRepos)
            ];
            
            // For each GitHub repository, create or update in our database
            foreach ($githubRepos as $repo) {
                // Skip repositories without names
                if (empty($repo['name'])) {
                    continue;
                }
                
                $repository = Repository::firstOrNew(['name' => $repo['name']]);
                
                if (!$repository->exists) {
                    // New repository
                    $repository->fill([
                        'name' => $repo['name'],
                        'description' => $repo['description'] ?? null,
                        'github_repo' => $repo['full_name'],
                        // Generate a random color for new repositories
                        'color' => '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT)
                    ]);
                    $repository->save();
                    $stats['created']++;
                } else if ($repository->github_repo !== $repo['full_name'] || $repository->description !== $repo['description']) {
                    // Existing repository with changes
                    $repository->github_repo = $repo['full_name'];
                    $repository->description = $repo['description'] ?? $repository->description;
                    $repository->save();
                    $stats['updated']++;
                } else {
                    // No changes
                    $stats['unchanged']++;
                }
            }
            
            // If we have at least one repository created or updated, consider it a success
            if ($stats['created'] > 0 || $stats['updated'] > 0) {
                return redirect()->route('repositories.index')
                    ->with('success', "Successfully synced repositories from GitHub ({$stats['created']} created, {$stats['updated']} updated, {$stats['unchanged']} unchanged)");
            } else if ($stats['unchanged'] > 0) {
                return redirect()->route('repositories.index')
                    ->with('info', "All repositories ({$stats['unchanged']}) are already up to date. No changes were needed.");
            } else {
                return redirect()->route('repositories.index')
                    ->with('warning', "No repositories were processed. You may need to check your GitHub configuration or create repositories manually.");
            }
            
        } catch (\Exception $e) {
            // Log the detailed error
            \Log::error("Failed to sync repositories: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(), 
                'organization' => env('GITHUB_ORGANIZATION')
            ]);
            
            return redirect()->route('repositories.index')
                ->with('error', 'Failed to sync repositories: ' . $e->getMessage() . '. You can still create repositories manually.');
        }
    }
} 