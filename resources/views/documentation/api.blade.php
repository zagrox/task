@extends('tasks.layout')

@section('title', 'API Documentation')

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
                <a href="{{ route('documentation.integration') }}" class="list-group-item list-group-item-action">Integration</a>
                <a href="{{ route('documentation.github') }}" class="list-group-item list-group-item-action">GitHub</a>
                <a href="{{ route('documentation.api') }}" class="list-group-item list-group-item-action active">API</a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h1>API Documentation</h1>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        The Task Manager provides a RESTful API that allows you to integrate with other applications and 
                        automate task management workflows. This documentation outlines the available endpoints, authentication
                        methods, and data formats.
                    </div>
                    
                    <div class="alert alert-primary mb-3">
                        <p class="mb-0">
                            <strong>Base URL:</strong> <code>{{ url('/api/v1') }}</code>
                        </p>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h3 fw-bold mb-3" id="authentication">Authentication</h2>
                            <p class="mb-3">
                                All API requests require authentication via API tokens. To authenticate:
                            </p>
                            <ol class="mb-3">
                                <li class="mb-2">Generate an API token in your user profile settings</li>
                                <li class="mb-2">Include the token in the request header as follows:</li>
                            </ol>
                            <div class="bg-dark text-light p-3 rounded mb-4 overflow-auto">
                                <pre class="mb-0"><code>Authorization: Bearer YOUR_API_TOKEN</code></pre>
                            </div>
                            <h3 class="h5 fw-bold mb-3">Token Generation</h3>
                            <p class="mb-3">
                                To generate an API token:
                            </p>
                            <ol class="mb-3">
                                <li class="mb-2">Navigate to your user profile</li>
                                <li class="mb-2">Click on "API Tokens" tab</li>
                                <li class="mb-2">Click "Generate New Token"</li>
                                <li class="mb-2">Enter a token name and select the appropriate permissions</li>
                                <li class="mb-2">Copy and securely store your token (it will only be shown once)</li>
                            </ol>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h3 fw-bold mb-3" id="tasks-api">Tasks API</h2>
                            
                            <div class="border-bottom pb-4 mb-4">
                                <h3 class="h5 fw-bold mb-3">List Tasks</h3>
                                <div class="mb-3">
                                    <span class="badge bg-success me-2">GET</span>
                                    <code>/api/v1/tasks</code>
                                </div>
                                <p class="mb-3">Retrieve a list of tasks with optional filtering.</p>
                                
                                <h4 class="h6 fw-bold mb-2">Query Parameters</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-3">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Parameter</th>
                                                <th>Type</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>status</td>
                                                <td>string</td>
                                                <td>Filter tasks by status (pending, in-progress, completed, on-hold, canceled)</td>
                                            </tr>
                                            <tr>
                                                <td>priority</td>
                                                <td>string</td>
                                                <td>Filter tasks by priority (high, medium, low)</td>
                                            </tr>
                                            <tr>
                                                <td>assignee</td>
                                                <td>string</td>
                                                <td>Filter tasks by assignee (username or ID)</td>
                                            </tr>
                                            <tr>
                                                <td>due_before</td>
                                                <td>date</td>
                                                <td>Filter tasks due before the specified date (YYYY-MM-DD)</td>
                                            </tr>
                                            <tr>
                                                <td>due_after</td>
                                                <td>date</td>
                                                <td>Filter tasks due after the specified date (YYYY-MM-DD)</td>
                                            </tr>
                                            <tr>
                                                <td>page</td>
                                                <td>integer</td>
                                                <td>Page number for pagination (default: 1)</td>
                                            </tr>
                                            <tr>
                                                <td>per_page</td>
                                                <td>integer</td>
                                                <td>Number of tasks per page (default: 20, max: 100)</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <h4 class="h6 fw-bold mb-2">Example Response</h4>
                                <div class="bg-dark text-light p-3 rounded mb-3 overflow-auto">
    <pre><code>{
      "data": [
        {
          "id": 1,
          "title": "Implement user authentication",
          "description": "Add user login and registration functionality",
          "status": "completed",
          "priority": "high",
          "assignee": {
            "id": 5,
            "name": "Jane Doe"
          },
          "due_date": "2023-05-15",
          "created_at": "2023-04-01T10:30:00Z",
          "updated_at": "2023-04-15T14:20:00Z"
        },
        // More tasks...
      ],
      "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 20,
        "total": 98
      }
    }</code></pre>
                                </div>
                            </div>
                            
                            <div class="border-bottom pb-4 mb-4">
                                <h3 class="h5 fw-bold mb-3">Get Task</h3>
                                <div class="mb-3">
                                    <span class="badge bg-success me-2">GET</span>
                                    <code>/api/v1/tasks/{id}</code>
                                </div>
                                <p class="mb-3">Retrieve detailed information about a specific task.</p>
                                
                                <h4 class="h6 fw-bold mb-2">Path Parameters</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-3">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Parameter</th>
                                                <th>Type</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>id</td>
                                                <td>integer</td>
                                                <td>The ID of the task to retrieve</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <h4 class="h6 fw-bold mb-2">Example Response</h4>
                                <div class="bg-dark text-light p-3 rounded mb-3 overflow-auto">
    <pre><code>{
      "data": {
        "id": 1,
        "title": "Implement user authentication",
        "description": "Add user login and registration functionality",
        "status": "completed",
        "priority": "high",
        "assignee": {
          "id": 5,
          "name": "Jane Doe"
        },
        "due_date": "2023-05-15",
        "created_at": "2023-04-01T10:30:00Z",
        "updated_at": "2023-04-15T14:20:00Z",
        "tags": [
          {
            "id": 1,
            "name": "backend"
          },
          {
            "id": 2,
            "name": "authentication"
          }
        ],
        "comments": [
          {
            "id": 1,
            "user": {
              "id": 3,
              "name": "John Smith"
            },
            "content": "Authentication flow completed.",
            "created_at": "2023-04-10T11:45:00Z"
          }
        ]
      }
    }</code></pre>
                                </div>
                            </div>
                            
                            <div class="border-bottom pb-4 mb-4">
                                <h3 class="h5 fw-bold mb-3">Create Task</h3>
                                <div class="mb-3">
                                    <span class="badge bg-primary me-2">POST</span>
                                    <code>/api/v1/tasks</code>
                                </div>
                                <p class="mb-3">Create a new task.</p>
                                
                                <h4 class="h6 fw-bold mb-2">Request Body</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered mb-3">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Field</th>
                                                <th>Type</th>
                                                <th>Required</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>title</td>
                                                <td>string</td>
                                                <td>Yes</td>
                                                <td>The title of the task</td>
                                            </tr>
                                            <tr>
                                                <td>description</td>
                                                <td>string</td>
                                                <td>No</td>
                                                <td>Detailed description of the task</td>
                                            </tr>
                                            <tr>
                                                <td>status</td>
                                                <td>string</td>
                                                <td>No</td>
                                                <td>Task status (default: pending)</td>
                                            </tr>
                                            <tr>
                                                <td>priority</td>
                                                <td>string</td>
                                                <td>No</td>
                                                <td>Task priority (default: medium)</td>
                                            </tr>
                                            <tr>
                                                <td>assignee_id</td>
                                                <td>integer</td>
                                                <td>No</td>
                                                <td>ID of the user to assign the task to</td>
                                            </tr>
                                            <tr>
                                                <td>due_date</td>
                                                <td>string</td>
                                                <td>No</td>
                                                <td>Due date in YYYY-MM-DD format</td>
                                            </tr>
                                            <tr>
                                                <td>tags</td>
                                                <td>array</td>
                                                <td>No</td>
                                                <td>Array of tag IDs or names</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <h4 class="h6 fw-bold mb-2">Example Request</h4>
                                <div class="bg-dark text-light p-3 rounded mb-3 overflow-auto">
    <pre><code>{
      "title": "Design new user dashboard",
      "description": "Create wireframes and mockups for the new user dashboard layout",
      "status": "pending",
      "priority": "high",
      "assignee_id": 3,
      "due_date": "2023-06-30",
      "tags": ["design", "ui/ux"]
    }</code></pre>
                                </div>
                                
                                <h4 class="h6 fw-bold mb-2">Example Response</h4>
                                <div class="bg-dark text-light p-3 rounded mb-3 overflow-auto">
    <pre><code>{
      "data": {
        "id": 99,
        "title": "Design new user dashboard",
        "description": "Create wireframes and mockups for the new user dashboard layout",
        "status": "pending",
        "priority": "high",
        "assignee": {
          "id": 3,
          "name": "Alice Johnson"
        },
        "due_date": "2023-06-30",
        "created_at": "2023-05-01T09:15:00Z",
        "updated_at": "2023-05-01T09:15:00Z",
        "tags": [
          {
            "id": 5,
            "name": "design"
          },
          {
            "id": 8,
            "name": "ui/ux"
          }
        ]
      }
    }</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h3 fw-bold mb-3" id="webhooks">Webhooks</h2>
                            <p class="mb-3">
                                Task Manager supports webhooks to notify external systems about task-related events.
                            </p>
                            
                            <h3 class="h5 fw-bold mb-3">Available Events</h3>
                            <div class="table-responsive">
                                <table class="table table-bordered mb-3">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Event</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>task.created</td>
                                            <td>Triggered when a new task is created</td>
                                        </tr>
                                        <tr>
                                            <td>task.updated</td>
                                            <td>Triggered when a task is updated</td>
                                        </tr>
                                        <tr>
                                            <td>task.deleted</td>
                                            <td>Triggered when a task is deleted</td>
                                        </tr>
                                        <tr>
                                            <td>task.status_changed</td>
                                            <td>Triggered when a task's status changes</td>
                                        </tr>
                                        <tr>
                                            <td>task.assigned</td>
                                            <td>Triggered when a task is assigned to a user</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <h3 class="h5 fw-bold mb-3">Setting Up Webhooks</h3>
                            <p class="mb-3">
                                To set up a webhook:
                            </p>
                            <ol class="mb-3">
                                <li class="mb-2">Go to your account settings</li>
                                <li class="mb-2">Navigate to the "Webhooks" tab</li>
                                <li class="mb-2">Click "Add Webhook"</li>
                                <li class="mb-2">Enter the URL where the webhook should send data</li>
                                <li class="mb-2">Select the events you want to subscribe to</li>
                                <li class="mb-2">Optionally, add a secret key for payload verification</li>
                                <li class="mb-2">Click "Save Webhook"</li>
                            </ol>
                            
                            <h3 class="h5 fw-bold mb-3">Sample Webhook Payload</h3>
                            <div class="bg-dark text-light p-3 rounded mb-3 overflow-auto">
    <pre><code>{
      "event": "task.status_changed",
      "timestamp": "2023-05-01T10:30:00Z",
      "data": {
        "task": {
          "id": 42,
          "title": "Implement user authentication",
          "status": {
            "previous": "in-progress",
            "current": "completed"
          }
        },
        "user": {
          "id": 5,
          "name": "Jane Doe"
        }
      }
    }</code></pre>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h3 fw-bold mb-3" id="rate-limits">Rate Limits</h2>
                            <p class="mb-3">
                                To ensure fair usage and system stability, the API enforces rate limits:
                            </p>
                            <ul class="mb-3">
                                <li class="mb-2">Standard API tokens: 60 requests per minute</li>
                                <li class="mb-2">Enhanced API tokens: 120 requests per minute</li>
                            </ul>
                            <p class="mb-3">
                                Rate limit information is included in the response headers:
                            </p>
                            <div class="bg-dark text-light p-3 rounded mb-3 overflow-auto">
    <pre><code>X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
X-RateLimit-Reset: 1620000000</code></pre>
                            </div>
                            <p class="mb-0">
                                When a rate limit is exceeded, the API returns a 429 Too Many Requests response with a Retry-After header indicating when you can resume making requests.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                        <a href="{{ route('documentation.user-guide') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> User Guide
                        </a>
                        <a href="{{ route('documentation.index') }}" class="btn btn-primary">
                            Back to Documentation
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 