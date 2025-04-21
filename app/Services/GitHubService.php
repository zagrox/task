<?php

namespace App\Services;

use App\Models\GitHubIssue;
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
        
        if (!$githubIssue->repository) {
            $githubIssue->repository = $this->repository;
            Log::info("Set repository to {$this->repository}");
        }
        
        $task = $githubIssue->getTask();
        
        if (!$task) {
            Log::error("Task #$taskId not found for GitHub sync");
            return null;
        }
        
        Log::info("Task found", ['title' => $task['title']]);
        
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
            
            // Update task data
            $taskData['tasks'][$taskIndex]['title'] = $issue['title'];
            
            // Update status based on issue state
            if ($issue['state'] === 'closed') {
                $taskData['tasks'][$taskIndex]['status'] = 'completed';
                $taskData['tasks'][$taskIndex]['progress'] = 100;
            } elseif ($issue['state'] === 'open') {
                // Only change from completed to something else, don't override in-progress or other statuses
                if ($taskData['tasks'][$taskIndex]['status'] === 'completed') {
                    $taskData['tasks'][$taskIndex]['status'] = 'in-progress';
                }
            }
            
            // Update priority and other metadata based on labels
            if (!empty($issue['labels'])) {
                foreach ($issue['labels'] as $label) {
                    if (strpos($label['name'], 'priority:') === 0) {
                        $priority = str_replace('priority:', '', $label['name']);
                        $taskData['tasks'][$taskIndex]['priority'] = $priority;
                    }
                }
            }
            
            // Add a note about the GitHub sync
            $taskData['tasks'][$taskIndex]['notes'][] = [
                'content' => "Synchronized with GitHub issue #{$githubIssue->issue_number}: {$githubIssue->issue_url}",
                'timestamp' => Carbon::now()->toIso8601String()
            ];
            
            // Update task updated timestamp
            $taskData['tasks'][$taskIndex]['updated_at'] = Carbon::now()->toIso8601String();
            
            // Save the updated tasks file
            file_put_contents($tasksFile, json_encode($taskData, JSON_PRETTY_PRINT));
            
            // Update the sync timestamp
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
     * Get all issues for a repository
     */
    public function getRepositoryIssues(string $repository = null, string $state = 'all'): array
    {
        try {
            $repo = $this->parseRepository($repository);
            
            return $this->client->api('issue')->all(
                $repo['owner'],
                $repo['repo'],
                ['state' => $state]
            );
        } catch (RuntimeException $e) {
            Log::error("GitHub API error: " . $e->getMessage());
            return [];
        }
    }
} 