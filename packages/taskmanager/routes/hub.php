<?php

use Illuminate\Support\Facades\Route;
use TaskApp\TaskManager\Http\Controllers\WebhookController;
use TaskApp\TaskManager\Http\Controllers\ApiTaskController;
use TaskApp\TaskManager\Http\Middleware\VerifyApiKey;

/*
|--------------------------------------------------------------------------
| TaskManager Hub Routes
|--------------------------------------------------------------------------
|
| These routes are loaded when the package is in hub mode and provide
| endpoints for task synchronization between projects.
|
*/

// API routes for tasks
Route::middleware(['api', VerifyApiKey::class])->prefix('api/tasks')->group(function () {
    // Task CRUD operations
    Route::get('/', [ApiTaskController::class, 'index']);
    Route::post('/', [ApiTaskController::class, 'store']);
    Route::get('/{id}', [ApiTaskController::class, 'show']);
    Route::put('/{id}', [ApiTaskController::class, 'update']);
    Route::delete('/{id}', [ApiTaskController::class, 'destroy']);
    
    // Batch operations
    Route::post('/batch', [ApiTaskController::class, 'batchUpdate']);
    
    // Sync status endpoint
    Route::get('/sync/status', [ApiTaskController::class, 'syncStatus']);
});

// Webhook routes for external providers
Route::prefix('webhooks')->group(function () {
    // GitHub webhook
    Route::post('/github', [WebhookController::class, 'handleGitHubWebhook']);
    
    // Project webhook (receives updates from standalone projects)
    Route::post('/project', [WebhookController::class, 'handleProjectWebhook'])
        ->middleware(VerifyApiKey::class);
}); 