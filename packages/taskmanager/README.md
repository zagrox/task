# TaskManager Package

A modular task management system that can be installed in any Laravel project.

## Installation

### Requirements

- PHP 8.2 or higher
- Laravel 10.0 or higher

### Step 1: Install via Composer

```bash
composer require taskapp/taskmanager
```

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --tag=taskmanager-config
```

### Step 3: Publish Migrations and Run Them

```bash
php artisan vendor:publish --tag=taskmanager-migrations
php artisan migrate
```

## Configuration

### Application Mode

The package supports two operation modes:

1. **Standalone Mode**: For individual projects managing their own tasks
2. **Hub Mode**: For central projects that synchronize tasks across multiple projects

Configure the mode in your `config/taskmanager.php`:

```php
'mode' => env('TASK_MANAGER_MODE', 'standalone'), // Options: 'standalone', 'hub'
```

### Storage Configuration

You can configure how tasks are stored:

```php
'storage' => [
    'driver' => env('TASK_STORAGE_DRIVER', 'database'), // Options: 'database', 'json'
    'json_path' => env('TASK_JSON_PATH', storage_path('app/tasks.json')),
],
```

### Synchronization Settings

For task synchronization between projects:

```php
'sync' => [
    'enabled' => env('TASK_SYNC_ENABLED', false),
    'interval' => env('TASK_SYNC_INTERVAL', 30), // minutes
    'providers' => [
        'github' => [
            'enabled' => env('TASK_SYNC_GITHUB_ENABLED', false),
            'repository' => env('TASK_SYNC_GITHUB_REPOSITORY', ''),
            'token' => env('TASK_SYNC_GITHUB_TOKEN', ''),
        ],
    ],
],
```

## Usage

### Basic Task Operations

```php
use TaskApp\TaskManager\Models\Task;

// Create a task
$task = Task::create([
    'title' => 'Implement new feature',
    'description' => 'Add ability to mark tasks as favorites',
    'status' => 'pending',
    'priority' => 'high',
    'due_date' => now()->addDays(7),
]);

// Update a task
$task->update(['status' => 'in-progress']);

// Delete a task
$task->delete();
```

### Task Synchronization

To sync tasks with external providers (like GitHub):

```php
use TaskApp\TaskManager\Services\SyncService;

$syncService = app(SyncService::class);
$results = $syncService->sync('github', 'both'); // Sync with GitHub in both directions
```

### Command Line Interface

The package includes several useful commands:

```bash
# Sync tasks with external providers
php artisan taskmanager:sync

# Sync tasks with specific provider and direction
php artisan taskmanager:sync --provider=github --direction=pull
```

## Hub Mode Setup

When using TaskManager in hub mode:

1. Set the mode to 'hub' in your configuration
2. Configure synchronization settings for all connected projects
3. Set up a scheduler to regularly sync tasks:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('taskmanager:sync')->everyThirtyMinutes();
}
```

4. Ensure your application is accessible to other projects for webhook notifications

## Standalone Mode Setup

For standalone mode:

1. Set the mode to 'standalone' in your configuration
2. If you want to sync with a hub, configure the hub URL:

```php
'hub' => [
    'url' => env('TASK_HUB_URL', ''),
    'api_key' => env('TASK_HUB_API_KEY', ''),
],
```

## Offline Functionality

TaskManager supports a robust local-first architecture that allows the system to function even when offline:

### Key Features

- **Automatic Feature Detection**: The system automatically detects available features such as network connectivity, database access, and hub service availability.
- **Sync Queue**: Changes made while offline are stored in a queue and synchronized when connectivity is restored.
- **Multiple Storage Options**: Fallback to file-based storage when a database is unavailable.
- **Graceful Degradation**: The system adapts to available resources, providing core functionality even with limited connectivity.

### Using Offline Mode

Offline functionality is enabled by default and works automatically. To check the current system status:

```bash
php artisan taskmanager:connectivity
```

To process pending sync operations:

```bash
php artisan taskmanager:process-sync-queue
```

### Configuration

Configure offline behavior in your `config/taskmanager.php`:

```php
'offline' => [
    'enabled' => env('TASK_OFFLINE_ENABLED', true),
    'sync_queue' => [
        'process_interval' => env('TASK_SYNC_QUEUE_INTERVAL', 15), // minutes
        'max_attempts' => env('TASK_SYNC_QUEUE_MAX_ATTEMPTS', 3),
        'cleanup_after_days' => env('TASK_SYNC_QUEUE_CLEANUP_DAYS', 7),
    ],
    'storage' => [
        'driver' => env('TASK_OFFLINE_STORAGE_DRIVER', 'file'),
        'path' => env('TASK_OFFLINE_STORAGE_PATH', 'taskmanager/storage'),
        'disk' => env('TASK_OFFLINE_STORAGE_DISK', 'local'),
    ],
],
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. 