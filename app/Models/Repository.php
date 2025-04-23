<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Repository extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'color',
        'github_repo',
    ];

    /**
     * Get the tasks for the repository.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the completion percentage for tasks in this repository.
     */
    public function getCompletionPercentage()
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) {
            return 0;
        }

        $completedTasks = $this->tasks()->where('status', 'completed')->count();
        return round(($completedTasks / $totalTasks) * 100);
    }

    /**
     * Get the color with a default if not set.
     */
    public function getColorAttribute($value)
    {
        return $value ?: '#6c757d';
    }

    /**
     * Parse the GitHub repository string into owner and repo name.
     *
     * @return array|null
     */
    public function getGitHubRepoDetails()
    {
        if (!$this->github_repo) {
            return null;
        }

        $parts = explode('/', $this->github_repo);
        if (count($parts) === 2) {
            return [
                'owner' => $parts[0],
                'repo' => $parts[1],
            ];
        }
        
        return null;
    }

    /**
     * Generate a random color if none is provided.
     */
    protected static function booted()
    {
        static::creating(function ($repository) {
            if (empty($repository->color)) {
                $repository->color = '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
            }
        });
    }
} 