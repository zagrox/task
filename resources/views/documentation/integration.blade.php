@extends('tasks.layout')

@section('title', 'Integration - Documentation')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="{{ route('documentation.index') }}" class="list-group-item list-group-item-action">Overview</a>
                <a href="{{ route('documentation.getting-started') }}" class="list-group-item list-group-item-action">Getting Started</a>
                <a href="{{ route('documentation.basic-tutorials') }}" class="list-group-item list-group-item-action">Basic Tutorials</a>
                <a href="{{ route('documentation.advanced-tutorials') }}" class="list-group-item list-group-item-action">Advanced Tutorials</a>
                <a href="{{ route('documentation.user-guide') }}" class="list-group-item list-group-item-action">User Guide</a>
                <a href="{{ route('documentation.integration') }}" class="list-group-item list-group-item-action active">Integration</a>
                <a href="{{ route('documentation.github') }}" class="list-group-item list-group-item-action">GitHub</a>
                <a href="{{ route('documentation.api') }}" class="list-group-item list-group-item-action">API</a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h1>Integration Guide</h1>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        Task Manager provides multiple options for integration with other systems and services. This guide covers the most common integration scenarios.
                    </div>

                    <section id="overview">
                        <h2 class="mt-4">Integration Overview</h2>
                        <p>Task Manager can be integrated with other systems in several ways:</p>
                        
                        <ul class="list-group mb-4">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>REST API</strong>
                                    <p class="mb-0 text-muted">Full programmatic access to Task Manager data and functionality</p>
                                </div>
                                <span class="badge bg-primary rounded-pill">Most Flexible</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Webhooks</strong>
                                    <p class="mb-0 text-muted">Receive real-time notifications about task events</p>
                                </div>
                                <span class="badge bg-success rounded-pill">Real-time</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>GitHub Integration</strong>
                                    <p class="mb-0 text-muted">Two-way sync with GitHub issues and pull requests</p>
                                </div>
                                <span class="badge bg-info rounded-pill">Pre-built</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>OAuth2</strong>
                                    <p class="mb-0 text-muted">Allow users to log in using existing accounts</p>
                                </div>
                                <span class="badge bg-secondary rounded-pill">Authentication</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>CLI Tool</strong>
                                    <p class="mb-0 text-muted">Command-line interface for Task Manager</p>
                                </div>
                                <span class="badge bg-dark rounded-pill">Automation</span>
                            </li>
                        </ul>
                    </section>

                    <section id="api-integration">
                        <h2 class="mt-4">API Integration</h2>
                        <p>Task Manager provides a RESTful API that allows you to programmatically access and manipulate task data:</p>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="mb-0">Getting Started with the API</h3>
                            </div>
                            <div class="card-body">
                                <h4>Authentication</h4>
                                <p>API requests require an API key for authentication:</p>
                                <ol>
                                    <li>Navigate to "Settings" > "API Keys" in Task Manager</li>
                                    <li>Click <span class="badge bg-primary">Generate API Key</span></li>
                                    <li>Set appropriate permissions for the key</li>
                                    <li>Include the key in your request header: <code>Authorization: Bearer YOUR_API_KEY</code></li>
                                </ol>
                                
                                <h4 class="mt-4">Basic Examples</h4>
                                <div class="card">
                                    <div class="card-body bg-light">
                                        <h5>Retrieve Tasks</h5>
                                        <pre><code>curl -X GET "https://yourdomain.com/api/tasks" \
-H "Authorization: Bearer YOUR_API_KEY" \
-H "Accept: application/json"</code></pre>
                                        
                                        <h5 class="mt-3">Create a Task</h5>
                                        <pre><code>curl -X POST "https://yourdomain.com/api/tasks" \
-H "Authorization: Bearer YOUR_API_KEY" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
    "title": "New task via API",
    "description": "This task was created programmatically",
    "priority": "medium",
    "status": "pending",
    "assignee": "user@example.com",
    "due_date": "2023-12-31"
}'</code></pre>
                                        
                                        <h5 class="mt-3">Update a Task</h5>
                                        <pre><code>curl -X PUT "https://yourdomain.com/api/tasks/123" \
-H "Authorization: Bearer YOUR_API_KEY" \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{
    "status": "in-progress",
    "priority": "high"
}'</code></pre>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mt-4">
                                    <strong>More API Details:</strong> For comprehensive API documentation, see the <a href="{{ route('documentation.api') }}">API Reference</a>.
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="webhooks">
                        <h2 class="mt-4">Webhooks</h2>
                        <p>Webhooks allow external systems to receive real-time notifications about events in Task Manager:</p>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="mb-0">Configuring Webhooks</h3>
                            </div>
                            <div class="card-body">
                                <h4>Setup Process</h4>
                                <ol>
                                    <li>Navigate to "Settings" > "Webhooks" in Task Manager</li>
                                    <li>Click <span class="badge bg-primary">Add Webhook</span></li>
                                    <li>Enter the endpoint URL that will receive webhook events</li>
                                    <li>Select the events you want to subscribe to:
                                        <ul>
                                            <li><strong>task.created</strong> - New task created</li>
                                            <li><strong>task.updated</strong> - Task details changed</li>
                                            <li><strong>task.deleted</strong> - Task removed</li>
                                            <li><strong>task.status_changed</strong> - Task status updated</li>
                                            <li><strong>task.assigned</strong> - Task assigned to user</li>
                                            <li><strong>comment.added</strong> - Comment added to task</li>
                                        </ul>
                                    </li>
                                    <li>Configure optional security settings:
                                        <ul>
                                            <li><strong>Secret:</strong> Used to verify webhook authenticity</li>
                                            <li><strong>IP Allowlist:</strong> Restrict which IPs can send requests</li>
                                        </ul>
                                    </li>
                                    <li>Save the webhook configuration</li>
                                </ol>
                                
                                <h4 class="mt-4">Webhook Payload Example</h4>
                                <div class="card">
                                    <div class="card-body bg-light">
                                        <pre><code>{
  "event": "task.status_changed",
  "timestamp": "2023-11-02T15:04:23Z",
  "task": {
    "id": 123,
    "title": "Implement webhook system",
    "previous_status": "in-progress",
    "new_status": "completed",
    "updated_by": {
      "id": 45,
      "name": "Jane Smith",
      "email": "jane@example.com"
    },
    "url": "https://yourdomain.com/tasks/123"
  },
  "signature": "sha256=..."
}</code></pre>
                                    </div>
                                </div>
                                
                                <h4 class="mt-4">Verifying Webhook Signatures</h4>
                                <p>To verify that a webhook came from Task Manager:</p>
                                <ol>
                                    <li>Extract the <code>X-Task-Manager-Signature</code> header from the request</li>
                                    <li>Calculate an HMAC signature using your webhook secret and the raw request body</li>
                                    <li>Compare your calculated signature with the one in the header</li>
                                </ol>
                                
                                <div class="card">
                                    <div class="card-body bg-light">
                                        <h5>PHP Example</h5>
                                        <pre><code>$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_TASK_MANAGER_SIGNATURE'];
$secret = 'your_webhook_secret';

$calculatedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (hash_equals($calculatedSignature, $signature)) {
    // Webhook is verified
    // Process the payload
} else {
    // Invalid signature
    http_response_code(401);
    exit('Invalid signature');
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="oauth2">
                        <h2 class="mt-4">OAuth2 Integration</h2>
                        <p>Task Manager supports OAuth2 for authentication with external identity providers:</p>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="mb-0">Setting Up OAuth2</h3>
                            </div>
                            <div class="card-body">
                                <h4>Supported Providers</h4>
                                <div class="row mb-4">
                                    <div class="col-md-3 text-center">
                                        <i class="fab fa-google fa-3x mb-2"></i>
                                        <p>Google</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <i class="fab fa-github fa-3x mb-2"></i>
                                        <p>GitHub</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <i class="fab fa-microsoft fa-3x mb-2"></i>
                                        <p>Microsoft</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <i class="fas fa-key fa-3x mb-2"></i>
                                        <p>OIDC</p>
                                    </div>
                                </div>
                                
                                <h4>Configuration Steps</h4>
                                <ol>
                                    <li>Navigate to "Settings" > "Authentication" in Task Manager</li>
                                    <li>Enable the OAuth2 providers you want to use</li>
                                    <li>For each provider:
                                        <ul>
                                            <li>Register Task Manager as an OAuth2 client in the provider's developer console</li>
                                            <li>Enter the client ID and client secret obtained from the provider</li>
                                            <li>Configure the redirect URI as <code>https://yourdomain.com/auth/callback/{provider}</code></li>
                                            <li>Configure scope requirements (e.g., email, profile)</li>
                                        </ul>
                                    </li>
                                    <li>Set up user provisioning and role mapping rules</li>
                                    <li>Test the authentication flow</li>
                                </ol>
                                
                                <div class="alert alert-warning mt-4">
                                    <strong>Note:</strong> When using OAuth2, you'll need to decide how to handle user provisioning (automatic vs. manual approval) and role assignment.
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="cli-tool">
                        <h2 class="mt-4">Command Line Interface</h2>
                        <p>Task Manager includes a CLI tool for automation and scripting:</p>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="mb-0">Using the CLI Tool</h3>
                            </div>
                            <div class="card-body">
                                <h4>Installation</h4>
                                <div class="card mb-4">
                                    <div class="card-body bg-light">
                                        <pre><code># Install globally via npm
npm install -g task-manager-cli

# Or use directly with npx
npx task-manager-cli --help</code></pre>
                                    </div>
                                </div>
                                
                                <h4>Configuration</h4>
                                <p>Create a configuration file <code>~/.taskmanagerrc</code> or use environment variables:</p>
                                <div class="card mb-4">
                                    <div class="card-body bg-light">
                                        <pre><code># ~/.taskmanagerrc
TASK_MANAGER_URL=https://yourdomain.com
TASK_MANAGER_API_KEY=your_api_key</code></pre>
                                    </div>
                                </div>
                                
                                <h4>Common Commands</h4>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Command</th>
                                                <th>Description</th>
                                                <th>Example</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><code>list</code></td>
                                                <td>List tasks with optional filters</td>
                                                <td><code>taskman list --status=pending --assignee=me</code></td>
                                            </tr>
                                            <tr>
                                                <td><code>create</code></td>
                                                <td>Create a new task</td>
                                                <td><code>taskman create "Fix login bug" --priority=high</code></td>
                                            </tr>
                                            <tr>
                                                <td><code>update</code></td>
                                                <td>Update an existing task</td>
                                                <td><code>taskman update 123 --status=completed</code></td>
                                            </tr>
                                            <tr>
                                                <td><code>comment</code></td>
                                                <td>Add a comment to a task</td>
                                                <td><code>taskman comment 123 "Fixed in commit abc123"</code></td>
                                            </tr>
                                            <tr>
                                                <td><code>report</code></td>
                                                <td>Generate task reports</td>
                                                <td><code>taskman report weekly --format=csv</code></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <h4 class="mt-4">Using in CI/CD Pipelines</h4>
                                <p>The CLI tool is perfect for automation in CI/CD environments:</p>
                                <div class="card">
                                    <div class="card-body bg-light">
                                        <h5>GitHub Actions Example</h5>
                                        <pre><code>name: Update Task Status

on:
  push:
    branches: [ main ]

jobs:
  update-task:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      
      - name: Match commit to task
        id: task-match
        run: |
          TASK_ID=$(echo "@{{ github.event.head_commit.message }}" | grep -oP 'Task-\d+' | cut -d'-' -f2)
          echo "::set-output name=task_id::$TASK_ID"
      
      - name: Update task status
        if: steps.task-match.outputs.task_id != ''
        run: |
          npx task-manager-cli update @{{ steps.task-match.outputs.task_id }} --status=ready-for-review --comment="Code pushed to main branch"
        env:
          TASK_MANAGER_URL: @{{ secrets.TASK_MANAGER_URL }}
          TASK_MANAGER_API_KEY: @{{ secrets.TASK_MANAGER_API_KEY }}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="card mt-5">
                        <div class="card-body">
                            <h2>Next Steps</h2>
                            <p>Explore these additional resources for specific integration scenarios:</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fab fa-github fa-3x mb-3"></i>
                                            <h5>GitHub Integration</h5>
                                            <p>Sync tasks with GitHub issues</p>
                                            <a href="{{ route('documentation.github') }}" class="btn btn-sm btn-outline-primary">View Guide</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-code fa-3x mb-3"></i>
                                            <h5>API Reference</h5>
                                            <p>Complete API documentation</p>
                                            <a href="{{ route('documentation.api') }}" class="btn btn-sm btn-outline-primary">View API Docs</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-robot fa-3x mb-3"></i>
                                            <h5>ZagroxAI</h5>
                                            <p>AI-powered task automation</p>
                                            <a href="{{ route('documentation.advanced-tutorials') }}#ai-features" class="btn btn-sm btn-outline-primary">Learn More</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 