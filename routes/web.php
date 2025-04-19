<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\VersionController;

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

// AI Task routes
Route::get('/ai-tasks', [App\Http\Controllers\TaskController::class, 'handleAiTasks'])->name('tasks.ai');

// Version routes
Route::get('/tasks-versions', [VersionController::class, 'index'])->name('tasks.versions');
Route::post('/tasks-versions/push', [VersionController::class, 'pushToRepository'])->name('tasks.versions.push');

// GitHub integration routes
Route::get('/tasks/{id}/sync-to-github', [App\Http\Controllers\TaskController::class, 'syncToGitHub'])->name('tasks.sync-to-github');
Route::post('/api/github/webhook', [App\Http\Controllers\TaskController::class, 'githubWebhook'])->name('github.webhook');
