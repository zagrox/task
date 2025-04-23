<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'assigned_to',
        'created_by',
        'feature',
        'phase',
        'version',
        'github_issue_id',
        'github_issue_number',
        'github_issue_url',
        'repository_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'estimated_hours' => 'float',
        'actual_hours' => 'float',
    ];
    
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in-progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_REVIEW = 'review';
    
    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';
    
    // Relationships
    
    /**
     * Get the GitHub issue associated with this task
     */
    public function githubIssue()
    {
        return $this->hasOne(GitHubIssue::class);
    }
    
    /**
     * Get the tags for this task
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
    
    /**
     * Get the tasks that depend on this task
     */
    public function dependents()
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'dependency_id', 'task_id')
            ->withTimestamps();
    }
    
    /**
     * Get the tasks that this task depends on
     */
    public function dependencies()
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'dependency_id')
            ->withTimestamps();
    }
    
    /**
     * Get the repository that owns the task
     */
    public function repository()
    {
        return $this->belongsTo(Repository::class);
    }
    
    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
    
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }
    
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
    
    public function scopeBlocked($query)
    {
        return $query->where('status', self::STATUS_BLOCKED);
    }
    
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED]);
    }
    
    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED]);
    }
    
    public function scopeAssignedToAi($query)
    {
        return $query->where('assigned_to', 'ai');
    }
    
    public function scopeAssignedToUser($query)
    {
        return $query->where('assigned_to', '!=', 'ai');
    }
    
    // Helpers
    public function isOverdue()
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== self::STATUS_COMPLETED;
    }
    
    public function isDueToday()
    {
        return $this->due_date && $this->due_date->isToday();
    }
    
    public function isAssignedToAi()
    {
        return $this->assigned_to === 'ai';
    }
} 