<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// AI Task Management Routes
Route::post('/generate-ai-tasks', function (Request $request) {
    try {
        $output = [];
        $returnCode = 0;
        exec('php artisan tasks:generate-ai --analyze-git 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Command execution failed: ' . implode("\n", $output),
                'output' => $output
            ], 500);
        }
        
        // Parse output to find the number of tasks created
        $tasksCreated = 0;
        foreach ($output as $line) {
            if (preg_match('/Generated (\d+) new AI tasks/i', $line, $matches)) {
                $tasksCreated = (int)$matches[1];
                break;
            }
        }
        
        return response()->json([
            'success' => true,
            'tasks_created' => $tasksCreated,
            'output' => $output
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

Route::post('/complete-task/{id}', function (Request $request, $id) {
    try {
        $tasksFile = base_path('project-management/tasks.json');
        
        if (!file_exists($tasksFile)) {
            return response()->json([
                'success' => false,
                'message' => 'Tasks file not found'
            ], 404);
        }
        
        $tasksData = json_decode(file_get_contents($tasksFile), true);
        $taskFound = false;
        
        foreach ($tasksData['tasks'] as &$task) {
            if ($task['id'] == $id) {
                $task['status'] = 'completed';
                $task['progress'] = 100;
                $task['completed_at'] = date('Y-m-d H:i:s');
                $taskFound = true;
                break;
            }
        }
        
        if (!$taskFound) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }
        
        // Update metadata
        $completedCount = 0;
        $totalCount = count($tasksData['tasks']);
        
        foreach ($tasksData['tasks'] as $task) {
            if ($task['status'] === 'completed') {
                $completedCount++;
            }
        }
        
        $tasksData['metadata']['total_tasks'] = $totalCount;
        $tasksData['metadata']['completed_tasks'] = $completedCount;
        $tasksData['metadata']['last_updated'] = date('Y-m-d H:i:s');
        
        file_put_contents($tasksFile, json_encode($tasksData, JSON_PRETTY_PRINT));
        
        return response()->json([
            'success' => true,
            'message' => 'Task marked as completed'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

Route::post('/complete-all-tasks', function (Request $request) {
    try {
        $taskIds = $request->input('task_ids', []);
        
        if (empty($taskIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No task IDs provided'
            ], 400);
        }
        
        $tasksFile = base_path('project-management/tasks.json');
        
        if (!file_exists($tasksFile)) {
            return response()->json([
                'success' => false,
                'message' => 'Tasks file not found'
            ], 404);
        }
        
        $tasksData = json_decode(file_get_contents($tasksFile), true);
        $completedCount = 0;
        
        foreach ($tasksData['tasks'] as &$task) {
            if (in_array($task['id'], $taskIds) && $task['status'] !== 'completed') {
                $task['status'] = 'completed';
                $task['progress'] = 100;
                $task['completed_at'] = date('Y-m-d H:i:s');
                $completedCount++;
            }
        }
        
        if ($completedCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No tasks were updated'
            ], 404);
        }
        
        // Update metadata
        $totalCompletedCount = 0;
        $totalCount = count($tasksData['tasks']);
        
        foreach ($tasksData['tasks'] as $task) {
            if ($task['status'] === 'completed') {
                $totalCompletedCount++;
            }
        }
        
        $tasksData['metadata']['total_tasks'] = $totalCount;
        $tasksData['metadata']['completed_tasks'] = $totalCompletedCount;
        $tasksData['metadata']['last_updated'] = date('Y-m-d H:i:s');
        
        file_put_contents($tasksFile, json_encode($tasksData, JSON_PRETTY_PRINT));
        
        return response()->json([
            'success' => true,
            'completed_count' => $completedCount,
            'message' => "{$completedCount} tasks marked as completed"
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}); 