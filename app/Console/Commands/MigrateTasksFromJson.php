<?php

namespace App\Console\Commands;

use App\Models\Tag;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateTasksFromJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:migrate-from-json';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate tasks from the JSON file to the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jsonPath = base_path('project-management/tasks.json');
        
        if (!File::exists($jsonPath)) {
            $this->error("Tasks JSON file not found at: $jsonPath");
            return 1;
        }

        $tasksData = json_decode(File::get($jsonPath), true);
        
        if (!isset($tasksData['tasks']) || !is_array($tasksData['tasks'])) {
            $this->error("Invalid tasks JSON format");
            return 1;
        }

        $tagMap = $this->processTagsFromTasks($tasksData['tasks']);
        $taskData = $this->processTasksFromJson($tasksData['tasks']);
        $this->processDependenciesFromJson($tasksData['tasks'], $taskData);

        $this->info("Migration completed successfully!");
        $this->info("Migrated " . count($taskData['taskMap']) . " tasks and " . count($tagMap) . " tags.");
        
        return 0;
    }

    /**
     * Extract and create tags from tasks
     */
    private function processTagsFromTasks(array $tasks): array
    {
        $this->info("Processing tags...");
        $progressBar = $this->output->createProgressBar(count($tasks));
        $progressBar->start();

        $allTags = [];
        $tagMap = [];

        // Collect all unique tags from tasks
        foreach ($tasks as $taskData) {
            if (isset($taskData['tags']) && is_array($taskData['tags'])) {
                foreach ($taskData['tags'] as $tag) {
                    $allTags[$tag] = true;
                }
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        
        // Create tags in the database
        $this->info("Creating " . count($allTags) . " tags...");
        foreach (array_keys($allTags) as $tagName) {
            $tag = Tag::firstOrCreate([
                'name' => $tagName
            ]);
            $tagMap[$tagName] = $tag->id;
        }

        return $tagMap;
    }

    /**
     * Process tasks from JSON
     */
    private function processTasksFromJson(array $tasks): array
    {
        $this->info("Processing tasks...");
        $progressBar = $this->output->createProgressBar(count($tasks));
        $progressBar->start();

        $taskMap = [];
        $originalIdMap = []; // Map original IDs to new IDs
        $normalizedStatuses = [];
        $normalizedAssignees = [];

        foreach ($tasks as $taskData) {
            $originalId = $taskData['id'];
            
            // Normalize status - ensure it's one of the allowed enum values
            $originalStatus = $taskData['status'] ?? 'pending';
            $status = $this->normalizeStatus($originalStatus);
            if ($originalStatus !== $status) {
                $normalizedStatuses[] = ["id" => $originalId, "from" => $originalStatus, "to" => $status];
            }
            
            // Normalize assignee
            $originalAssignee = $taskData['assignee'] ?? 'user';
            $assignee = $this->normalizeAssignee($originalAssignee);
            if ($originalAssignee !== $assignee) {
                $normalizedAssignees[] = ["id" => $originalId, "from" => $originalAssignee, "to" => $assignee];
            }
            
            // Normalize priority
            $priority = $this->normalizePriority($taskData['priority'] ?? 'medium');
            
            $task = new Task();
            // Don't set ID - let the database auto-increment handle it
            $task->title = $taskData['title'];
            $task->description = $taskData['description'] ?? '';
            $task->assignee = $assignee;
            $task->status = $status;
            $task->priority = $priority;
            $task->due_date = isset($taskData['due_date']) ? $taskData['due_date'] : null;
            $task->related_feature = $taskData['related_feature'] ?? null;
            $task->related_phase = $taskData['related_phase'] ?? null;
            $task->progress = $taskData['progress'] ?? 0;
            $task->estimated_hours = $taskData['estimated_hours'] ?? 0;
            $task->actual_hours = $taskData['actual_hours'] ?? 0;
            $task->version = $taskData['version'] ?? null;
            $task->notes = $taskData['notes'] ?? [];
            $task->save();

            // Associate tags
            if (isset($taskData['tags']) && is_array($taskData['tags'])) {
                $tagIds = [];
                foreach ($taskData['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => $tagName]);
                    $tagIds[] = $tag->id;
                }
                $task->tags()->sync($tagIds);
            }

            // Store both the original ID and the new task object
            $taskMap[$task->id] = $task;
            $originalIdMap[$originalId] = $task->id;
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Created tasks with the following ID mappings:");
        foreach ($originalIdMap as $oldId => $newId) {
            $this->line("  Original ID: $oldId → New ID: $newId");
        }
        
        if (!empty($normalizedStatuses)) {
            $this->warn("Normalized status values:");
            foreach ($normalizedStatuses as $norm) {
                $this->line("  Task ID: {$norm['id']} - Changed from '{$norm['from']}' to '{$norm['to']}'");
            }
        }
        
        if (!empty($normalizedAssignees)) {
            $this->warn("Normalized assignee values:");
            foreach ($normalizedAssignees as $norm) {
                $this->line("  Task ID: {$norm['id']} - Changed from '{$norm['from']}' to '{$norm['to']}'");
            }
        }

        return [
            'taskMap' => $taskMap,
            'originalIdMap' => $originalIdMap
        ];
    }

    /**
     * Normalize status to one of the allowed enum values
     */
    private function normalizeStatus(string $status): string
    {
        $allowedStatuses = ['pending', 'in-progress', 'completed', 'blocked', 'review'];
        
        if (in_array($status, $allowedStatuses)) {
            return $status;
        }
        
        // Map non-standard statuses to standard ones
        $statusMap = [
            'system' => 'completed',
            'done' => 'completed',
            'open' => 'in-progress',
            'new' => 'pending',
            'active' => 'in-progress',
            'on-hold' => 'blocked'
        ];
        
        return $statusMap[strtolower($status)] ?? 'pending';
    }
    
    /**
     * Normalize priority to one of the allowed enum values
     */
    private function normalizePriority(string $priority): string
    {
        $allowedPriorities = ['low', 'medium', 'high', 'critical'];
        
        if (in_array($priority, $allowedPriorities)) {
            return $priority;
        }
        
        // Map non-standard priorities to standard ones
        $priorityMap = [
            'normal' => 'medium',
            'urgent' => 'high',
            'highest' => 'critical',
            'lowest' => 'low'
        ];
        
        return $priorityMap[strtolower($priority)] ?? 'medium';
    }

    /**
     * Normalize assignee to one of the allowed enum values
     */
    private function normalizeAssignee(string $assignee): string
    {
        $allowedAssignees = ['user', 'ai'];
        
        if (in_array($assignee, $allowedAssignees)) {
            return $assignee;
        }
        
        // Map non-standard assignees to standard ones
        $assigneeMap = [
            'system' => 'ai',
            'auto' => 'ai',
            'admin' => 'user',
            'developer' => 'user'
        ];
        
        return $assigneeMap[strtolower($assignee)] ?? 'user';
    }

    /**
     * Process dependencies from JSON
     */
    private function processDependenciesFromJson(array $tasks, array $taskData): void
    {
        $taskMap = $taskData['taskMap'];
        $originalIdMap = $taskData['originalIdMap'];
        
        $this->info("Processing dependencies...");
        $progressBar = $this->output->createProgressBar(count($tasks));
        $progressBar->start();

        foreach ($tasks as $taskData) {
            $originalId = $taskData['id'];
            
            // Skip if no dependencies or task not found
            if (!isset($taskData['dependencies']) || !is_array($taskData['dependencies']) || !isset($originalIdMap[$originalId])) {
                $progressBar->advance();
                continue;
            }

            $newTaskId = $originalIdMap[$originalId];
            $task = $taskMap[$newTaskId];
            $dependencyIds = [];

            foreach ($taskData['dependencies'] as $originalDependencyId) {
                if (isset($originalIdMap[$originalDependencyId])) {
                    $newDependencyId = $originalIdMap[$originalDependencyId];
                    $dependencyIds[] = $newDependencyId;
                }
            }

            if (!empty($dependencyIds)) {
                $task->dependsOn()->sync($dependencyIds);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }
} 