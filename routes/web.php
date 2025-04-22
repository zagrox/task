<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AiSettingsController;
use App\Http\Controllers\ZagroxAiController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\RepositoryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Task Management Routes
Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::get('/tasks/{id}', [TaskController::class, 'show'])->name('tasks.show');
Route::get('/tasks/{id}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
Route::put('/tasks/{id}', [TaskController::class, 'update'])->name('tasks.update');
Route::get('/tasks/{id}/delete', [TaskController::class, 'confirmDelete'])->name('tasks.confirm-delete');
Route::delete('/tasks/{id}', [TaskController::class, 'destroy'])->name('tasks.destroy');
Route::post('/tasks/{id}/note', [TaskController::class, 'addNote'])->name('tasks.add-note');
Route::get('/tasks-report', [TaskController::class, 'report'])->name('tasks.report');

// Tag Management Routes
Route::resource('tags', TagController::class);

// Repository Dashboard Routes
Route::resource('repositories', RepositoryController::class);
Route::post('/repositories/{repository}/github/connect', [RepositoryController::class, 'connectToGitHub'])->name('repositories.github.connect');
Route::get('/repositories/{repository}/github/sync', [RepositoryController::class, 'syncWithGitHub'])->name('repositories.github.sync');
Route::get('/repositories/github/sync-all', [RepositoryController::class, 'syncAllRepositories'])->name('repositories.github.sync-all');

// Temporary route to create Energymo repository
Route::get('/create-energymo', function() {
    $repo = \App\Models\Repository::firstOrCreate(
        ['name' => 'Energymo'], 
        [
            'description' => 'Sport Application',
            'color' => '#e83e8c',
            'github_repo' => 'zagrox/energymo'
        ]
    );
    
    // Link task #58 to this repository if it exists
    $task = \App\Models\Task::find(58);
    if ($task) {
        $task->repository_id = $repo->id;
        $task->save();
        return "Energymo repository created and Task #58 linked to it successfully.";
    }
    
    return "Energymo repository created successfully. ID: " . $repo->id;
});

// AI Task routes
Route::get('/ai-tasks', [App\Http\Controllers\TaskController::class, 'aiTasks'])->name('tasks.ai');

// Version routes
Route::get('/tasks-versions', [VersionController::class, 'index'])->name('tasks.versions');
Route::post('/tasks-versions/push', [VersionController::class, 'pushToRepository'])->name('tasks.versions.push');

// GitHub integration routes
Route::get('/tasks/{id}/sync-to-github', [App\Http\Controllers\TaskController::class, 'syncToGitHub'])->name('tasks.sync-to-github');
Route::post('/api/github/webhook', [App\Http\Controllers\TaskController::class, 'githubWebhook'])->name('github.webhook');

// User settings routes
Route::get('/user/settings', [UserController::class, 'settings'])->name('user.settings');
Route::post('/user/settings', [UserController::class, 'updateSettings'])->name('user.update-settings');
Route::post('/user/password', [UserController::class, 'updatePassword'])->name('user.update-password');

// AI Settings Routes
Route::get('/ai-settings', [AiSettingsController::class, 'index'])->name('ai.settings');
Route::post('/ai-settings/update-assistant', [AiSettingsController::class, 'updateAiAssistant'])->name('ai.settings.update-assistant');
Route::post('/ai-settings/update-task-generation', [AiSettingsController::class, 'updateAiTaskGeneration'])->name('ai.settings.update-task-generation');
Route::post('/ai-settings/update-github', [AiSettingsController::class, 'updateGithubIntegration'])->name('ai.settings.update-github');

// Task Generator Route (if not already defined)
Route::get('/tasks/generate-ai', [App\Http\Controllers\TaskController::class, 'handleAiTasks'])->name('tasks.generate-ai');
Route::get('/tasks/sync-github', [App\Http\Controllers\TaskController::class, 'syncAllToGitHub'])->name('tasks.sync-github');
Route::post('/tasks/process-ai', [App\Http\Controllers\TaskController::class, 'processAiTasks'])->name('tasks.process-ai');

// ZagroxAI Routes
Route::prefix('zagroxai')->group(function () {
    Route::get('/', [ZagroxAiController::class, 'dashboard'])->name('zagroxai.dashboard');
    Route::post('/process', [ZagroxAiController::class, 'processTasks'])->name('zagroxai.process');
    Route::post('/assign/{id}', [ZagroxAiController::class, 'assignToAi'])->name('zagroxai.assign');
    Route::post('/sync/{id}', [ZagroxAiController::class, 'syncToGitHub'])->name('zagroxai.sync');
    Route::get('/settings', [ZagroxAiController::class, 'settings'])->name('zagroxai.settings');
    Route::post('/settings', [ZagroxAiController::class, 'updateSettings'])->name('zagroxai.settings.update');
});

// GitHub Webhook
Route::post('/api/github/webhook', [ZagroxAiController::class, 'webhook'])->name('github.webhook');

// Documentation Routes
Route::prefix('documentation')->name('documentation.')->group(function () {
    Route::get('/', function () {
        return view('documentation.index');
    })->name('index');
    
    Route::get('/getting-started', function () {
        return view('documentation.getting-started');
    })->name('getting-started');
    
    Route::get('/basic-tutorials', function () {
        return view('documentation.basic-tutorials');
    })->name('basic-tutorials');
    
    Route::get('/advanced-tutorials', function () {
        return view('documentation.advanced-tutorials');
    })->name('advanced-tutorials');
    
    Route::get('/user-guide', function () {
        return view('documentation.user-guide');
    })->name('user-guide');
    
    Route::get('/integration', function () {
        return view('documentation.integration');
    })->name('integration');
    
    Route::get('/github', function () {
        return view('documentation.github');
    })->name('github');
    
    Route::get('/api', function () {
        return view('documentation.api');
    })->name('api');
});

// About App Route
Route::get('/about', [AboutController::class, 'index'])->name('about.index');
