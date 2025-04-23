<?php

namespace App\Http\Controllers;

use App\Services\ZagroxAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class ZagroxAiController extends Controller
{
    /**
     * @var ZagroxAiService
     */
    protected $zagroxAiService;

    /**
     * Tasks file path
     * 
     * @var string
     */
    protected $tasksFile;

    public function __construct(ZagroxAiService $zagroxAiService)
    {
        $this->zagroxAiService = $zagroxAiService;
        $this->tasksFile = base_path('project-management/tasks.json');
    }

    /**
     * Display AI tasks dashboard
     */
    public function dashboard()
    {
        // Ensure tasks file exists
        if (!File::exists($this->tasksFile)) {
            return view('zagroxai.dashboard')->with('error', 'Tasks file not found');
        }

        // Load tasks
        $taskData = json_decode(File::get($this->tasksFile), true);
        $tasks = $taskData['tasks'] ?? [];

        // Filter AI tasks
        $aiTasks = array_filter($tasks, function($task) {
            return ($task['assignee'] ?? '') === 'ai';
        });

        // Calculate statistics
        $totalAiTasks = count($aiTasks);
        $pendingAiTasks = count(array_filter($aiTasks, function($task) {
            return ($task['status'] ?? '') === 'pending';
        }));
        $inProgressAiTasks = count(array_filter($aiTasks, function($task) {
            return ($task['status'] ?? '') === 'in-progress';
        }));
        $completedAiTasks = count(array_filter($aiTasks, function($task) {
            return ($task['status'] ?? '') === 'completed';
        }));

        return view('zagroxai.dashboard', [
            'aiTasks' => array_values($aiTasks),
            'stats' => [
                'total' => $totalAiTasks,
                'pending' => $pendingAiTasks,
                'in_progress' => $inProgressAiTasks,
                'completed' => $completedAiTasks
            ]
        ]);
    }

    /**
     * Process pending AI tasks
     */
    public function processTasks(Request $request)
    {
        $limit = $request->input('limit', 5);
        
        $result = $this->zagroxAiService->processPendingAiTasks($limit);
        
        return response()->json([
            'success' => true,
            'processed' => $result['processed'],
            'errors' => $result['errors'],
            'pending' => $result['total_pending']
        ]);
    }

    /**
     * Assign a task to ZagroxAI
     */
    public function assignToAi($id)
    {
        $success = $this->zagroxAiService->assignTaskToAi($id);
        
        if ($success) {
            return redirect()->back()->with('success', "Task #{$id} successfully assigned to ZagroxAI");
        } else {
            return redirect()->back()->with('error', "Failed to assign task #{$id} to ZagroxAI");
        }
    }

    /**
     * Sync a task to GitHub
     */
    public function syncToGitHub($id)
    {
        $githubIssue = $this->zagroxAiService->createGitHubIssueForTask($id);
        
        if ($githubIssue) {
            return redirect()->back()->with('success', "Task #{$id} successfully synced to GitHub issue #{$githubIssue->issue_number}");
        } else {
            return redirect()->back()->with('error', "Failed to sync task #{$id} to GitHub");
        }
    }

    /**
     * Display ZagroxAI settings
     */
    public function settings()
    {
        $settings = [
            'github' => [
                'username' => Config::get('zagroxai.github.username'),
                'email' => Config::get('zagroxai.github.email'),
                'repository' => Config::get('zagroxai.github.repository'),
            ],
            'tasks' => [
                'auto_assign_types' => Config::get('zagroxai.tasks.auto_assign_types'),
                'auto_assign_priority_threshold' => Config::get('zagroxai.tasks.auto_assign_priority_threshold'),
                'max_concurrent_tasks' => Config::get('zagroxai.tasks.max_concurrent_tasks'),
            ],
            'integration' => [
                'create_github_issues' => Config::get('zagroxai.integration.create_github_issues'),
                'create_pull_requests' => Config::get('zagroxai.integration.create_pull_requests'),
                'auto_label' => Config::get('zagroxai.integration.auto_label'),
            ],
            'workflow' => [
                'default_branch' => Config::get('zagroxai.workflow.default_branch'),
                'auto_review' => Config::get('zagroxai.workflow.auto_review'),
                'auto_comment' => Config::get('zagroxai.workflow.auto_comment'),
            ]
        ];
        
        return view('zagroxai.settings', [
            'settings' => $settings
        ]);
    }

    /**
     * Update ZagroxAI settings
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'github_username' => 'required|string|max:100',
            'github_email' => 'required|email|max:100',
            'github_repository' => 'required|string|max:100',
            'auto_assign_priority_threshold' => 'required|in:low,medium,high,critical',
            'max_concurrent_tasks' => 'required|integer|min:1|max:20',
            'default_branch' => 'required|string|max:100',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        // Update .env file
        $this->updateEnvVariable('ZAGROXAI_GITHUB_USERNAME', $request->input('github_username'));
        $this->updateEnvVariable('ZAGROXAI_GITHUB_EMAIL', $request->input('github_email'));
        $this->updateEnvVariable('ZAGROXAI_GITHUB_REPOSITORY', $request->input('github_repository'));
        
        if ($request->has('github_token')) {
            $this->updateEnvVariable('ZAGROXAI_GITHUB_TOKEN', $request->input('github_token'));
        }
        
        // Update configuration
        // Note: In a production app, you might want to update the config files or database
        // instead of just .env file. This is a simplified example.
        
        return redirect()->route('zagroxai.settings')->with('success', 'ZagroxAI settings updated successfully');
    }

    /**
     * Update environment variable
     */
    protected function updateEnvVariable($key, $value)
    {
        $path = base_path('.env');
        
        if (File::exists($path)) {
            $content = File::get($path);
            
            // Check if the key exists
            if (strpos($content, $key) !== false) {
                // Replace existing key
                $content = preg_replace("/{$key}=.*/", "{$key}=\"{$value}\"", $content);
            } else {
                // Add new key
                $content .= "\n{$key}=\"{$value}\"";
            }
            
            File::put($path, $content);
        }
    }

    /**
     * GitHub webhook handler
     */
    public function webhook(Request $request)
    {
        // Verify webhook secret if needed
        $webhookSecret = Config::get('zagroxai.integration.webhook_secret');
        
        if (!empty($webhookSecret)) {
            $signature = $request->header('X-Hub-Signature-256');
            $payload = $request->getContent();
            $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $webhookSecret);
            
            if ($signature !== $expectedSignature) {
                Log::error('Invalid GitHub webhook signature');
                return response()->json(['error' => 'Invalid signature'], 403);
            }
        }
        
        $event = $request->header('X-GitHub-Event');
        $payload = $request->json()->all();
        
        // Process different event types
        switch ($event) {
            case 'issues':
                return $this->handleIssueEvent($payload);
            
            case 'pull_request':
                return $this->handlePullRequestEvent($payload);
            
            case 'push':
                return $this->handlePushEvent($payload);
            
            default:
                return response()->json(['status' => 'ignored', 'event' => $event]);
        }
    }

    /**
     * Handle GitHub issue event
     */
    protected function handleIssueEvent(array $payload)
    {
        $action = $payload['action'] ?? '';
        $issue = $payload['issue'] ?? [];
        
        if (empty($issue)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid issue data']);
        }
        
        $issueNumber = $issue['number'] ?? null;
        
        if (!$issueNumber) {
            return response()->json(['status' => 'error', 'message' => 'Missing issue number']);
        }
        
        // Find corresponding task
        $githubIssue = \App\Models\GitHubIssue::where('issue_number', $issueNumber)->first();
        
        if (!$githubIssue) {
            return response()->json(['status' => 'ignored', 'message' => 'No matching task found']);
        }
        
        // Update task based on issue changes
        $tasksFile = base_path('project-management/tasks.json');
        
        if (!File::exists($tasksFile)) {
            return response()->json(['status' => 'error', 'message' => 'Tasks file not found']);
        }
        
        $tasksData = json_decode(File::get($tasksFile), true);
        
        // Find the task index
        $taskIndex = null;
        foreach ($tasksData['tasks'] as $index => $task) {
            if ($task['id'] == $githubIssue->task_id) {
                $taskIndex = $index;
                break;
            }
        }
        
        if ($taskIndex === null) {
            return response()->json(['status' => 'error', 'message' => 'Task not found']);
        }
        
        // Update task based on issue action
        switch ($action) {
            case 'closed':
                $tasksData['tasks'][$taskIndex]['status'] = 'completed';
                $tasksData['tasks'][$taskIndex]['progress'] = 100;
                break;
            
            case 'reopened':
                $tasksData['tasks'][$taskIndex]['status'] = 'in-progress';
                break;
            
            case 'edited':
                // Update title if changed
                if (isset($issue['title'])) {
                    $tasksData['tasks'][$taskIndex]['title'] = $issue['title'];
                }
                break;
        }
        
        // Add a note about the GitHub update
        $tasksData['tasks'][$taskIndex]['notes'][] = [
            'content' => "GitHub issue #{$issueNumber} was {$action}",
            'timestamp' => Carbon::now()->toIso8601String()
        ];
        
        // Update the task updated timestamp
        $tasksData['tasks'][$taskIndex]['updated_at'] = Carbon::now()->toIso8601String();
        
        // Save the updated tasks file
        File::put($tasksFile, json_encode($tasksData, JSON_PRETTY_PRINT));
        
        return response()->json(['status' => 'success', 'action' => $action, 'task_id' => $githubIssue->task_id]);
    }

    /**
     * Handle GitHub pull request event
     */
    protected function handlePullRequestEvent(array $payload)
    {
        // Similar implementation to handleIssueEvent but for PRs
        return response()->json(['status' => 'acknowledged', 'event' => 'pull_request']);
    }

    /**
     * Handle GitHub push event
     */
    protected function handlePushEvent(array $payload)
    {
        // Process repository changes based on push event
        return response()->json(['status' => 'acknowledged', 'event' => 'push']);
    }
}