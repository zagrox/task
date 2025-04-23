@extends('tasks.layout')

@section('title', 'GitHub Integration - Documentation')

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
                <a href="{{ route('documentation.github') }}" class="list-group-item list-group-item-action active">GitHub</a>
                <a href="{{ route('documentation.api') }}" class="list-group-item list-group-item-action">API</a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h1>GitHub Integration</h1>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        Task Manager seamlessly integrates with GitHub to synchronize tasks with issues, automate workflows, and streamline development.
                    </div>

                    <section id="setup">
                        <h2 class="mt-4">Setup and Configuration</h2>
                        <p>Connect your Task Manager instance with GitHub repositories to enable two-way synchronization.</p>
                        
                        <h3>Prerequisites</h3>
                        <ul>
                            <li>GitHub account with admin access to repositories</li>
                            <li>Task Manager admin privileges</li>
                        </ul>
                        
                        <h3>Connection Steps</h3>
                        <ol>
                            <li>Navigate to Settings > Integrations > GitHub</li>
                            <li>Click "Connect GitHub Account"</li>
                            <li>Authorize Task Manager in the GitHub OAuth flow</li>
                            <li>Select repositories to connect with Task Manager</li>
                            <li>Configure webhook settings for real-time updates</li>
                            <li>Save your configuration</li>
                        </ol>
                        
                        <div class="alert alert-warning">
                            <strong>Note:</strong> Once connected, initial synchronization may take several minutes depending on the number of issues in your repositories.
                        </div>
                    </section>

                    <section id="mapping">
                        <h2 class="mt-4">Field Mapping</h2>
                        <p>Task Manager maps GitHub issue fields to task fields as follows:</p>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>GitHub Issue</th>
                                        <th>Task Manager</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Title</td>
                                        <td>Title</td>
                                        <td>Direct mapping</td>
                                    </tr>
                                    <tr>
                                        <td>Body</td>
                                        <td>Description</td>
                                        <td>Markdown formatting preserved</td>
                                    </tr>
                                    <tr>
                                        <td>State (open/closed)</td>
                                        <td>Status</td>
                                        <td>Configurable mapping</td>
                                    </tr>
                                    <tr>
                                        <td>Labels</td>
                                        <td>Tags</td>
                                        <td>Color information preserved</td>
                                    </tr>
                                    <tr>
                                        <td>Assignees</td>
                                        <td>Assignee</td>
                                        <td>Requires user mapping</td>
                                    </tr>
                                    <tr>
                                        <td>Milestone</td>
                                        <td>Version</td>
                                        <td>Optional mapping</td>
                                    </tr>
                                    <tr>
                                        <td>Comments</td>
                                        <td>Comments</td>
                                        <td>Synchronized in both directions</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <h3>Custom Field Mapping</h3>
                        <p>Configure custom mapping for GitHub labels to Task Manager priorities, features, or phases:</p>
                        <ol>
                            <li>Go to Settings > Integrations > GitHub > Field Mapping</li>
                            <li>Create mappings between GitHub labels and Task Manager fields</li>
                            <li>Example: Map the GitHub label "priority:high" to Task Manager priority "High"</li>
                        </ol>
                    </section>

                    <section id="sync">
                        <h2 class="mt-4">Synchronization</h2>
                        
                        <h3>Automatic Synchronization</h3>
                        <p>Changes are automatically synchronized in these scenarios:</p>
                        <ul>
                            <li>When issues are created, updated, or closed in GitHub</li>
                            <li>When tasks are created, updated, or completed in Task Manager</li>
                            <li>When comments are added in either system</li>
                        </ul>
                        
                        <h3>Manual Synchronization</h3>
                        <p>Force synchronization when needed:</p>
                        <ol>
                            <li>For a specific task: Click "Sync with GitHub" on the task detail page</li>
                            <li>For all tasks: Go to Settings > Integrations > GitHub > "Sync All Tasks"</li>
                        </ol>
                        
                        <h3>Conflict Resolution</h3>
                        <p>When conflicts occur during synchronization:</p>
                        <ol>
                            <li>Task Manager will flag the conflict with a notification</li>
                            <li>Navigate to the task details page to see conflict information</li>
                            <li>Choose which version to keep or manually merge changes</li>
                        </ol>
                    </section>

                    <section id="commands">
                        <h2 class="mt-4">GitHub Comment Commands</h2>
                        <p>Control tasks directly from GitHub issue comments using special commands:</p>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Command</th>
                                        <th>Action</th>
                                        <th>Example</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>/priority</code></td>
                                        <td>Change task priority</td>
                                        <td><code>/priority high</code></td>
                                    </tr>
                                    <tr>
                                        <td><code>/assign</code></td>
                                        <td>Assign task to user</td>
                                        <td><code>/assign @username</code></td>
                                    </tr>
                                    <tr>
                                        <td><code>/status</code></td>
                                        <td>Change task status</td>
                                        <td><code>/status in-progress</code></td>
                                    </tr>
                                    <tr>
                                        <td><code>/feature</code></td>
                                        <td>Set task feature</td>
                                        <td><code>/feature authentication</code></td>
                                    </tr>
                                    <tr>
                                        <td><code>/phase</code></td>
                                        <td>Set task phase</td>
                                        <td><code>/phase development</code></td>
                                    </tr>
                                    <tr>
                                        <td><code>/estimate</code></td>
                                        <td>Set hour estimate</td>
                                        <td><code>/estimate 4h</code></td>
                                    </tr>
                                    <tr>
                                        <td><code>/version</code></td>
                                        <td>Set target version</td>
                                        <td><code>/version 2.0.0</code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section id="webhooks">
                        <h2 class="mt-4">Webhook Configuration</h2>
                        <p>Task Manager uses GitHub webhooks for real-time updates:</p>
                        
                        <h3>Automatic Setup</h3>
                        <p>When you connect repositories, Task Manager automatically configures the required webhooks with these events:</p>
                        <ul>
                            <li>Issues (opened, closed, reopened, edited, assigned, labeled, etc.)</li>
                            <li>Issue comments (created, edited, deleted)</li>
                            <li>Pull requests (optional)</li>
                        </ul>
                        
                        <h3>Manual Webhook Setup</h3>
                        <p>If automatic setup fails, manually configure webhooks in GitHub:</p>
                        <ol>
                            <li>Go to repository Settings > Webhooks > Add webhook</li>
                            <li>Set Payload URL to: <code>https://your-task-manager-url/api/github/webhook</code></li>
                            <li>Set Content type to: <code>application/json</code></li>
                            <li>Set Secret to the value from Task Manager settings</li>
                            <li>Select the events: Issues, Issue comments, and Pull requests (optional)</li>
                        </ol>
                    </section>

                    <section id="troubleshooting">
                        <h2 class="mt-4">Troubleshooting</h2>
                        
                        <div class="accordion" id="troubleshootingAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                        Synchronization not working
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body">
                                        <ol>
                                            <li>Check webhook delivery status in GitHub repository settings</li>
                                            <li>Verify Task Manager logs for webhook receipt errors</li>
                                            <li>Ensure GitHub API credentials are still valid</li>
                                            <li>Try manual synchronization from Settings</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        User mapping issues
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body">
                                        <ol>
                                            <li>Verify user email addresses match between GitHub and Task Manager</li>
                                            <li>Check manual user mappings in Settings > Integrations > GitHub > User Mapping</li>
                                            <li>Ensure users have both systems connected to their account</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        Authentication errors
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#troubleshootingAccordion">
                                    <div class="accordion-body">
                                        <ol>
                                            <li>Reconnect GitHub integration from Settings</li>
                                            <li>Check if GitHub OAuth token needs refreshing</li>
                                            <li>Verify GitHub App permissions if using GitHub Apps</li>
                                            <li>Check Task Manager logs for API errors</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 