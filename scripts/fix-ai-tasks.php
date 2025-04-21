<?php
/**
 * Script to fix AI task IDs, titles, and assignees in the tasks.json file
 */

$tasksFile = __DIR__ . '/../project-management/tasks.json';

if (!file_exists($tasksFile)) {
    echo "Error: tasks.json file not found at $tasksFile\n";
    exit(1);
}

// Create a backup
$backupFile = __DIR__ . '/../project-management/tasks.json.bak-' . date('YmdHis');
if (!copy($tasksFile, $backupFile)) {
    echo "Error: Could not create backup file\n";
    exit(1);
}

echo "Created backup at $backupFile\n";

// Read tasks.json
$taskData = json_decode(file_get_contents($tasksFile), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Error: Invalid JSON in tasks.json\n";
    exit(1);
}

$modifiedTasks = 0;
$nextId = 1;

// Find highest numeric ID
foreach ($taskData['tasks'] as $task) {
    if (is_numeric($task['id']) && $task['id'] >= $nextId) {
        $nextId = $task['id'] + 1;
    }
}

// Process tasks
foreach ($taskData['tasks'] as $key => $task) {
    $modified = false;
    
    // Fix task ID if it has an 'ai_' prefix
    if (is_string($task['id']) && strpos($task['id'], 'ai_') === 0) {
        $taskData['tasks'][$key]['id'] = $nextId;
        $nextId++;
        $modified = true;
        echo "Changed task ID from {$task['id']} to {$taskData['tasks'][$key]['id']}\n";
    }
    
    // Remove [AI] prefix from title if present
    if (substr($task['title'], 0, 5) === '[AI] ') {
        $taskData['tasks'][$key]['title'] = substr($task['title'], 5);
        $modified = true;
        echo "Removed [AI] prefix from task '{$task['title']}'\n";
    }
    
    // Set assignee to 'ai' if null or empty
    if (empty($task['assignee'])) {
        $taskData['tasks'][$key]['assignee'] = 'ai';
        $modified = true;
        echo "Set assignee to 'ai' for task ID {$taskData['tasks'][$key]['id']}\n";
    }
    
    if ($modified) {
        $modifiedTasks++;
    }
}

// Update metadata
$userTasks = 0;
$aiTasks = 0;
$completedTasks = 0;

foreach ($taskData['tasks'] as $task) {
    if ($task['assignee'] === 'user') {
        $userTasks++;
    } elseif ($task['assignee'] === 'ai') {
        $aiTasks++;
    }
    
    if ($task['status'] === 'completed') {
        $completedTasks++;
    }
}

$taskData['metadata']['total_tasks'] = count($taskData['tasks']);
$taskData['metadata']['completed_tasks'] = $completedTasks;
$taskData['metadata']['user_tasks'] = $userTasks;
$taskData['metadata']['ai_tasks'] = $aiTasks;
$taskData['metadata']['last_updated'] = date('c');

// Write updated tasks.json
if (file_put_contents($tasksFile, json_encode($taskData, JSON_PRETTY_PRINT))) {
    echo "Successfully updated tasks.json\n";
    echo "Modified $modifiedTasks tasks\n";
    echo "Total tasks: {$taskData['metadata']['total_tasks']}\n";
    echo "User tasks: $userTasks\n";
    echo "AI tasks: $aiTasks\n";
    echo "Completed tasks: $completedTasks\n";
} else {
    echo "Error: Could not write to tasks.json\n";
    exit(1);
}

echo "Done!\n"; 