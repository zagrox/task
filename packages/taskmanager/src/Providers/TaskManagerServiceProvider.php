<?php

namespace TaskManager\Providers;

use Illuminate\Support\ServiceProvider;
use TaskManager\Contracts\SyncProviderInterface;
use TaskManager\Providers\GitHub\GitHubSyncProvider;

class TaskManagerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register the configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/taskmanager.php', 'taskmanager'
        );
        
        // Register the GitHub sync provider
        $this->app->bind(SyncProviderInterface::class, function ($app) {
            return new GitHubSyncProvider();
        });
        
        // Register named instance for GitHub provider
        $this->app->bind('taskmanager.providers.github', function ($app) {
            return new GitHubSyncProvider();
        });
    }
    
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/taskmanager.php' => config_path('taskmanager.php'),
        ], 'taskmanager-config');
        
        // Load routes if needed
        // $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        
        // Load migrations if needed
        // $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
} 