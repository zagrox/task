<?php

use Illuminate\Support\Facades\Route;
use TaskApp\TaskManager\Http\Controllers\SyncController;

/*
|--------------------------------------------------------------------------
| TaskManager Standalone Routes
|--------------------------------------------------------------------------
|
| These routes are loaded when the package is in standalone mode and provide
| endpoints for task synchronization with a hub.
|
*/

// API routes for syncing with hub
Route::middleware('api')->prefix('api/tasksync')->group(function () {
    // Manual sync trigger
    Route::post('/trigger', [SyncController::class, 'triggerSync']);
    
    // Status endpoint
    Route::get('/status', [SyncController::class, 'syncStatus']);
    
    // Configuration endpoint
    Route::get('/config', [SyncController::class, 'getConfig']);
    Route::post('/config', [SyncController::class, 'updateConfig']);
    
    // Webhook receiver (for hub to push updates)
    Route::post('/webhook', [SyncController::class, 'receiveWebhook'])
        ->middleware('TaskApp\TaskManager\Http\Middleware\VerifyHubSignature');
}); 