@extends('tasks.layout')

@section('title', 'Advanced Tutorials - Documentation')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="{{ route('documentation.index') }}" class="list-group-item list-group-item-action">Overview</a>
                <a href="{{ route('documentation.getting-started') }}" class="list-group-item list-group-item-action">Getting Started</a>
                <a href="{{ route('documentation.basic-tutorials') }}" class="list-group-item list-group-item-action">Basic Tutorials</a>
                <a href="{{ route('documentation.advanced-tutorials') }}" class="list-group-item list-group-item-action active">Advanced Tutorials</a>
                <a href="{{ route('documentation.user-guide') }}" class="list-group-item list-group-item-action">User Guide</a>
                <a href="{{ route('documentation.integration') }}" class="list-group-item list-group-item-action">Integration</a>
                <a href="{{ route('documentation.github') }}" class="list-group-item list-group-item-action">GitHub</a>
                <a href="{{ route('documentation.api') }}" class="list-group-item list-group-item-action">API</a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h1>Advanced Tutorials</h1>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        These advanced tutorials cover more complex features of Task Manager. They assume you're already familiar with the basic functionality covered in the <a href="{{ route('documentation.basic-tutorials') }}">Basic Tutorials</a>.
                    </div>

                    <section id="task-dependencies">
                        <h2 class="mt-4">Task Dependencies</h2>
                        <p>Learn how to create and manage task dependencies:</p>
                        
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3>Setting Up Task Dependencies</h3>
                                <ol>
                                    <li>Open a task that depends on another task</li>
                                    <li>Scroll to the "Dependencies" section</li>
                                    <li>Click <span class="badge bg-primary">Add Dependency</span></li>
                                    <li>Search for and select the prerequisite task</li>
                                    <li>Choose the dependency type:
                                        <ul>
                                            <li><strong>Blocks:</strong> This task blocks the selected task</li>
                                            <li><strong>Is blocked by:</strong> This task is blocked by the selected task</li>
                                            <li><strong>Related to:</strong> Tasks are related but not blocking</li>
                                        </ul>
                                    </li>
                                    <li>Click <span class="badge bg-success">Save</span> to establish the dependency</li>
                                </ol>
                                
                                <div class="alert alert-warning">
                                    <strong>Important:</strong> Tasks with blocking dependencies cannot be marked as completed until all blocker tasks are resolved.
                                </div>
                                
                                <h3 class="mt-4">Visualizing Dependencies</h3>
                                <ol>
                                    <li>Navigate to "Reports" in the sidebar</li>
                                    <li>Select "Dependency Graph"</li>
                                    <li>Filter by feature or phase if needed</li>
                                    <li>The graph shows all tasks and their relationships:
                                        <ul>
                                            <li>Red lines indicate blocking relationships</li>
                                            <li>Blue lines indicate related tasks</li>
                                            <li>Hover over any task to highlight its direct dependencies</li>
                                        </ul>
                                    </li>
                                </ol>
                                
                                <div class="text-center">
                                    <img src="/img/documentation/dependency-graph.png" alt="Task dependency graph" class="img-fluid border rounded shadow-sm my-3" style="max-width: 500px;">
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="custom-fields">
                        <h2 class="mt-4">Custom Fields and Templates</h2>
                        <p>Learn how to create and use custom fields and task templates:</p>
                        
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3>Creating Custom Fields</h3>
                                <ol>
                                    <li>Navigate to "Settings" > "Custom Fields"</li>
                                    <li>Click <span class="badge bg-primary">Add Custom Field</span></li>
                                    <li>Configure the field:
                                        <ul>
                                            <li><strong>Name:</strong> How the field will be labeled</li>
                                            <li><strong>Type:</strong> Text, Number, Date, Dropdown, Checkbox, etc.</li>
                                            <li><strong>Required:</strong> Whether the field must be filled</li>
                                            <li><strong>Default Value:</strong> Pre-filled value if any</li>
                                            <li><strong>Options:</strong> For dropdown fields, the available choices</li>
                                        </ul>
                                    </li>
                                    <li>Set field visibility by feature or phase</li>
                                    <li>Click <span class="badge bg-success">Save Field</span></li>
                                </ol>
                                
                                <h3 class="mt-4">Creating Task Templates</h3>
                                <ol>
                                    <li>Navigate to "Settings" > "Task Templates"</li>
                                    <li>Click <span class="badge bg-primary">New Template</span></li>
                                    <li>Set template details:
                                        <ul>
                                            <li><strong>Name:</strong> Template identifier (e.g., "Bug Report")</li>
                                            <li><strong>Description:</strong> When to use this template</li>
                                            <li><strong>Pre-filled fields:</strong> Default values for standard fields</li>
                                            <li><strong>Custom fields:</strong> Which custom fields to include</li>
                                        </ul>
                                    </li>
                                    <li>Click <span class="badge bg-success">Save Template</span></li>
                                </ol>
                                
                                <div class="alert alert-secondary">
                                    <strong>Note:</strong> Templates appear in the "New Task" form as a dropdown. Selecting a template automatically fills in the configured default values.
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="automation">
                        <h2 class="mt-4">Workflow Automation</h2>
                        <p>Learn how to automate task workflows:</p>
                        
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3>Creating Automation Rules</h3>
                                <ol>
                                    <li>Navigate to "Settings" > "Automation"</li>
                                    <li>Click <span class="badge bg-primary">New Rule</span></li>
                                    <li>Configure the trigger:
                                        <ul>
                                            <li><strong>When:</strong> Task created, updated, comment added, etc.</li>
                                            <li><strong>Conditions:</strong> Optional filters (e.g., only tasks with high priority)</li>
                                        </ul>
                                    </li>
                                    <li>Set the actions to perform:
                                        <ul>
                                            <li><strong>Change field:</strong> Update a field value</li>
                                            <li><strong>Assign to:</strong> Reassign the task</li>
                                            <li><strong>Add comment:</strong> Post an automated comment</li>
                                            <li><strong>Send notification:</strong> Email or system notification</li>
                                            <li><strong>Create subtask:</strong> Generate a dependent task</li>
                                        </ul>
                                    </li>
                                    <li>Set rule priority (for multiple matching rules)</li>
                                    <li>Click <span class="badge bg-success">Save Rule</span></li>
                                </ol>
                                
                                <div class="alert alert-success">
                                    <strong>Example Rule:</strong> When a task status changes to "Ready for Review", automatically assign it to the QA team lead and add a comment mentioning the original assignee.
                                </div>
                                
                                <h3 class="mt-4">Scheduled Actions</h3>
                                <ol>
                                    <li>Navigate to "Settings" > "Scheduled Actions"</li>
                                    <li>Click <span class="badge bg-primary">New Schedule</span></li>
                                    <li>Configure the schedule:
                                        <ul>
                                            <li><strong>Frequency:</strong> Daily, Weekly, Monthly, or Custom cron expression</li>
                                            <li><strong>Time:</strong> When the action should run</li>
                                            <li><strong>Task filter:</strong> Which tasks to affect</li>
                                        </ul>
                                    </li>
                                    <li>Set the actions to perform (similar to automation rules)</li>
                                    <li>Click <span class="badge bg-success">Save Schedule</span></li>
                                </ol>
                                
                                <div class="alert alert-info">
                                    <strong>Common use cases:</strong> Send overdue task reminders every morning, automatically escalate priority for tasks inactive for >7 days.
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="ai-features">
                        <h2 class="mt-4">ZagroxAI Integration</h2>
                        <p>Learn how to leverage AI features in Task Manager:</p>
                        
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3>Setting Up ZagroxAI</h3>
                                <ol>
                                    <li>Navigate to "ZagroxAI" > "Settings" in the sidebar</li>
                                    <li>Configure API settings:
                                        <ul>
                                            <li><strong>API Key:</strong> Your OpenAI or compatible API key</li>
                                            <li><strong>Model:</strong> Select the AI model to use</li>
                                            <li><strong>Temperature:</strong> Creativity level (0.0-1.0)</li>
                                        </ul>
                                    </li>
                                    <li>Configure task generation settings:
                                        <ul>
                                            <li><strong>Repository Path:</strong> Path to your code repository</li>
                                            <li><strong>Analysis Depth:</strong> How many commits to analyze</li>
                                            <li><strong>Task Generation Rules:</strong> Configure task creation parameters</li>
                                        </ul>
                                    </li>
                                    <li>Click <span class="badge bg-success">Save Settings</span></li>
                                </ol>
                                
                                <h3 class="mt-4">Using AI Task Generation</h3>
                                <ol>
                                    <li>Navigate to "ZagroxAI" > "Dashboard"</li>
                                    <li>Click <span class="badge bg-primary">Generate Tasks</span></li>
                                    <li>Select analysis parameters:
                                        <ul>
                                            <li><strong>Branch:</strong> Which git branch to analyze</li>
                                            <li><strong>Date Range:</strong> Limit analysis to specific period</li>
                                            <li><strong>Task Type:</strong> Bug fixes, features, or both</li>
                                        </ul>
                                    </li>
                                    <li>Click <span class="badge bg-success">Start Analysis</span></li>
                                    <li>Review generated tasks, edit as needed, then click <span class="badge bg-success">Approve & Create</span></li>
                                </ol>
                                
                                <div class="alert alert-warning">
                                    <strong>Note:</strong> ZagroxAI requires a valid API key and sufficient credits for the selected model.
                                </div>
                            </div>
                        </div>
                    </section>

                    <section id="api-webhooks">
                        <h2 class="mt-4">API and Webhooks</h2>
                        <p>Learn how to integrate Task Manager with other systems:</p>
                        
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3>Using the API</h3>
                                <ol>
                                    <li>Navigate to "Settings" > "API Keys"</li>
                                    <li>Click <span class="badge bg-primary">Generate API Key</span></li>
                                    <li>Set permissions for the key:
                                        <ul>
                                            <li><strong>Read:</strong> Retrieve data only</li>
                                            <li><strong>Write:</strong> Create and update tasks</li>
                                            <li><strong>Delete:</strong> Remove tasks</li>
                                            <li><strong>Admin:</strong> Full access including settings</li>
                                        </ul>
                                    </li>
                                    <li>Set scope (which areas the key can access)</li>
                                    <li>Click <span class="badge bg-success">Create Key</span></li>
                                    <li>Use the provided key in API requests with the <code>Authorization: Bearer YOUR_API_KEY</code> header</li>
                                </ol>
                                
                                <div class="alert alert-secondary">
                                    <strong>Example API request:</strong><br>
                                    <code>curl -X GET https://yourdomain.com/api/tasks \<br>
                                    -H "Authorization: Bearer YOUR_API_KEY" \<br>
                                    -H "Accept: application/json"</code>
                                </div>
                                
                                <h3 class="mt-4">Configuring Webhooks</h3>
                                <ol>
                                    <li>Navigate to "Settings" > "Webhooks"</li>
                                    <li>Click <span class="badge bg-primary">Add Webhook</span></li>
                                    <li>Configure the webhook:
                                        <ul>
                                            <li><strong>URL:</strong> Endpoint to receive notifications</li>
                                            <li><strong>Events:</strong> Which events trigger the webhook</li>
                                            <li><strong>Secret:</strong> Optional signature verification key</li>
                                            <li><strong>Format:</strong> JSON or form data</li>
                                        </ul>
                                    </li>
                                    <li>Click <span class="badge bg-success">Save Webhook</span></li>
                                </ol>
                                
                                <div class="alert alert-info">
                                    <strong>Tip:</strong> Test webhooks with services like Webhook.site before connecting to production systems.
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="card mt-5">
                        <div class="card-body">
                            <h2>Next Steps</h2>
                            <p>Continue your learning journey with these resources:</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-plug fa-3x mb-3"></i>
                                            <h5>Integration Guide</h5>
                                            <p>Connect with other tools and services</p>
                                            <a href="{{ route('documentation.integration') }}" class="btn btn-sm btn-outline-primary">View Guide</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-code fa-3x mb-3"></i>
                                            <h5>API Documentation</h5>
                                            <p>Full API reference for developers</p>
                                            <a href="{{ route('documentation.api') }}" class="btn btn-sm btn-outline-primary">View API Docs</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-users-cog fa-3x mb-3"></i>
                                            <h5>Admin Guide</h5>
                                            <p>System administration and maintenance</p>
                                            <a href="{{ route('documentation.user-guide') }}#admin" class="btn btn-sm btn-outline-primary">View Admin Guide</a>
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