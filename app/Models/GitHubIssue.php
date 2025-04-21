<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class GitHubIssue extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'github_issues';

    protected $fillable = [
        'task_id',
        'repository',
        'issue_number',
        'issue_url',
        'issue_state',
        'last_synced_at'
    ];

    protected $casts = [
        'task_id' => 'integer',
        'issue_number' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the task associated with this GitHub issue from the tasks.json file
     */
    public function getTask()
    {
        $tasksFile = base_path('project-management/tasks.json');
        
        if (!File::exists($tasksFile)) {
            return null;
        }
        
        $taskData = json_decode(File::get($tasksFile), true);
        
        foreach ($taskData['tasks'] as $task) {
            if ($task['id'] == $this->task_id) {
                return $task;
            }
        }
        
        return null;
    }

    /**
     * Check if the task associated with this GitHub issue exists
     */
    public function taskExists()
    {
        return $this->getTask() !== null;
    }

    /**
     * Get the labels for the GitHub issue based on task data
     */
    public function getLabelsFromTask()
    {
        $task = $this->getTask();
        
        if (!$task) {
            return [];
        }
        
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
        
        // Add tags as labels
        if (!empty($task['tags']) && is_array($task['tags'])) {
            foreach ($task['tags'] as $tag) {
                $labels[] = $tag;
            }
        }
        
        return $labels;
    }

    /**
     * Get the issue body content based on task data
     */
    public function getIssueBodyFromTask()
    {
        $task = $this->getTask();
        
        if (!$task) {
            return '';
        }
        
        $body = $task['description'] . "\n\n";
        
        // Add metadata section
        $body .= "## Task Metadata\n\n";
        $body .= "- **ID:** " . $task['id'] . "\n";
        $body .= "- **Status:** " . $task['status'] . "\n";
        $body .= "- **Priority:** " . $task['priority'] . "\n";
        $body .= "- **Assignee:** user\n";
        
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
        
        // Add footer with sync information
        $body .= "\n---\n";
        $body .= "_This issue is synchronized with the Task Manager. Changes made in GitHub will be reflected in the Task Manager._";
        
        return $body;
    }
}
