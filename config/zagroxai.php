<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ZagroxAI GitHub Integration Settings
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the ZagroxAI GitHub account
    | integration. These settings control how the AI assistant interacts
    | with GitHub repositories and tasks.
    |
    */

    'github' => [
        'username' => env('ZAGROXAI_GITHUB_USERNAME', 'ZagroxAi'),
        'email' => env('ZAGROXAI_GITHUB_EMAIL', 'ai@zagrox.com'),
        'access_token' => env('ZAGROXAI_GITHUB_TOKEN', ''),
        'repository' => env('ZAGROXAI_GITHUB_REPOSITORY', 'ZagroxAi/task-contributions'),
    ],

    'tasks' => [
        // Types of tasks that should be automatically assigned to ZagroxAI
        'auto_assign_types' => [
            'documentation',
            'testing',
            'refactoring',
            'optimization',
            'dependency-update',
        ],

        // Priority threshold for auto-assignment (tasks with priority lower than this will be assigned to AI)
        'auto_assign_priority_threshold' => 'medium',

        // Maximum number of concurrent tasks for ZagroxAI
        'max_concurrent_tasks' => 5,
    ],

    'integration' => [
        // Enable automatic creation of GitHub issues for AI tasks
        'create_github_issues' => true,

        // Enable automatic PR creation for completed AI tasks
        'create_pull_requests' => true,

        // GitHub webhook secret for securing webhooks
        'webhook_secret' => env('ZAGROXAI_WEBHOOK_SECRET', ''),

        // Automatically add the 'ai-generated' label to commits and PRs
        'auto_label' => true,
    ],

    'workflow' => [
        // Default branch to create PRs against
        'default_branch' => 'main',

        // Whether ZagroxAI should add itself as a reviewer to PRs it didn't create
        'auto_review' => true,

        // Whether ZagroxAI should automatically comment on issues and PRs
        'auto_comment' => true,

        // Template directories for ZagroxAI responses
        'templates_dir' => base_path('resources/views/zagroxai/templates'),
    ],
]; 