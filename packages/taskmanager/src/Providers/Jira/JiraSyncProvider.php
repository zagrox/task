<?php

namespace TaskManager\Providers\Jira;

use TaskManager\Contracts\SyncProviderInterface;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class JiraSyncProvider implements SyncProviderInterface
{
    /**
     * Jira API client
     *
     * @var Client
     */
    protected $client;
    
    /**
     * Jira domain
     *
     * @var string
     */
    protected $domain;
    
    /**
     * Jira project key
     *
     * @var string
     */
    protected $projectKey;
    
    /**
     * Jira username/email
     *
     * @var string
     */
    protected $username;
    
    /**
     * Jira API token
     *
     * @var string
     */
    protected $token;
    
    /**
     * Create a new Jira sync provider instance
     */
    public function __construct()
    {
        $this->domain = Config::get('taskmanager.jira.domain');
        $this->projectKey = Config::get('taskmanager.jira.project_key');
        $this->username = Config::get('taskmanager.jira.username');
        $this->token = Config::get('taskmanager.jira.token');
        
        if ($this->isConfigured()) {
            $this->client = new Client([
                'base_uri' => "https://{$this->domain}.atlassian.net/rest/api/3/",
                'auth' => [$this->username, $this->token],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ]
            ]);
        }
    }
    
    /**
     * Get the name of the provider
     *
     * @return string
     */
    public function getName()
    {
        return 'Jira';
    }
    
    /**
     * Get tasks from Jira issues
     *
     * @return array
     */
    public function getTasks()
    {
        if (!$this->isConfigured()) {
            return [];
        }
        
        try {
            $jql = "project = {$this->projectKey} ORDER BY updated DESC";
            
            $response = $this->client->get('search', [
                'query' => [
                    'jql' => $jql,
                    'maxResults' => 100
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            $issues = $result['issues'] ?? [];
            
            return array_map(function ($issue) {
                return $this->mapIssueToTask($issue);
            }, $issues);
        } catch (\Exception $e) {
            Log::error('Failed to get Jira issues: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create a new task in Jira as an issue
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
            $issueData = [
                'fields' => [
                    'project' => [
                        'key' => $this->projectKey
                    ],
                    'summary' => $task['title'],
                    'description' => [
                        'type' => 'doc',
                        'version' => 1,
                        'content' => [
                            [
                                'type' => 'paragraph',
                                'content' => [
                                    [
                                        'type' => 'text',
                                        'text' => $task['description'] ?? ''
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'issuetype' => [
                        'name' => 'Task'
                    ],
                    'labels' => $this->getLabelsFromTask($task)
                ]
            ];
            
            // Add custom fields based on task data
            $this->addCustomFieldsToIssueData($issueData, $task);
            
            $response = $this->client->post('issue', [
                'json' => $issueData
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            return $result['key'] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to create Jira issue: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update an existing task in Jira
     *
     * @param string|int $externalId ID of the issue in Jira
     * @param array $task Updated task data
     * @return bool
     */
    public function updateTask($externalId, array $task)
    {
        if (!$this->isConfigured()) {
            return false;
        }
        
        try {
            $issueData = [
                'fields' => [
                    'summary' => $task['title'],
                    'description' => [
                        'type' => 'doc',
                        'version' => 1,
                        'content' => [
                            [
                                'type' => 'paragraph',
                                'content' => [
                                    [
                                        'type' => 'text',
                                        'text' => $task['description'] ?? ''
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'labels' => $this->getLabelsFromTask($task)
                ]
            ];
            
            // Add custom fields based on task data
            $this->addCustomFieldsToIssueData($issueData, $task);
            
            $this->client->put("issue/{$externalId}", [
                'json' => $issueData
            ]);
            
            // Update status if necessary
            if (!empty($task['status'])) {
                $this->updateIssueStatus($externalId, $task['status']);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to update Jira issue {$externalId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a task in Jira (close the issue)
     *
     * @param string|int $externalId ID of the issue in Jira
     * @return bool
     */
    public function deleteTask($externalId)
    {
        if (!$this->isConfigured()) {
            return false;
        }
        
        try {
            $this->client->delete("issue/{$externalId}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete Jira issue {$externalId}: " . $e->getMessage());
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
        return !empty($this->domain) && !empty($this->projectKey) && 
               !empty($this->username) && !empty($this->token);
    }
    
    /**
     * Map a Jira issue to a task array
     *
     * @param array $issue
     * @return array
     */
    protected function mapIssueToTask(array $issue)
    {
        $fields = $issue['fields'];
        
        $status = $this->mapJiraStatusToTaskStatus($fields['status']['name'] ?? '');
        $priority = $this->mapJiraPriorityToTaskPriority($fields['priority']['name'] ?? '');
        
        // Extract version from fixVersions if available
        $version = null;
        if (!empty($fields['fixVersions'])) {
            $version = $fields['fixVersions'][0]['name'] ?? null;
        }
        
        // Extract related feature from component if available
        $relatedFeature = null;
        if (!empty($fields['components'])) {
            $relatedFeature = $fields['components'][0]['name'] ?? null;
        }
        
        // Extract description from Jira's complex content structure
        $description = '';
        if (isset($fields['description']) && is_array($fields['description'])) {
            $description = $this->extractTextFromJiraContent($fields['description']);
        } elseif (isset($fields['description']) && is_string($fields['description'])) {
            $description = $fields['description'];
        }
        
        return [
            'id' => $issue['id'],
            'title' => $fields['summary'] ?? '',
            'description' => $description,
            'status' => $status,
            'priority' => $priority,
            'assignee' => isset($fields['assignee']['displayName']) ? $fields['assignee']['displayName'] : 'unassigned',
            'version' => $version,
            'related_feature' => $relatedFeature,
            'estimated_hours' => $this->getEstimatedHours($fields),
            'actual_hours' => $this->getLoggedHours($fields),
            'notes' => $this->extractNotesFromIssue($issue),
            'tags' => $fields['labels'] ?? [],
            'external_id' => $issue['key'],
            'external_url' => "https://{$this->domain}.atlassian.net/browse/{$issue['key']}",
            'created_at' => $fields['created'] ?? null,
            'updated_at' => $fields['updated'] ?? null,
        ];
    }
    
    /**
     * Extract text from Jira's content structure
     *
     * @param array $content
     * @return string
     */
    protected function extractTextFromJiraContent($content)
    {
        // Handle Jira Cloud's Atlassian Document Format (ADF)
        if (isset($content['content'])) {
            $text = '';
            foreach ($content['content'] as $block) {
                if ($block['type'] === 'paragraph' && isset($block['content'])) {
                    foreach ($block['content'] as $item) {
                        if ($item['type'] === 'text') {
                            $text .= $item['text'] . "\n";
                        }
                    }
                }
            }
            return trim($text);
        }
        
        return '';
    }
    
    /**
     * Map Jira status to task status
     *
     * @param string $status
     * @return string
     */
    protected function mapJiraStatusToTaskStatus($status)
    {
        $statusMap = [
            'To Do' => 'pending',
            'In Progress' => 'in-progress',
            'In Review' => 'review',
            'Done' => 'completed',
            'Blocked' => 'blocked'
        ];
        
        return $statusMap[trim($status)] ?? 'pending';
    }
    
    /**
     * Map Jira priority to task priority
     *
     * @param string $priority
     * @return string
     */
    protected function mapJiraPriorityToTaskPriority($priority)
    {
        $priorityMap = [
            'Highest' => 'critical',
            'High' => 'high',
            'Medium' => 'medium',
            'Low' => 'low',
            'Lowest' => 'low'
        ];
        
        return $priorityMap[trim($priority)] ?? 'medium';
    }
    
    /**
     * Map task status to Jira transition
     *
     * @param string $status
     * @return string|null
     */
    protected function mapTaskStatusToJiraTransition($status)
    {
        $transitionMap = [
            'pending' => 'To Do',
            'in-progress' => 'In Progress',
            'review' => 'In Review',
            'completed' => 'Done',
            'blocked' => 'Blocked'
        ];
        
        return $transitionMap[$status] ?? null;
    }
    
    /**
     * Update issue status via transitions
     *
     * @param string $issueKey
     * @param string $status
     * @return bool
     */
    protected function updateIssueStatus($issueKey, $status)
    {
        try {
            // Get available transitions
            $response = $this->client->get("issue/{$issueKey}/transitions");
            $transitions = json_decode($response->getBody()->getContents(), true)['transitions'] ?? [];
            
            $targetTransitionName = $this->mapTaskStatusToJiraTransition($status);
            if (!$targetTransitionName) {
                return false;
            }
            
            // Find the transition ID that matches the target name
            $transitionId = null;
            foreach ($transitions as $transition) {
                if ($transition['name'] === $targetTransitionName) {
                    $transitionId = $transition['id'];
                    break;
                }
            }
            
            if (!$transitionId) {
                Log::warning("Could not find transition for status '{$status}' in Jira issue {$issueKey}");
                return false;
            }
            
            // Perform the transition
            $this->client->post("issue/{$issueKey}/transitions", [
                'json' => [
                    'transition' => [
                        'id' => $transitionId
                    ]
                ]
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to update status for Jira issue {$issueKey}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get estimated hours from Jira fields
     *
     * @param array $fields
     * @return float|null
     */
    protected function getEstimatedHours($fields)
    {
        // Try to get from various possible custom fields
        // This would need to be customized based on your Jira setup
        $originalEstimate = $fields['timeoriginalestimate'] ?? null;
        
        if ($originalEstimate) {
            // Convert from seconds to hours
            return round($originalEstimate / 3600, 2);
        }
        
        return null;
    }
    
    /**
     * Get logged hours from Jira fields
     *
     * @param array $fields
     * @return float|null
     */
    protected function getLoggedHours($fields)
    {
        // Try to get from various possible custom fields
        $timeSpent = $fields['timespent'] ?? null;
        
        if ($timeSpent) {
            // Convert from seconds to hours
            return round($timeSpent / 3600, 2);
        }
        
        return null;
    }
    
    /**
     * Extract notes from Jira issue
     *
     * @param array $issue
     * @return array
     */
    protected function extractNotesFromIssue($issue)
    {
        $notes = [];
        
        // Check for comments
        if (isset($issue['fields']['comment']['comments'])) {
            foreach ($issue['fields']['comment']['comments'] as $comment) {
                // Extract text from comment body (could be in ADF format)
                $text = '';
                if (is_array($comment['body'])) {
                    $text = $this->extractTextFromJiraContent($comment['body']);
                } else {
                    $text = $comment['body'];
                }
                
                $notes[] = [
                    'text' => $text,
                    'author' => $comment['author']['displayName'] ?? 'Unknown',
                    'date' => $comment['created'] ?? null
                ];
            }
        }
        
        return $notes;
    }
    
    /**
     * Get labels from task data for Jira
     *
     * @param array $task
     * @return array
     */
    protected function getLabelsFromTask(array $task)
    {
        $labels = [];
        
        // Add version as label if exists
        if (!empty($task['version'])) {
            $labels[] = 'version-' . $task['version'];
        }
        
        // Add priority as label
        if (!empty($task['priority'])) {
            $labels[] = 'priority-' . $task['priority'];
        }
        
        // Add feature as label
        if (!empty($task['related_feature'])) {
            $labels[] = 'feature-' . $task['related_feature'];
        }
        
        // Add tags if they exist
        if (!empty($task['tags']) && is_array($task['tags'])) {
            $labels = array_merge($labels, $task['tags']);
        }
        
        return $labels;
    }
    
    /**
     * Add custom fields to Jira issue data based on task properties
     *
     * @param array &$issueData
     * @param array $task
     */
    protected function addCustomFieldsToIssueData(&$issueData, $task)
    {
        // Set priority if available
        if (!empty($task['priority'])) {
            $priorityMap = [
                'critical' => 'Highest',
                'high' => 'High',
                'medium' => 'Medium',
                'low' => 'Low'
            ];
            
            $jiraPriority = $priorityMap[$task['priority']] ?? 'Medium';
            $issueData['fields']['priority'] = ['name' => $jiraPriority];
        }
        
        // Set component (for related feature) if available
        if (!empty($task['related_feature'])) {
            $issueData['fields']['components'] = [
                ['name' => $task['related_feature']]
            ];
        }
        
        // Set fix version if available
        if (!empty($task['version'])) {
            $issueData['fields']['fixVersions'] = [
                ['name' => $task['version']]
            ];
        }
        
        // Set estimated time if available (in seconds)
        if (!empty($task['estimated_hours'])) {
            $issueData['fields']['timeoriginalestimate'] = (int)($task['estimated_hours'] * 3600);
        }
        
        // Note: You may need to customize this method with your specific Jira custom fields
        // Example for a custom field:
        // $issueData['fields']['customfield_10001'] = $task['some_property'];
    }
} 