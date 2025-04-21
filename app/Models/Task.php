<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'assignee',
        'status',
        'priority',
        'due_date',
        'related_feature',
        'related_phase',
        'progress',
        'estimated_hours',
        'actual_hours',
        'version',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
        'progress' => 'integer',
        'estimated_hours' => 'float',
        'actual_hours' => 'float',
        'notes' => 'array',
    ];

    /**
     * Get the GitHub issue associated with the task
     */
    public function githubIssue(): HasOne
    {
        return $this->hasOne(GitHubIssue::class);
    }

    /**
     * Get the tasks that this task depends on
     */
    public function dependsOn(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class, 
            'task_dependencies', 
            'task_id', 
            'dependency_id'
        );
    }

    /**
     * Get the tasks that depend on this task
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class, 
            'task_dependencies', 
            'dependency_id', 
            'task_id'
        );
    }

    /**
     * Get the tags associated with the task
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'task_tag');
    }

    /**
     * Scope a query to only include tasks with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include tasks assigned to a specific assignee.
     */
    public function scopeAssignedTo($query, $assignee)
    {
        return $query->where('assignee', $assignee);
    }

    /**
     * Scope a query to only include tasks with a specific priority.
     */
    public function scopeWithPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to only include tasks related to a specific feature.
     */
    public function scopeRelatedToFeature($query, $feature)
    {
        return $query->where('related_feature', $feature);
    }

    /**
     * Scope a query to only include tasks related to a specific phase.
     */
    public function scopeRelatedToPhase($query, $phase)
    {
        return $query->where('related_phase', $phase);
    }

    /**
     * Scope a query to only include tasks with a specific version.
     */
    public function scopeWithVersion($query, $version)
    {
        return $query->where('version', $version);
    }

    /**
     * Scope a query to only include tasks with a specific tag.
     */
    public function scopeWithTag($query, $tag)
    {
        return $query->whereHas('tags', function($q) use ($tag) {
            $q->where('name', $tag);
        });
    }

    /**
     * Add a note to the task
     */
    public function addNote($content)
    {
        $notes = $this->notes ?? [];
        $notes[] = [
            'content' => $content,
            'timestamp' => now()->toIso8601String()
        ];
        
        $this->notes = $notes;
        $this->save();
        
        return $this;
    }

    /**
     * Check if the task is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the task is overdue
     */
    public function isOverdue()
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isCompleted();
    }

    /**
     * Check if the task is due today
     */
    public function isDueToday()
    {
        return $this->due_date && $this->due_date->isToday() && !$this->isCompleted();
    }
} 