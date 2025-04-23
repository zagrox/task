<?php

namespace TaskApp\TaskManager;

use Illuminate\Support\ServiceProvider;
use TaskApp\TaskManager\Console\Commands\SyncTasksCommand;
use TaskApp\TaskManager\Services\SyncService;
use TaskApp\TaskManager\Contracts\SyncProviderInterface;
use TaskApp\TaskManager\Providers\GitHubSyncProvider;

class TaskManagerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/taskmanager.php', 'taskmanager'
        );
        
        // Detect application mode (standalone or hub)
        $isHub = $this->app['config']->get('taskmanager.mode', 'standalone') === 'hub';
        
        // Register core services
        $this->registerCoreServices($isHub);
    }
    
    /**
     * Register core services based on application mode
     */
    protected function registerCoreServices(bool $isHub): void
    {
        // Register the SyncService
        $this->app->singleton(SyncService::class, function ($app) use ($isHub) {
            return new SyncService();
        });
        
        // Register GitHub provider if enabled
        if ($this->app['config']->get('taskmanager.sync.github.enabled', false)) {
            $this->app->bind(GitHubSyncProvider::class, function ($app) {
                return new GitHubSyncProvider(
                    $app['config']->get('taskmanager.sync.github.token'),
                    $app['config']->get('taskmanager.sync.github.repository')
                );
            });
            
            // Add GitHub provider to the list of provider implementations
            $this->app->tag([GitHubSyncProvider::class], 'sync.providers');
        }
        
        // In hub mode, register additional hub-specific services
        if ($isHub) {
            $this->registerHubServices();
        }
    }
    
    /**
     * Register hub-specific services
     */
    protected function registerHubServices(): void
    {
        // Register webhook listener
        $this->app->singleton('taskmanager.webhook.handler', function ($app) {
            return new WebhookHandler($app->make(SyncService::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/taskmanager.php' => config_path('taskmanager.php'),
        ], 'taskmanager-config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'taskmanager-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        
        // Load routes if in hub mode
        if ($this->app['config']->get('taskmanager.mode', 'standalone') === 'hub') {
            $this->loadRoutesFrom(__DIR__.'/../routes/hub.php');
        } else {
            $this->loadRoutesFrom(__DIR__.'/../routes/standalone.php');
        }

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                SyncTasksCommand::class,
            ]);
        }
    }
} 