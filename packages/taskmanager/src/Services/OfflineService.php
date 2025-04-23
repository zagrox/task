<?php

namespace TaskApp\TaskManager\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use TaskApp\TaskManager\Models\SyncQueue;
use TaskApp\TaskManager\Contracts\StorageDriverInterface;

class OfflineService
{
    /**
     * The storage driver
     *
     * @var StorageDriverInterface
     */
    protected $storageDriver;

    /**
     * Features available in the current environment
     *
     * @var array
     */
    protected $availableFeatures = [];

    /**
     * Create a new offline service instance
     *
     * @param StorageDriverInterface $storageDriver
     * @return void
     */
    public function __construct(StorageDriverInterface $storageDriver)
    {
        $this->storageDriver = $storageDriver;
        $this->detectAvailableFeatures();
    }

    /**
     * Detect what features are available in the current environment
     *
     * @return void
     */
    public function detectAvailableFeatures()
    {
        // Check for network connectivity
        $this->availableFeatures['online'] = $this->checkNetworkConnectivity();
        
        // Check for database connectivity
        $this->availableFeatures['database'] = $this->checkDatabaseConnectivity();
        
        // Check for local storage availability
        $this->availableFeatures['local_storage'] = $this->checkLocalStorageAvailability();
        
        // Check for hub service availability
        $this->availableFeatures['hub_service'] = $this->checkHubServiceAvailability();
        
        // IndexedDB or other browser storage for web clients
        $this->availableFeatures['indexed_db'] = $this->isWebClient() ? true : false;
        
        // Cache the detected features
        Cache::put('taskmanager_available_features', $this->availableFeatures, now()->addHours(1));
        
        Log::info('TaskManager features detected', $this->availableFeatures);
    }
    
    /**
     * Check if there is network connectivity
     *
     * @return bool
     */
    protected function checkNetworkConnectivity()
    {
        // For web environments, assume online
        if (php_sapi_name() !== 'cli') {
            return true;
        }
        
        // For CLI environments, check connectivity to hub if configured
        $hubUrl = config('taskmanager.hub.url');
        if (!$hubUrl) {
            return true; // No hub configured, so no online requirement
        }
        
        try {
            $ch = curl_init($hubUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode > 0 && $httpCode < 500;
        } catch (\Exception $e) {
            Log::warning('Network connectivity check failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if database connectivity is available
     *
     * @return bool
     */
    protected function checkDatabaseConnectivity()
    {
        try {
            // Simple query to test database connectivity
            \DB::select('SELECT 1');
            return true;
        } catch (\Exception $e) {
            Log::warning('Database connectivity check failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if local storage is available
     *
     * @return bool
     */
    protected function checkLocalStorageAvailability()
    {
        try {
            $tempFilePath = 'taskmanager/temp/connectivity_test.txt';
            Storage::put($tempFilePath, 'Connectivity test');
            $result = Storage::exists($tempFilePath);
            Storage::delete($tempFilePath);
            
            return $result;
        } catch (\Exception $e) {
            Log::warning('Local storage check failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if hub service is available
     *
     * @return bool
     */
    protected function checkHubServiceAvailability()
    {
        $hubUrl = config('taskmanager.hub.url');
        if (!$hubUrl) {
            return false; // No hub configured
        }
        
        try {
            $pingUrl = rtrim($hubUrl, '/') . '/api/tasksync/status';
            $ch = curl_init($pingUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $httpCode === 200;
        } catch (\Exception $e) {
            Log::warning('Hub service check failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Determine if running in a web client environment
     *
     * @return bool
     */
    protected function isWebClient()
    {
        return php_sapi_name() !== 'cli';
    }
    
    /**
     * Check if a specific feature is available
     *
     * @param string $feature
     * @return bool
     */
    public function hasFeature($feature)
    {
        // Refresh feature detection if needed
        if (empty($this->availableFeatures)) {
            $cached = Cache::get('taskmanager_available_features');
            if ($cached) {
                $this->availableFeatures = $cached;
            } else {
                $this->detectAvailableFeatures();
            }
        }
        
        return $this->availableFeatures[$feature] ?? false;
    }
    
    /**
     * Queue an operation for later synchronization
     *
     * @param string $operation
     * @param string $entityType
     * @param int|string $entityId
     * @param array $data
     * @return bool
     */
    public function queueForSync($operation, $entityType, $entityId, array $data)
    {
        if ($this->hasFeature('database')) {
            // Store in database sync queue
            return SyncQueue::create([
                'operation' => $operation,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'data' => json_encode($data),
                'status' => 'pending',
                'attempts' => 0,
                'created_at' => now(),
            ]) ? true : false;
        } else {
            // Store in local file sync queue
            return $this->storeInLocalSyncQueue($operation, $entityType, $entityId, $data);
        }
    }
    
    /**
     * Store operation in local file sync queue when database is unavailable
     *
     * @param string $operation
     * @param string $entityType
     * @param int|string $entityId
     * @param array $data
     * @return bool
     */
    protected function storeInLocalSyncQueue($operation, $entityType, $entityId, array $data)
    {
        try {
            $queueFile = 'taskmanager/sync_queue/queue.json';
            
            // Read existing queue or create new one
            if (Storage::exists($queueFile)) {
                $queue = json_decode(Storage::get($queueFile), true);
                if (!is_array($queue)) {
                    $queue = ['operations' => []];
                }
            } else {
                $queue = ['operations' => []];
            }
            
            // Add new operation
            $queue['operations'][] = [
                'id' => uniqid(),
                'operation' => $operation,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'data' => $data,
                'status' => 'pending',
                'attempts' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            
            // Save updated queue
            Storage::put($queueFile, json_encode($queue, JSON_PRETTY_PRINT));
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to store in local sync queue: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process pending sync operations
     *
     * @param int $limit
     * @return array
     */
    public function processSyncQueue($limit = 50)
    {
        if (!$this->hasFeature('online') || !$this->hasFeature('hub_service')) {
            return ['processed' => 0, 'failed' => 0, 'status' => 'offline'];
        }
        
        $processed = 0;
        $failed = 0;
        
        if ($this->hasFeature('database')) {
            // Process from database queue
            $result = $this->processDatabaseQueue($limit);
            $processed = $result['processed'];
            $failed = $result['failed'];
        } else {
            // Process from file queue
            $result = $this->processLocalFileQueue($limit);
            $processed = $result['processed'];
            $failed = $result['failed'];
        }
        
        return [
            'processed' => $processed,
            'failed' => $failed,
            'status' => ($processed > 0 || $failed > 0) ? 'completed' : 'no_pending_items'
        ];
    }
    
    /**
     * Process sync queue from database
     *
     * @param int $limit
     * @return array
     */
    protected function processDatabaseQueue($limit)
    {
        $processed = 0;
        $failed = 0;
        
        $pendingItems = SyncQueue::where('status', 'pending')
            ->where('attempts', '<', 3)
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
            
        foreach ($pendingItems as $item) {
            try {
                $syncService = app(SyncService::class);
                $data = json_decode($item->data, true);
                
                $success = $syncService->syncItem(
                    $item->operation,
                    $item->entity_type,
                    $item->entity_id,
                    $data
                );
                
                if ($success) {
                    $item->status = 'completed';
                    $item->synced_at = now();
                    $item->save();
                    $processed++;
                } else {
                    $item->attempts += 1;
                    $item->last_error = 'Sync operation failed';
                    
                    if ($item->attempts >= 3) {
                        $item->status = 'failed';
                    }
                    
                    $item->save();
                    $failed++;
                }
            } catch (\Exception $e) {
                $item->attempts += 1;
                $item->last_error = $e->getMessage();
                
                if ($item->attempts >= 3) {
                    $item->status = 'failed';
                }
                
                $item->save();
                $failed++;
                
                Log::error('Error processing sync queue item: ' . $e->getMessage(), [
                    'item_id' => $item->id,
                    'operation' => $item->operation,
                    'entity_type' => $item->entity_type,
                    'entity_id' => $item->entity_id
                ]);
            }
        }
        
        return ['processed' => $processed, 'failed' => $failed];
    }
    
    /**
     * Process sync queue from local file
     *
     * @param int $limit
     * @return array
     */
    protected function processLocalFileQueue($limit)
    {
        $processed = 0;
        $failed = 0;
        
        try {
            $queueFile = 'taskmanager/sync_queue/queue.json';
            
            if (!Storage::exists($queueFile)) {
                return ['processed' => 0, 'failed' => 0];
            }
            
            $queue = json_decode(Storage::get($queueFile), true);
            if (!isset($queue['operations']) || !is_array($queue['operations'])) {
                return ['processed' => 0, 'failed' => 0];
            }
            
            $pendingItems = array_filter($queue['operations'], function ($item) {
                return ($item['status'] === 'pending' && ($item['attempts'] ?? 0) < 3);
            });
            
            $pendingItems = array_slice($pendingItems, 0, $limit);
            $syncService = app(SyncService::class);
            
            foreach ($pendingItems as $key => $item) {
                try {
                    $success = $syncService->syncItem(
                        $item['operation'],
                        $item['entity_type'],
                        $item['entity_id'],
                        $item['data']
                    );
                    
                    if ($success) {
                        $queue['operations'][$key]['status'] = 'completed';
                        $queue['operations'][$key]['synced_at'] = date('Y-m-d H:i:s');
                        $processed++;
                    } else {
                        $queue['operations'][$key]['attempts'] = ($item['attempts'] ?? 0) + 1;
                        $queue['operations'][$key]['last_error'] = 'Sync operation failed';
                        
                        if ($queue['operations'][$key]['attempts'] >= 3) {
                            $queue['operations'][$key]['status'] = 'failed';
                        }
                        
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $queue['operations'][$key]['attempts'] = ($item['attempts'] ?? 0) + 1;
                    $queue['operations'][$key]['last_error'] = $e->getMessage();
                    
                    if ($queue['operations'][$key]['attempts'] >= 3) {
                        $queue['operations'][$key]['status'] = 'failed';
                    }
                    
                    $failed++;
                    
                    Log::error('Error processing local sync queue item: ' . $e->getMessage(), [
                        'item_id' => $item['id'],
                        'operation' => $item['operation'],
                        'entity_type' => $item['entity_type'],
                        'entity_id' => $item['entity_id']
                    ]);
                }
            }
            
            // Save updated queue
            Storage::put($queueFile, json_encode($queue, JSON_PRETTY_PRINT));
            
            return ['processed' => $processed, 'failed' => $failed];
        } catch (\Exception $e) {
            Log::error('Failed to process local file queue: ' . $e->getMessage());
            return ['processed' => 0, 'failed' => 0];
        }
    }
    
    /**
     * Get appropriate storage driver based on available features
     *
     * @return StorageDriverInterface
     */
    public function getStorageDriver()
    {
        return $this->storageDriver;
    }
} 