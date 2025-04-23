<?php

namespace TaskManager\Providers\GitHub;

use TaskManager\Contracts\SyncProviderInterface;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class GitHubSyncProvider implements SyncProviderInterface
{
    /**
     * GitHub API client
     *
     * @var Client
     */
    protected $client;
    
    /**
     * GitHub repository owner
     *
     * @var string
     */
    protected $owner;
    
    /**
     * GitHub repository name
     *
     * @var string
     */
    protected $repo;
    
    /**
     * GitHub API token
     *
     * @var string
     */
    protected $token;
    
    /**
     * Create a new GitHub sync provider instance
     */
    public function __construct()
    {
        $this->token = Config::get('taskmanager.github.token');
        $this->owner = Config::get('taskmanager.github.owner');
        $this->repo = Config::get('taskmanager.github.repository');
        
        $this->client = new Client([
            'base_uri' => 'https://api.github.com/',
            'headers' => [
                'Authorization' => 'token ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'TaskManager'
            ]
        ]);
    }
    
    /**
     * Get the name of the provider
     *
     * @return string
     */
    public function getName()
    {
        return 'GitHub';
    }
    
    /**
     * Get tasks from GitHub issues
     *
     * @return array
     */
    public function getTasks()
    {
        if (!$this->isConfigured()) {
            return [];
        }
        
        try {
            $response = $this->client->get("repos/{$this->owner}/{$this->repo}/issues", [
                'query' => ['state' => 'all']
            ]);
            
            $issues = json_decode($response->getBody()->getContents(), true);
            
            return array_map(function ($issue) {
                return $this->mapIssueToTask($issue);
            }, $issues);
        } catch (\Exception $e) {
            Log::error('Failed to get GitHub issues: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create a new task in GitHub as an issue
     *
     * @param array $task Task data
     * @return string|int External ID of the created issue
     */
    public function createTask(array $task)
    {
        if (!$this->isConfigured()) {
            return null;
        }
        
        try {
            $response = $this->client->post("repos/{$this->owner}/{$this->repo}/issues", [
                'json' => [
                    'title' => $task['title'],
                    'body' => $this->formatTaskBody($task),
                    'labels' => $this->getLabelsFromTask($task)
                ]
            ]);
            
            $issue = json_decode($response->getBody()->getContents(), true);
            return $issue['number'];
        } catch (\Exception $e) {
            Log::error('Failed to create GitHub issue: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update an existing task in GitHub
     *
     * @param string|int $externalId ID of the issue in GitHub
     * @param array $task Updated task data
     * @return bool
     */
    public function updateTask($externalId, array $task)
    {
        if (!$this->isConfigured()) {
            return false;
        }
        
        try {
            $this->client->patch("repos/{$this->owner}/{$this->repo}/issues/{$externalId}", [
                'json' => [
                    'title' => $task['title'],
                    'body' => $this->formatTaskBody($task),
                    'state' => $this->mapStatusToState($task['status'] ?? 'pending'),
                    'labels' => $this->getLabelsFromTask($task)
                ]
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to update GitHub issue #{$externalId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a task in GitHub (close the issue)
     *
     * @param string|int $externalId ID of the issue in GitHub
     * @return bool
     */
    public function deleteTask($externalId)
    {
        if (!$this->isConfigured()) {
            return false;
        }
        
        try {
            $this->client->patch("repos/{$this->owner}/{$this->repo}/issues/{$externalId}", [
                'json' => [
                    'state' => 'closed'
                ]
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to close GitHub issue #{$externalId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if the provider is properly configured
     *
     * @return bool
     */
    public function isConfigured()
    {
        return !empty($this->token) && !empty($this->owner) && !empty($this->repo);
    }
    
    /**
     * Map a GitHub issue to a task array
     *
     * @param array $issue
     * @return array
     */
    protected function mapIssueToTask(array $issue)
    {
        $priority = 'medium';
        $relatedFeature = null;
        $estimatedHours = null;
        $version = null;
        
        // Extract task metadata from issue body
        $metadata = $this->extractMetadataFromBody($issue['body'] ?? '');
        
        // Extract metadata from labels
        foreach ($issue['labels'] ?? [] as $label) {
            $name = $label['name'];
            
            if (strpos($name, 'priority:') === 0) {
                $priority = str_replace('priority:', '', $name);
            } elseif (strpos($name, 'feature:') === 0) {
                $relatedFeature = str_replace('feature:', '', $name);
            } elseif (strpos($name, 'version:') === 0) {
                $version = str_replace('version:', '', $name);
            }
        }
        
        return [
            'id' => $issue['number'],
            'title' => $issue['title'],
            'description' => $metadata['description'] ?? '',
            'status' => $this->mapStateToStatus($issue['state'], $metadata['status'] ?? null),
            'assignee' => $issue['assignee']['login'] ?? 'unassigned',
            'priority' => $priority,
            'estimated_hours' => $metadata['estimated_hours'] ?? null,
            'actual_hours' => $metadata['actual_hours'] ?? null,
            'related_feature' => $relatedFeature,
            'version' => $version,
            'notes' => $metadata['notes'] ?? [],
            'external_id' => $issue['number'],
            'external_url' => $issue['html_url'],
            'created_at' => $issue['created_at'],
            'updated_at' => $issue['updated_at'],
        ];
    }
    
    /**
     * Extract metadata from the issue body
     *
     * @param string $body
     * @return array
     */
    protected function extractMetadataFromBody(string $body)
    {
        $metadata = [
            'description' => $body,
            'status' => null,
            'estimated_hours' => null,
            'actual_hours' => null,
            'notes' => [],
        ];
        
        // Extract metadata that might be in the format:
        // <!-- TASK_METADATA: {"key": "value"} -->
        preg_match('/<!-- TASK_METADATA: (.*?) -->/s', $body, $matches);
        if (!empty($matches[1])) {
            $jsonData = json_decode($matches[1], true);
            if (is_array($jsonData)) {
                $metadata = array_merge($metadata, $jsonData);
                // Remove the metadata section from description
                $metadata['description'] = str_replace($matches[0], '', $body);
            }
        }
        
        return $metadata;
    }
    
    /**
     * Format a task body for GitHub issues
     *
     * @param array $task
     * @return string
     */
    protected function formatTaskBody(array $task)
    {
        $metadata = [
            'status' => $task['status'] ?? 'pending',
            'estimated_hours' => $task['estimated_hours'] ?? null,
            'actual_hours' => $task['actual_hours'] ?? null,
            'notes' => $task['notes'] ?? []
        ];
        
        $description = $task['description'] ?? '';
        
        // Add task metadata as hidden comment
        $body = $description . "\n\n<!-- TASK_METADATA: " . json_encode($metadata) . " -->";
        
        return $body;
    }
    
    /**
     * Get GitHub labels from task data
     *
     * @param array $task
     * @return array
     */
    protected function getLabelsFromTask(array $task)
    {
        $labels = [];
        
        // Add priority label
        if (!empty($task['priority'])) {
            $labels[] = 'priority:' . $task['priority'];
        }
        
        // Add status label if not pending/completed (those map to open/closed state)
        if (!empty($task['status']) && !in_array($task['status'], ['pending', 'completed'])) {
            $labels[] = 'status:' . $task['status'];
        }
        
        // Add feature label
        if (!empty($task['related_feature'])) {
            $labels[] = 'feature:' . $task['related_feature'];
        }
        
        // Add version label
        if (!empty($task['version'])) {
            $labels[] = 'version:' . $task['version'];
        }
        
        // Add tags as labels
        if (!empty($task['tags']) && is_array($task['tags'])) {
            foreach ($task['tags'] as $tag) {
                $labels[] = 'tag:' . $tag;
            }
        }
        
        return $labels;
    }
    
    /**
     * Map GitHub issue state to task status
     *
     * @param string $state GitHub state (open, closed)
     * @param string|null $statusOverride Status override from metadata
     * @return string
     */
    protected function mapStateToStatus(string $state, ?string $statusOverride = null)
    {
        if ($statusOverride && in_array($statusOverride, ['pending', 'in-progress', 'blocked', 'review', 'completed'])) {
            return $statusOverride;
        }
        
        return $state === 'open' ? 'pending' : 'completed';
    }
    
    /**
     * Map task status to GitHub issue state
     *
     * @param string $status Task status
     * @return string
     */
    protected function mapStatusToState(string $status)
    {
        return $status === 'completed' ? 'closed' : 'open';
    }
} 