<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\GenerateAiTasks::class,
        \App\Console\Commands\SyncTasksToGitHub::class,
        \App\Console\Commands\UpdateVersion::class,
        \App\Console\Commands\UpdateTaskStatus::class,
        \App\Console\Commands\MigrateTasksFromJson::class,
        \App\Console\Commands\ProcessAiTasks::class,
        \App\Console\Commands\SeedTaskData::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        
        // Register the GitMonitor command to run hourly
        $schedule->command('git:monitor')->hourly();
        
        // Register the ProjectDashboard command to run daily
        $schedule->command('project:dashboard')->dailyAt('09:00');
        
        // Register the TaskManager backup to run daily at midnight
        $schedule->command('task:manage report --report-type=summary')->dailyAt('00:00')
            ->appendOutputTo(storage_path('logs/task-reports.log'));
        
        // Schedule the AI task generator to run weekly
        $schedule->command('task:generate-ai --analyze-git')
                 ->weekly()
                 ->sundays()
                 ->at('23:00')
                 ->appendOutputTo(storage_path('logs/ai-tasks.log'));
                 
        // Run a monthly reminder to check for version updates
        $schedule->command('version:update --no-git')
                 ->monthly()
                 ->firstMonday()
                 ->at('09:00')
                 ->appendOutputTo(storage_path('logs/version-updates.log'));

        // Run AI task generation daily
        $schedule->command('tasks:generate-ai')
                 ->dailyAt('01:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/ai-task-generation.log'));
                 
        // Run GitHub synchronization daily
        $schedule->command('tasks:sync-to-github --all')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/github-sync.log'));
                 
        // Run AI task processing every 6 hours
        $schedule->command('tasks:process-ai --limit=3')
                 ->everySixHours()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/ai-task-processing.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 