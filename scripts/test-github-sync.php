<?php
/**
 * Test script for GitHub integration
 * 
 * Usage: php scripts/test-github-sync.php
 */

// Bootstrap the Laravel application
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\GitHubIssue;
use App\Services\GitHubService;
use Illuminate\Support\Facades\Log;

// Configure logging for this script
Log::info('Starting GitHub sync test script');

// Create a test GitHubIssue
$taskId = 1; // Use a task ID that exists in your tasks.json
$githubIssue = GitHubIssue::firstOrNew(['task_id' => $taskId]);

echo "Testing GitHubIssue model creation\n";
echo "--------------------------------\n";
echo "Table name: " . $githubIssue->getTable() . "\n";
echo "Is new record: " . ($githubIssue->exists ? 'No' : 'Yes') . "\n";

if (!$githubIssue->exists) {
    $githubIssue->repository = 'yourusername/task';
    $githubIssue->save();
    echo "Created new GitHubIssue record for task #$taskId\n";
} else {
    echo "Found existing GitHubIssue record for task #$taskId\n";
    echo "Repository: " . $githubIssue->repository . "\n";
    echo "Issue number: " . ($githubIssue->issue_number ?: 'Not synced yet') . "\n";
}

// Test GitHub service
echo "\nTesting GitHubService\n";
echo "-------------------\n";

$github = app(GitHubService::class);
if (empty(env('GITHUB_REPOSITORY')) || empty(env('GITHUB_ACCESS_TOKEN'))) {
    echo "Error: GitHub configuration is missing. Please check your .env file.\n";
    echo "Required variables: GITHUB_REPOSITORY, GITHUB_ACCESS_TOKEN\n";
    exit(1);
}

echo "GitHub repository: " . env('GITHUB_REPOSITORY') . "\n";
echo "GitHub token configured: " . (empty(env('GITHUB_ACCESS_TOKEN')) ? 'No' : 'Yes') . "\n";

// List all GitHub issues in the database
echo "\nGitHub Issues in Database\n";
echo "------------------------\n";
$issues = GitHubIssue::all();
if ($issues->isEmpty()) {
    echo "No GitHub issues found in the database.\n";
} else {
    foreach ($issues as $issue) {
        echo "Task #{$issue->task_id}, Issue #{$issue->issue_number}, Repository: {$issue->repository}\n";
    }
}

// Test database connection
echo "\nTesting Database Connection\n";
echo "-------------------------\n";
try {
    $tables = DB::select('SELECT name FROM sqlite_master WHERE type="table"');
    echo "Connected to database. Tables:\n";
    foreach ($tables as $table) {
        echo "- " . $table->name . "\n";
    }
} catch (Exception $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n"; 