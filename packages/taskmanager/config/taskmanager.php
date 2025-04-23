<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Task Synchronization Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the TaskManager package.
    |
    */

    // Application mode - standalone or hub
    'mode' => env('TASK_MANAGER_MODE', 'standalone'), // Options: 'standalone', 'hub'

    // Storage configuration
    'storage' => [
        'driver' => env('TASK_STORAGE_DRIVER', 'database'), // Options: 'database', 'json'
        'json_path' => env('TASK_JSON_PATH', storage_path('app/tasks.json')),
    ],

    // Task defaults
    'defaults' => [
        'status' => 'pending',
        'priority' => 'medium',
        'assignee' => 'user',
    ],

    // Synchronization configuration
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
    
    // Hub configuration (for standalone mode)
    'hub' => [
        'url' => env('TASK_HUB_URL', ''),
        'api_key' => env('TASK_HUB_API_KEY', ''),
    ],
    
    // Offline functionality configuration
    'offline' => [
        'enabled' => env('TASK_OFFLINE_ENABLED', true),
        'sync_queue' => [
            'process_interval' => env('TASK_SYNC_QUEUE_INTERVAL', 15), // minutes
            'max_attempts' => env('TASK_SYNC_QUEUE_MAX_ATTEMPTS', 3),
            'cleanup_after_days' => env('TASK_SYNC_QUEUE_CLEANUP_DAYS', 7),
        ],
        'storage' => [
            'driver' => env('TASK_OFFLINE_STORAGE_DRIVER', 'file'), // Options: 'file', 'redis', 'database'
            'path' => env('TASK_OFFLINE_STORAGE_PATH', 'taskmanager/storage'),
            'disk' => env('TASK_OFFLINE_STORAGE_DISK', 'local'),
        ],
        'features' => [
            'auto_detect' => env('TASK_OFFLINE_AUTO_DETECT', true),
            'cache_detection' => env('TASK_OFFLINE_CACHE_DETECTION', true),
            'cache_ttl' => env('TASK_OFFLINE_CACHE_TTL', 3600), // seconds
        ],
    ],
    
    // Feature detection configuration
    'feature_detection' => [
        'network_check_url' => env('TASK_NETWORK_CHECK_URL', 'https://www.google.com'),
        'network_check_timeout' => env('TASK_NETWORK_CHECK_TIMEOUT', 5), // seconds
    ],
    
    // Fallback mechanisms
    'fallbacks' => [
        'hub_unavailable' => env('TASK_FALLBACK_HUB_UNAVAILABLE', 'queue'), // Options: 'queue', 'error'
        'database_unavailable' => env('TASK_FALLBACK_DATABASE_UNAVAILABLE', 'file'), // Options: 'file', 'error'
    ],
]; 