<?php

namespace TaskApp\TaskManager\Models;

use Illuminate\Database\Eloquent\Model;

class SyncQueue extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'task_sync_queue';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'operation', 
        'entity_type', 
        'entity_id', 
        'data', 
        'status', 
        'attempts', 
        'last_error', 
        'synced_at'
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'attempts' => 'integer',
        'synced_at' => 'datetime',
    ];
    
    /**
     * Get sync operations that need to be processed.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPending($limit = 50)
    {
        return self::where('status', 'pending')
            ->where('attempts', '<', 3)
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Mark a sync operation as completed.
     *
     * @return bool
     */
    public function markCompleted()
    {
        $this->status = 'completed';
        $this->synced_at = now();
        return $this->save();
    }
    
    /**
     * Mark a sync operation as failed.
     *
     * @param string $error
     * @return bool
     */
    public function markFailed($error = null)
    {
        $this->attempts += 1;
        
        if ($error) {
            $this->last_error = $error;
        }
        
        if ($this->attempts >= 3) {
            $this->status = 'failed';
        }
        
        return $this->save();
    }
    
    /**
     * Reset failed sync operations to try again.
     *
     * @return int
     */
    public static function resetFailed()
    {
        return self::where('status', 'failed')
            ->update([
                'status' => 'pending',
                'attempts' => 0,
                'last_error' => null
            ]);
    }
    
    /**
     * Clean up old completed sync operations.
     *
     * @param int $days
     * @return int
     */
    public static function cleanupOld($days = 7)
    {
        return self::where('status', 'completed')
            ->where('synced_at', '<', now()->subDays($days))
            ->delete();
    }
} 