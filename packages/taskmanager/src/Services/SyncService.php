<?php

namespace TaskManager\Services;

use TaskManager\Contracts\SyncProviderInterface;
use TaskManager\Models\Task;
use Illuminate\Support\Facades\Log;

class SyncService
{
    /**
     * The available sync providers.
     *
     * @var array
     */
    protected $providers = [];

    /**
     * Create a new sync service instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->registerProviders();
    }

    /**
     * Register the available sync providers.
     *
     * @return void
     */
    protected function registerProviders()
    {
        // Register GitHub provider if enabled
        if (config('taskmanager.sync.github.enabled')) {
            $this->providers['github'] = app(GitHubSyncProvider::class);
        }
    }

    /**
     * Synchronize tasks with the specified provider.
     *
     * @param  string  $providerName
     * @param  string  $direction
     * @return array
     */
    public function sync($providerName = 'all', $direction = 'both')
    {
        $results = [];
        $providers = $this->getProviders($providerName);

        foreach ($providers as $name => $provider) {
            Log::info("Starting synchronization with {$name} provider");

            if ($direction === 'both' || $direction === 'pull') {
                $pullCount = $this->pullFromProvider($provider);
                $results["{$name}:pull"] = $pullCount;
            }

            if ($direction === 'both' || $direction === 'push') {
                $pushCount = $this->pushToProvider($provider);
                $results["{$name}:push"] = $pushCount;
            }

            Log::info("Completed synchronization with {$name} provider");
        }

        return $results;
    }

    /**
     * Get the providers to sync with.
     *
     * @param  string  $providerName
     * @return array
     */
    protected function getProviders($providerName)
    {
        if ($providerName === 'all') {
            return $this->providers;
        }

        if (isset($this->providers[$providerName])) {
            return [$providerName => $this->providers[$providerName]];
        }

        throw new \InvalidArgumentException("Provider [{$providerName}] is not registered or enabled.");
    }

    /**
     * Pull tasks from the provider.
     *
     * @param  \TaskManager\Contracts\SyncProviderInterface  $provider
     * @return int
     */
    protected function pullFromProvider(SyncProviderInterface $provider)
    {
        $externalTasks = $provider->getTasks();
        $count = 0;

        foreach ($externalTasks as $externalTask) {
            $task = Task::withExternalId($provider->getName(), $externalTask['id'])
                ->first();

            if ($task) {
                // Update existing task
                $task->update($this->mapExternalTaskToTask($externalTask));
            } else {
                // Create new task
                Task::create(array_merge(
                    $this->mapExternalTaskToTask($externalTask),
                    ['external_id' => $externalTask['id'], 'external_provider' => $provider->getName()]
                ));
            }

            $count++;
        }

        return $count;
    }

    /**
     * Push tasks to the provider.
     *
     * @param  \TaskManager\Contracts\SyncProviderInterface  $provider
     * @return int
     */
    protected function pushToProvider(SyncProviderInterface $provider)
    {
        $tasks = Task::where('external_provider', $provider->getName())
            ->orWhereNull('external_provider')
            ->orderBy('updated_at', 'desc')
            ->get();

        $count = 0;

        foreach ($tasks as $task) {
            $externalTask = $this->mapTaskToExternalTask($task);
            
            if ($task->external_id) {
                // Update task in external provider
                $provider->updateTask($task->external_id, $externalTask);
            } else {
                // Create task in external provider
                $externalId = $provider->createTask($externalTask);
                
                // Update task with external ID
                $task->update([
                    'external_id' => $externalId,
                    'external_provider' => $provider->getName(),
                ]);
            }

            $count++;
        }

        return $count;
    }

    /**
     * Map an external task to a local task structure.
     *
     * @param  array  $externalTask
     * @return array
     */
    protected function mapExternalTaskToTask($externalTask)
    {
        return [
            'title' => $externalTask['title'] ?? '',
            'description' => $externalTask['description'] ?? '',
            'status' => $this->mapExternalStatus($externalTask['status'] ?? ''),
            'priority' => $externalTask['priority'] ?? config('taskmanager.defaults.priority'),
            'due_date' => $externalTask['due_date'] ?? null,
            'notes' => $externalTask['notes'] ?? [],
            'last_synced_at' => now(),
        ];
    }

    /**
     * Map a local task to an external task structure.
     *
     * @param  \TaskManager\Models\Task  $task
     * @return array
     */
    protected function mapTaskToExternalTask($task)
    {
        return [
            'title' => $task->title,
            'description' => $task->description,
            'status' => $this->mapInternalStatus($task->status),
            'priority' => $task->priority,
            'due_date' => $task->due_date,
            'notes' => $task->notes,
        ];
    }

    /**
     * Map an external status to the internal status.
     *
     * @param  string  $status
     * @return string
     */
    protected function mapExternalStatus($status)
    {
        $statusMap = [
            'open' => 'pending',
            'in_progress' => 'in-progress',
            'closed' => 'completed',
            'blocked' => 'blocked',
        ];

        return $statusMap[$status] ?? config('taskmanager.defaults.status');
    }

    /**
     * Map an internal status to the external status.
     *
     * @param  string  $status
     * @return string
     */
    protected function mapInternalStatus($status)
    {
        $statusMap = [
            'pending' => 'open',
            'in-progress' => 'in_progress',
            'completed' => 'closed',
            'blocked' => 'blocked',
            'review' => 'in_progress',
        ];

        return $statusMap[$status] ?? 'open';
    }
} 