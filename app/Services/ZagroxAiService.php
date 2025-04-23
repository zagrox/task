<?php

namespace App\Services;

use App\Models\GitHubIssue;
use App\Models\Task;
use Github\Client;
use Github\Exception\RuntimeException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class ZagroxAiService
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
        if (Config::get('zagroxai.github.access_token')) {
            $this->client->authenticate(
                Config::get('zagroxai.github.access_token'),
                null,
                Client::AUTH_ACCESS_TOKEN
            );
        }
        
        // Set default repository
        $this->repository = Config::get('zagroxai.github.repository');
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
     * Assign a task to ZagroxAI
     */
    public function assignTaskToAi(int $taskId): bool
    {
        $tasksFile = base_path('project-management/tasks.json');
        
        if (!File::exists($tasksFile)) {
            Log::error("Tasks file not found when assigning to ZagroxAI: {$tasksFile}");
            return false;
        }
        
        $tasksData = json_decode(File::get($tasksFile), true);
        
        // Find the task index
        $taskIndex = null;
        foreach ($tasksData['tasks'] as $index => $task) {
            if ($task['id'] == $taskId) {
                $taskIndex = $index;
                break;
            }
        }
        
        if ($taskIndex === null) {
            Log::error("Task #{$taskId} not found when assigning to ZagroxAI");
            return false;
        }
        
        // Update the assignee to 'ai'
        $tasksData['tasks'][$taskIndex]['assignee'] = 'ai';
        
        // Add a note about the assignment
        $tasksData['tasks'][$taskIndex]['notes'][] = [
            'content' => "Task automatically assigned to ZagroxAI for processing",
            'timestamp' => Carbon::now()->toIso8601String()
        ];
        
        // Update task updated timestamp
        $tasksData['tasks'][$taskIndex]['updated_at'] = Carbon::now()->toIso8601String();
        
        // Save the updated tasks file
        File::put($tasksFile, json_encode($tasksData, JSON_PRETTY_PRINT));
        
        // Create GitHub issue if enabled
        if (Config::get('zagroxai.integration.create_github_issues', false)) {
            try {
                $this->createGitHubIssueForTask($taskId);
            } catch (\Exception $e) {
                Log::error("Failed to create GitHub issue for ZagroxAI task #{$taskId}: " . $e->getMessage());
                // Don't return false here, we still successfully assigned the task
            }
        }
        
        return true;
    }

    /**
     * Check if a task should be automatically assigned to ZagroxAI
     */
    public function shouldAutoAssignToAi(array $task): bool
    {
        $autoAssignTypes = Config::get('zagroxai.tasks.auto_assign_types', []);
        $priorityThreshold = Config::get('zagroxai.tasks.auto_assign_priority_threshold', 'medium');
        
        $priorityValues = [
            'low' => 0,
            'medium' => 1,
            'high' => 2,
            'critical' => 3
        ];
        
        $taskPriorityValue = $priorityValues[$task['priority'] ?? 'medium'] ?? 1;
        $thresholdValue = $priorityValues[$priorityThreshold] ?? 1;
        
        // Check if task type (via tags) matches auto-assign types
        $taskType = null;
        if (!empty($task['tags']) && is_array($task['tags'])) {
            foreach ($task['tags'] as $tag) {
                if (in_array($tag, $autoAssignTypes)) {
                    $taskType = $tag;
                    break;
                }
            }
        }
        
        // Auto-assign if:
        // 1. Task has one of the auto-assign types
        // 2. Task priority is below threshold (e.g., 'low' when threshold is 'medium')
        return ($taskType !== null || $taskPriorityValue < $thresholdValue);
    }

    /**
     * Create a GitHub issue for a task
     */
    public function createGitHubIssueForTask(int $taskId): ?GitHubIssue
    {
        $tasksFile = base_path('project-management/tasks.json');
        
        if (!File::exists($tasksFile)) {
            Log::error("Tasks file not found when creating GitHub issue: {$tasksFile}");
            return null;
        }
        
        $tasksData = json_decode(File::get($tasksFile), true);
        
        // Find the task
        $task = null;
        foreach ($tasksData['tasks'] as $t) {
            if ($t['id'] == $taskId) {
                $task = $t;
                break;
            }
        }
        
        if (!$task) {
            Log::error("Task #{$taskId} not found when creating GitHub issue");
            return null;
        }
        
        // Check if GitHub issue already exists for this task
        $githubIssue = GitHubIssue::where('task_id', $taskId)->first();
        
        if (!$githubIssue) {
            $githubIssue = new GitHubIssue([
                'task_id' => $taskId,
                'repository' => $this->repository
            ]);
        }
        
        try {
            $repo = $this->parseRepository($githubIssue->repository);
            
            // Prepare issue body with ZagroxAI note
            $body = $this->getIssueBodyFromTask($task);
            $body .= "\n\n> **Note**: This issue is assigned to ZagroxAI, the artificial intelligence assistant for ZAGROX projects.";
            
            // Prepare issue data
            $issueData = [
                'title' => $task['title'],
                'body' => $body,
                'labels' => $this->getLabelsFromTask($task),
                'assignees' => [Config::get('zagroxai.github.username', 'ZagroxAi')]
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
     * Get labels for a task
     */
    protected function getLabelsFromTask(array $task): array
    {
        $labels = [];
        
        // Add priority label
        if (!empty($task['priority'])) {
            $labels[] = 'priority:' . $task['priority'];
        }
        
        // Add status label
        if (!empty($task['status'])) {
            $labels[] = 'status:' . $task['status'];
        }
        
        // Add related feature label
        if (!empty($task['related_feature'])) {
            $labels[] = 'feature:' . $task['related_feature'];
        }
        
        // Add phase label
        if (!empty($task['related_phase'])) {
            $labels[] = 'phase:' . $task['related_phase'];
        }
        
        // Add tags as labels
        if (!empty($task['tags']) && is_array($task['tags'])) {
            foreach ($task['tags'] as $tag) {
                $labels[] = $tag;
            }
        }
        
        // Add ai-generated label
        if (Config::get('zagroxai.integration.auto_label', true)) {
            $labels[] = 'ai-generated';
        }
        
        return $labels;
    }

    /**
     * Get issue body for a task
     */
    protected function getIssueBodyFromTask(array $task): string
    {
        $body = $task['description'] . "\n\n";
        
        // Add metadata section
        $body .= "## Task Metadata\n\n";
        $body .= "- **ID:** " . $task['id'] . "\n";
        $body .= "- **Status:** " . $task['status'] . "\n";
        $body .= "- **Priority:** " . $task['priority'] . "\n";
        $body .= "- **Assignee:** ZagroxAI\n";
        
        if (!empty($task['due_date'])) {
            $body .= "- **Due Date:** " . $task['due_date'] . "\n";
        }
        
        if (!empty($task['progress'])) {
            $body .= "- **Progress:** " . $task['progress'] . "%\n";
        }
        
        if (!empty($task['estimated_hours'])) {
            $body .= "- **Estimated Hours:** " . $task['estimated_hours'] . "\n";
        }
        
        if (!empty($task['actual_hours'])) {
            $body .= "- **Actual Hours:** " . $task['actual_hours'] . "\n";
        }
        
        if (!empty($task['version'])) {
            $body .= "- **Version:** " . $task['version'] . "\n";
        }
        
        // Add notes section if there are notes
        if (!empty($task['notes']) && is_array($task['notes'])) {
            $body .= "\n## Notes\n\n";
            
            foreach ($task['notes'] as $note) {
                $timestamp = Carbon::parse($note['timestamp'])->format('Y-m-d H:i');
                $body .= "### " . $timestamp . "\n\n";
                $body .= $note['content'] . "\n\n";
            }
        }
        
        return $body;
    }

    /**
     * Create a pull request for a completed task
     */
    public function createPullRequestForTask(int $taskId, string $branchName, array $files): bool
    {
        if (!Config::get('zagroxai.integration.create_pull_requests', true)) {
            return false;
        }
        
        $tasksFile = base_path('project-management/tasks.json');
        
        if (!File::exists($tasksFile)) {
            Log::error("Tasks file not found when creating PR: {$tasksFile}");
            return false;
        }
        
        // Get task data
        $tasksData = json_decode(File::get($tasksFile), true);
        
        // Find the task
        $task = null;
        foreach ($tasksData['tasks'] as $t) {
            if ($t['id'] == $taskId) {
                $task = $t;
                break;
            }
        }
        
        if (!$task) {
            Log::error("Task #{$taskId} not found when creating PR");
            return false;
        }
        
        try {
            $repo = $this->parseRepository();
            
            // Create a PR
            $this->client->api('pull_request')->create(
                $repo['owner'],
                $repo['repo'],
                [
                    'title' => "Task #{$taskId}: " . $task['title'],
                    'body' => $this->getPrBodyFromTask($task, $files),
                    'head' => $branchName,
                    'base' => Config::get('zagroxai.workflow.default_branch', 'main')
                ]
            );
            
            return true;
        } catch (RuntimeException $e) {
            Log::error("GitHub API error when creating PR: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format PR body from task
     */
    protected function getPrBodyFromTask(array $task, array $files): string
    {
        $body = "## Task Information\n\n";
        $body .= "- **ID:** " . $task['id'] . "\n";
        $body .= "- **Title:** " . $task['title'] . "\n";
        $body .= "- **Priority:** " . $task['priority'] . "\n\n";
        
        $body .= "## Description\n\n" . $task['description'] . "\n\n";
        
        // Add files changed section
        if (!empty($files)) {
            $body .= "## Files Changed\n\n";
            foreach ($files as $file) {
                $body .= "- `" . $file . "`\n";
            }
            $body .= "\n";
        }
        
        // Add AI attribution
        $body .= "## Implementation Notes\n\n";
        $body .= "This pull request was created by ZagroxAI, the artificial intelligence assistant for ZAGROX projects.\n\n";
        $body .= "Please review the changes and provide feedback if necessary.\n\n";
        
        return $body;
    }

    /**
     * Process AI tasks that are pending
     */
    public function processPendingAiTasks(int $limit = 5): array
    {
        $tasksFile = base_path('project-management/tasks.json');
        
        if (!File::exists($tasksFile)) {
            Log::error("Tasks file not found when processing AI tasks: {$tasksFile}");
            return ['processed' => 0, 'errors' => 1];
        }
        
        $tasksData = json_decode(File::get($tasksFile), true);
        
        // Find pending AI tasks
        $pendingTasks = [];
        foreach ($tasksData['tasks'] as $index => $task) {
            if (($task['assignee'] ?? '') === 'ai' && ($task['status'] ?? '') === 'pending') {
                $pendingTasks[$index] = $task;
                
                // Limit the number of tasks to process
                if (count($pendingTasks) >= $limit) {
                    break;
                }
            }
        }
        
        $processed = 0;
        $errors = 0;
        
        foreach ($pendingTasks as $index => $task) {
            // Update task status to in-progress
            $tasksData['tasks'][$index]['status'] = 'in-progress';
            $tasksData['tasks'][$index]['updated_at'] = Carbon::now()->toIso8601String();
            $tasksData['tasks'][$index]['notes'][] = [
                'content' => "Task processing started by ZagroxAI",
                'timestamp' => Carbon::now()->toIso8601String()
            ];
            
            // Create or update GitHub issue
            try {
                $this->createGitHubIssueForTask($task['id']);
                $processed++;
            } catch (\Exception $e) {
                Log::error("Failed to process AI task #{$task['id']}: " . $e->getMessage());
                $errors++;
            }
        }
        
        // Save the updated tasks file
        File::put($tasksFile, json_encode($tasksData, JSON_PRETTY_PRINT));
        
        return [
            'processed' => $processed,
            'errors' => $errors,
            'total_pending' => count($pendingTasks)
        ];
    }
} 