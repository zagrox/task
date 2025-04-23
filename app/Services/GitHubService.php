<?php

namespace App\Services;

use App\Models\GitHubIssue;
use App\Models\Task;
use App\Models\Tag;
use Github\Client;
use Github\Exception\RuntimeException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GitHubService
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $repository;

    /**
     * Constructor
     */
    public function __construct(Client $client = null)
    {
        if ($client) {
            $this->client = $client;
        } else {
            $this->client = new Client();
        }
        
        // Configure authentication if available
        if (env('GITHUB_ACCESS_TOKEN')) {
            $this->client->authenticate(
                env('GITHUB_ACCESS_TOKEN'),
                null,
                Client::AUTH_ACCESS_TOKEN
            );
        }
        
        // Set default repository if configured
        $this->repository = env('GITHUB_REPOSITORY');
    }

    /**
     * Set the repository to work with
     */
    public function setRepository(string $repository): self
    {
        $this->repository = $repository;
        return $this;
    }

    /**
     * Parse a repository string into owner and repo name
     */
    protected function parseRepository(string $repository = null): array
    {
        $repo = $repository ?? $this->repository;
        
        if (empty($repo)) {
            throw new \InvalidArgumentException('Repository not specified');
        }
        
        $parts = explode('/', $repo);
        
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException('Invalid repository format. Use owner/repo');
        }
        
        return [
            'owner' => $parts[0],
            'repo' => $parts[1]
        ];
    }

    /**
     * Get repository from task tags
     */
    public function getRepositoryFromTask($task): ?string
    {
        // Check if task has tags
        if (!isset($task['tags']) || !is_array($task['tags'])) {
            return $this->repository;
        }
        
        // Check for repo: tags
        foreach ($task['tags'] as $tag) {
            if (strpos($tag, 'repo:') === 0) {
                // Extract repository name from tag (remove the "repo:" prefix)
                $repoName = substr($tag, 5);
                return $repoName;
            }
        }
        
        // Fall back to default repository
        return $this->repository;
    }
    
    /**
     * Synchronize a task to GitHub issue
     */
    public function syncTaskToGitHub(int $taskId): ?GitHubIssue
    {
        // Add debug logging
        Log::info("Starting GitHub sync for task #{$taskId}");
        
        // Load the task from tasks.json
        $githubIssue = GitHubIssue::firstOrNew(['task_id' => $taskId]);
        
        Log::info("GitHub issue model created/loaded", [
            'is_new' => !$githubIssue->exists,
            'issue_number' => $githubIssue->issue_number,
            'repository' => $githubIssue->repository ?: $this->repository
        ]);
        
        $task = $githubIssue->getTask();
        
        if (!$task) {
            Log::error("Task #$taskId not found for GitHub sync");
            return null;
        }
        
        // Check if task has a repository_id
        $repository = $this->repository; // Default repository
        
        if (isset($task['repository_id']) && !empty($task['repository_id'])) {
            // Try to load the repository model
            try {
                $repositoryModel = app('db')->table('repositories')->find($task['repository_id']);
                
                if ($repositoryModel && !empty($repositoryModel->github_repo)) {
                    Log::info("Found repository from repository_id", [
                        'repository_id' => $task['repository_id'],
                        'repository' => $repositoryModel->github_repo
                    ]);
                    $repository = $repositoryModel->github_repo;
                } else {
                    // Fall back to tags-based repository
                    Log::warning("Repository not found for ID {$task['repository_id']}, falling back to tags");
                    $repository = $this->getRepositoryFromTask($task);
                }
            } catch (\Exception $e) {
                Log::error("Error loading repository: " . $e->getMessage());
                $repository = $this->getRepositoryFromTask($task);
            }
        } else {
            // Fall back to tags-based repository
            $repository = $this->getRepositoryFromTask($task);
        }
        
        if (!$githubIssue->repository) {
            $githubIssue->repository = $repository;
            Log::info("Set repository to {$repository}");
        }
        
        Log::info("Task found", ['title' => $task['title'], 'repository' => $repository]);
        
        try {
            $repo = $this->parseRepository($githubIssue->repository);
            Log::info("Parsed repository", ['owner' => $repo['owner'], 'repo' => $repo['repo']]);
            
            // Prepare issue body with monitoring note
            $body = $githubIssue->getIssueBodyFromTask();
            $body .= "\n\n> **Note**: GitHub issues are used for monitoring only. Primary task management happens in the task manager system. ";
            $body .= "All tasks appear with 'user' assignee in GitHub for simplicity, regardless of actual assignee in the task manager.";
            
            // Prepare issue data
            $issueData = [
                'title' => $task['title'],
                'body' => $body,
                'labels' => $githubIssue->getLabelsFromTask(),
            ];
            
            // Set issue state based on task status
            if ($task['status'] === 'completed') {
                $issueData['state'] = 'closed';
            } else {
                $issueData['state'] = 'open';
            }
            
            // If issue doesn't exist yet, create it
            if (!$githubIssue->issue_number) {
                $response = $this->client->api('issue')->create(
                    $repo['owner'],
                    $repo['repo'],
                    $issueData
                );
                
                $githubIssue->issue_number = $response['number'];
                $githubIssue->issue_url = $response['html_url'];
                $githubIssue->issue_state = $response['state'];
            } else {
                // Otherwise update the existing issue
                $this->client->api('issue')->update(
                    $repo['owner'],
                    $repo['repo'],
                    $githubIssue->issue_number,
                    $issueData
                );
            }
            
            // Update the sync timestamp
            $githubIssue->last_synced_at = now();
            $githubIssue->save();
            
            return $githubIssue;
        } catch (RuntimeException $e) {
            Log::error("GitHub API error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Synchronize GitHub issue to task
     */
    public function syncGitHubToTask(GitHubIssue $githubIssue): bool
    {
        try {
            $repo = $this->parseRepository($githubIssue->repository);
            
            // Get issue from GitHub
            $issue = $this->client->api('issue')->show(
                $repo['owner'],
                $repo['repo'],
                $githubIssue->issue_number
            );
            
            if (!$issue) {
                Log::error("GitHub issue #{$githubIssue->issue_number} not found");
                return false;
            }
            
            // Get the current tasks file
            $tasksFile = base_path('project-management/tasks.json');
            $taskData = json_decode(file_get_contents($tasksFile), true);
            
            // Find the task index
            $taskIndex = null;
            foreach ($taskData['tasks'] as $index => $task) {
                if ($task['id'] == $githubIssue->task_id) {
                    $taskIndex = $index;
                    break;
                }
            }
            
            if ($taskIndex === null) {
                Log::error("Task #{$githubIssue->task_id} not found in tasks file");
                return false;
            }
            
            // Update task status if issue was closed or reopened
            if ($issue['state'] === 'closed' && $taskData['tasks'][$taskIndex]['status'] !== 'completed') {
                $taskData['tasks'][$taskIndex]['status'] = 'completed';
                $taskData['tasks'][$taskIndex]['updated_at'] = Carbon::now()->toIso8601String();
                
                // Add a note about the change
                if (!isset($taskData['tasks'][$taskIndex]['notes'])) {
                    $taskData['tasks'][$taskIndex]['notes'] = [];
                }
                
                $taskData['tasks'][$taskIndex]['notes'][] = [
                    'text' => "Task marked as completed automatically due to GitHub issue being closed.",
                    'created_at' => Carbon::now()->toIso8601String()
                ];
            } else if ($issue['state'] === 'open' && $taskData['tasks'][$taskIndex]['status'] === 'completed') {
                $taskData['tasks'][$taskIndex]['status'] = 'in-progress';
                $taskData['tasks'][$taskIndex]['updated_at'] = Carbon::now()->toIso8601String();
                
                // Add a note about the change
                if (!isset($taskData['tasks'][$taskIndex]['notes'])) {
                    $taskData['tasks'][$taskIndex]['notes'] = [];
                }
                
                $taskData['tasks'][$taskIndex]['notes'][] = [
                    'text' => "Task marked as in-progress automatically due to GitHub issue being reopened.",
                    'created_at' => Carbon::now()->toIso8601String()
                ];
            }
            
            // Update task title if changed
            if ($issue['title'] !== $taskData['tasks'][$taskIndex]['title']) {
                $oldTitle = $taskData['tasks'][$taskIndex]['title'];
                $taskData['tasks'][$taskIndex]['title'] = $issue['title'];
                $taskData['tasks'][$taskIndex]['updated_at'] = Carbon::now()->toIso8601String();
                
                // Add a note about the change
                if (!isset($taskData['tasks'][$taskIndex]['notes'])) {
                    $taskData['tasks'][$taskIndex]['notes'] = [];
                }
                
                $taskData['tasks'][$taskIndex]['notes'][] = [
                    'text' => "Title updated via GitHub from '{$oldTitle}' to '{$issue['title']}'.",
                    'created_at' => Carbon::now()->toIso8601String()
                ];
            }
            
            // Save changes to tasks file
            file_put_contents($tasksFile, json_encode($taskData, JSON_PRETTY_PRINT));
            
            // Update GitHubIssue record
            $githubIssue->issue_state = $issue['state'];
            $githubIssue->last_synced_at = now();
            $githubIssue->save();
            
            return true;
        } catch (RuntimeException $e) {
            Log::error("GitHub API error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all issues from a repository
     */
    public function getRepositoryIssues(string $repository = null, string $state = 'all'): array
    {
        try {
            $repo = $this->parseRepository($repository);
            
            $issues = $this->client->api('issue')->all(
                $repo['owner'],
                $repo['repo'],
                ['state' => $state]
            );
            
            return $issues;
        } catch (RuntimeException $e) {
            Log::error("GitHub API error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get repository statistics grouped by tag
     */
    public function getRepositoryStats(): array
    {
        $stats = [];
        
        // Get all repository tags
        $repoTags = Tag::where('name', 'like', 'repo:%')->get();
        
        foreach ($repoTags as $tag) {
            $repoName = substr($tag->name, 5);
            $taskCount = $tag->tasks()->count();
            $pendingCount = $tag->tasks()->where('status', Task::STATUS_PENDING)->count();
            $inProgressCount = $tag->tasks()->where('status', Task::STATUS_IN_PROGRESS)->count();
            $completedCount = $tag->tasks()->where('status', Task::STATUS_COMPLETED)->count();
            
            $stats[$repoName] = [
                'tag' => $tag,
                'task_count' => $taskCount,
                'pending_count' => $pendingCount,
                'in_progress_count' => $inProgressCount,
                'completed_count' => $completedCount,
                'completion_rate' => $taskCount > 0 ? round(($completedCount / $taskCount) * 100) : 0
            ];
        }
        
        return $stats;
    }

    /**
     * Get all repositories from a GitHub organization
     *
     * @param string|null $organization GitHub organization name (defaults to GITHUB_ORGANIZATION env variable)
     * @return array Array of repositories with name, description, and full_name
     */
    public function getAllRepositories(string $organization = null): array
    {
        try {
            $org = $organization ?? env('GITHUB_ORGANIZATION', '');
            
            if (empty($org)) {
                throw new \InvalidArgumentException('GitHub organization not specified');
            }

            Log::info("Fetching repositories for organization: {$org}");
            
            // Make sure the client is authenticated
            if (!env('GITHUB_ACCESS_TOKEN')) {
                Log::error("GitHub API token is not configured in environment");
                return [];
            }
            
            // Explicitly re-authenticate the client to ensure we have valid credentials
            $this->client->authenticate(
                env('GITHUB_ACCESS_TOKEN'),
                null,
                Client::AUTH_ACCESS_TOKEN
            );
            
            // Try to fetch user repositories first as a fallback
            try {
                // Fetch all repositories for the authenticated user
                Log::info("Attempting to fetch user repositories first");
                $repositories = $this->client->api('current_user')->repositories();
                
                if (!empty($repositories)) {
                    Log::info("Successfully fetched " . count($repositories) . " user repositories");
                }
            } catch (\Exception $e) {
                Log::warning("Failed to fetch user repositories: " . $e->getMessage());
                $repositories = [];
            }
            
            // If we couldn't get user repos or have none, try organization repos
            if (empty($repositories)) {
                try {
                    // Fetch all repositories for the organization
                    Log::info("Attempting to fetch organization repositories");
                    $repositories = $this->client->api('organization')->repositories($org);
                } catch (\Exception $e) {
                    Log::error("Failed to fetch organization repositories: " . $e->getMessage());
                    
                    // Try one more fallback to public repositories
                    try {
                        Log::info("Attempting to fetch public repositories for org {$org}");
                        $repositories = $this->client->api('repo')->org($org);
                    } catch (\Exception $e2) {
                        Log::error("Failed to fetch public repositories: " . $e2->getMessage());
                        return [];
                    }
                }
            }
            
            // Format the response to contain just the data we need
            $formattedRepos = [];
            foreach ($repositories as $repo) {
                $formattedRepos[] = [
                    'name' => $repo['name'],
                    'full_name' => $repo['full_name'] ?? "{$org}/" . $repo['name'],
                    'description' => $repo['description'] ?? '',
                    'html_url' => $repo['html_url'] ?? "https://github.com/{$org}/" . $repo['name'],
                    'updated_at' => $repo['updated_at'] ?? now()->toIso8601String(),
                ];
            }
            
            Log::info("Successfully formatted " . count($formattedRepos) . " repositories");
            return $formattedRepos;
            
        } catch (RuntimeException $e) {
            Log::error("GitHub API error when fetching repositories: " . $e->getMessage());
            return [];
        } catch (\Exception $e) {
            Log::error("Unexpected error when fetching repositories: " . $e->getMessage());
            return [];
        }
    }
} 