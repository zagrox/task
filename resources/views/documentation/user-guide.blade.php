@extends('tasks.layout')

@section('title', 'User Guide - Documentation')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="{{ route('documentation.index') }}" class="list-group-item list-group-item-action">Overview</a>
                <a href="{{ route('documentation.getting-started') }}" class="list-group-item list-group-item-action">Getting Started</a>
                <a href="{{ route('documentation.basic-tutorials') }}" class="list-group-item list-group-item-action">Basic Tutorials</a>
                <a href="{{ route('documentation.advanced-tutorials') }}" class="list-group-item list-group-item-action">Advanced Tutorials</a>
                <a href="{{ route('documentation.user-guide') }}" class="list-group-item list-group-item-action active">User Guide</a>
                <a href="{{ route('documentation.integration') }}" class="list-group-item list-group-item-action">Integration</a>
                <a href="{{ route('documentation.github') }}" class="list-group-item list-group-item-action">GitHub</a>
                <a href="{{ route('documentation.api') }}" class="list-group-item list-group-item-action">API</a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h1>Task Manager User Guide</h1>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        The Task Manager application provides a comprehensive set of tools for managing tasks, tracking progress, 
                        and collaborating with team members. This guide will walk you through the main features and how to use them effectively.
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h3 fw-bold mb-3" id="dashboard">Dashboard</h2>
                            <p class="mb-3">
                                The dashboard is your central hub for monitoring tasks and project progress. From here you can:
                            </p>
                            <ul class="mb-4">
                                <li class="mb-2">View task statistics and summaries</li>
                                <li class="mb-2">See tasks grouped by status (pending, in-progress, completed)</li>
                                <li class="mb-2">Track priority distribution</li>
                                <li class="mb-2">Monitor recent activity</li>
                                <li class="mb-2">Access quick links to frequently used features</li>
                            </ul>
                            <img src="{{ asset('images/documentation/dashboard.jpg') }}" alt="Dashboard Screenshot" class="img-fluid rounded border mb-3">
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h3 fw-bold mb-3" id="tasks">Managing Tasks</h2>
                            
                            <h3 class="h5 fw-bold mb-3">Creating Tasks</h3>
                            <p class="mb-3">
                                To create a new task:
                            </p>
                            <ol class="mb-4">
                                <li class="mb-2">Click the "New Task" button in the navigation bar or dashboard</li>
                                <li class="mb-2">Fill in the task details including title, description, and due date</li>
                                <li class="mb-2">Assign the task to yourself or a team member</li>
                                <li class="mb-2">Set priority level (Low, Medium, High)</li>
                                <li class="mb-2">Add relevant tags for categorization</li>
                                <li class="mb-2">Click "Create Task" to save</li>
                            </ol>
                            
                            <h3 class="h5 fw-bold mb-3">Editing Tasks</h3>
                            <p class="mb-3">
                                To edit an existing task:
                            </p>
                            <ol class="mb-4">
                                <li class="mb-2">Navigate to the task list page</li>
                                <li class="mb-2">Click on the task you want to edit</li>
                                <li class="mb-2">Click the "Edit" button in the task details view</li>
                                <li class="mb-2">Update the task information as needed</li>
                                <li class="mb-2">Click "Save Changes" to update the task</li>
                            </ol>
                            
                            <h3 class="h5 fw-bold mb-3">Task Status Management</h3>
                            <p class="mb-3">
                                Tasks can have one of the following statuses:
                            </p>
                            <ul class="mb-4">
                                <li class="mb-2"><span class="badge bg-warning text-dark">Pending</span> - Tasks that have not been started</li>
                                <li class="mb-2"><span class="badge bg-primary">In Progress</span> - Tasks currently being worked on</li>
                                <li class="mb-2"><span class="badge bg-success">Completed</span> - Tasks that have been finished</li>
                                <li class="mb-2"><span class="badge bg-info">On Hold</span> - Tasks temporarily paused</li>
                                <li class="mb-2"><span class="badge bg-danger">Canceled</span> - Tasks that have been canceled</li>
                            </ul>
                            <p class="mb-3">
                                To change a task's status:
                            </p>
                            <ol class="mb-4">
                                <li class="mb-2">Open the task details</li>
                                <li class="mb-2">Click the "Change Status" dropdown</li>
                                <li class="mb-2">Select the new status</li>
                                <li class="mb-2">Provide a comment about the status change (optional)</li>
                                <li class="mb-2">Click "Update Status"</li>
                            </ol>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h3 fw-bold mb-3" id="filters">Filtering and Searching</h2>
                            <p class="mb-3">
                                The task manager provides powerful filtering and search capabilities to help you find specific tasks:
                            </p>
                            
                            <h3 class="h5 fw-bold mb-3">Basic Search</h3>
                            <p class="mb-3">
                                Use the search bar at the top of the task list to quickly find tasks by title or description.
                            </p>
                            
                            <h3 class="h5 fw-bold mb-3">Advanced Filtering</h3>
                            <p class="mb-3">
                                The advanced filter panel allows you to filter tasks by:
                            </p>
                            <ul class="mb-4">
                                <li class="mb-2">Status (Pending, In Progress, Completed, etc.)</li>
                                <li class="mb-2">Priority (High, Medium, Low)</li>
                                <li class="mb-2">Assignee</li>
                                <li class="mb-2">Due date range</li>
                                <li class="mb-2">Tags</li>
                                <li class="mb-2">Creation date range</li>
                            </ul>
                            <p class="mb-3">
                                To use advanced filtering:
                            </p>
                            <ol class="mb-4">
                                <li class="mb-2">Click the "Filters" button above the task list</li>
                                <li class="mb-2">Select the desired filter criteria</li>
                                <li class="mb-2">Click "Apply Filters" to update the task list</li>
                                <li class="mb-2">Click "Save Filter" to save this filter configuration for future use</li>
                            </ol>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h3 fw-bold mb-3" id="reports">Reports and Analytics</h2>
                            <p class="mb-3">
                                Task Manager provides comprehensive reporting tools to help you track progress and productivity:
                            </p>
                            
                            <h3 class="h5 fw-bold mb-3">Task Report</h3>
                            <p class="mb-3">
                                The Task Report provides an overview of all tasks in the system with statistics on:
                            </p>
                            <ul class="mb-4">
                                <li class="mb-2">Total number of tasks by status</li>
                                <li class="mb-2">Task distribution by priority</li>
                                <li class="mb-2">Tasks grouped by assignee</li>
                                <li class="mb-2">Tasks grouped by tags</li>
                                <li class="mb-2">Completion rate over time</li>
                            </ul>
                            <p class="mb-3">
                                To access the Task Report, click "Reports" in the main navigation and select "Task Report".
                            </p>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h3 fw-bold mb-3" id="keyboard-shortcuts">Keyboard Shortcuts</h2>
                            <p class="mb-3">
                                Task Manager provides keyboard shortcuts for common actions to improve productivity:
                            </p>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h3 class="h5 fw-bold mb-2">Navigation</h3>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><kbd>G</kbd> + <kbd>D</kbd> - Go to Dashboard</li>
                                        <li class="mb-2"><kbd>G</kbd> + <kbd>T</kbd> - Go to Tasks</li>
                                        <li class="mb-2"><kbd>G</kbd> + <kbd>R</kbd> - Go to Reports</li>
                                        <li class="mb-2"><kbd>G</kbd> + <kbd>S</kbd> - Go to Settings</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h3 class="h5 fw-bold mb-2">Task Actions</h3>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><kbd>N</kbd> - Create New Task</li>
                                        <li class="mb-2"><kbd>E</kbd> - Edit Current Task</li>
                                        <li class="mb-2"><kbd>A</kbd> - Assign Task</li>
                                        <li class="mb-2"><kbd>S</kbd> - Change Status</li>
                                        <li class="mb-2"><kbd>/</kbd> - Focus Search</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                        <a href="{{ route('documentation.getting-started') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Getting Started
                        </a>
                        <a href="{{ route('documentation.api') }}" class="btn btn-primary">
                            Next: API Documentation <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 