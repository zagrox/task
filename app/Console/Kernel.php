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
        // ... existing commands
        Commands\Tasks\GenerateAiTasks::class,
        Commands\UpdateVersion::class,
        Commands\SyncTasksToGitHub::class,
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

        // Sync tasks to GitHub daily at midnight
        $schedule->command('tasks:sync-to-github --all')
                 ->daily()
                 ->at('00:00')
                 ->appendOutputTo(storage_path('logs/github-sync.log'));
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