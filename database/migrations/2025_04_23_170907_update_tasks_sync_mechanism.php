<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Task;
use App\Models\Repository;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make sure the repository_id column exists in the tasks table
        if (!Schema::hasColumn('tasks', 'repository_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->foreignId('repository_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }
        
        // Now let's perform a one-time sync of repository data with tasks
        $this->syncRepositoryData();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No drop needed since we want to keep the repository_id column
    }
    
    /**
     * Sync repository data from JSON tasks
     */
    private function syncRepositoryData(): void
    {
        $tasksFile = base_path('project-management/tasks.json');
        
        // Skip if tasks file doesn't exist
        if (!File::exists($tasksFile)) {
            return;
        }
        
        // Load tasks from JSON
        $tasksData = json_decode(File::get($tasksFile), true);
        $tasks = $tasksData['tasks'] ?? [];
        $changed = false;
        
        // Process each task
        foreach ($tasks as &$task) {
            // Find task in DB
            $dbTask = Task::find($task['id']);
            
            if (!$dbTask) {
                continue;
            }
            
            // Set repository_id from DB task to JSON task if exists
            if (!empty($dbTask->repository_id) && 
                (empty($task['repository_id']) || $task['repository_id'] != $dbTask->repository_id)) {
                $task['repository_id'] = $dbTask->repository_id;
                $changed = true;
            }
            
            // Set repository_id from JSON task to DB task if exists
            if (!empty($task['repository_id']) && $dbTask->repository_id != $task['repository_id']) {
                $dbTask->repository_id = $task['repository_id'];
                $dbTask->save();
            }
            
            // Add repo: tag if task has repository_id
            if (!empty($task['repository_id'])) {
                $repository = Repository::find($task['repository_id']);
                
                if ($repository) {
                    $repoTag = 'repo:' . $repository->name;
                    
                    // Add repo tag if not exists
                    if (empty($task['tags'])) {
                        $task['tags'] = [$repoTag];
                        $changed = true;
                    } else if (!in_array($repoTag, $task['tags'])) {
                        $task['tags'][] = $repoTag;
                        $changed = true;
                    }
                }
            }
        }
        
        // Save changes back to JSON file if needed
        if ($changed) {
            $tasksData['tasks'] = $tasks;
            File::put($tasksFile, json_encode($tasksData, JSON_PRETTY_PRINT));
        }
    }
};
