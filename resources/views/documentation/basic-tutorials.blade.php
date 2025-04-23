@extends('tasks.layout')

@section('title', 'Basic Tutorials - Documentation')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="{{ route('documentation.index') }}" class="list-group-item list-group-item-action">Overview</a>
                <a href="{{ route('documentation.getting-started') }}" class="list-group-item list-group-item-action">Getting Started</a>
                <a href="{{ route('documentation.basic-tutorials') }}" class="list-group-item list-group-item-action active">Basic Tutorials</a>
                <a href="{{ route('documentation.advanced-tutorials') }}" class="list-group-item list-group-item-action">Advanced Tutorials</a>
                <a href="{{ route('documentation.user-guide') }}" class="list-group-item list-group-item-action">User Guide</a>
                <a href="{{ route('documentation.integration') }}" class="list-group-item list-group-item-action">Integration</a>
                <a href="{{ route('documentation.github') }}" class="list-group-item list-group-item-action">GitHub</a>
                <a href="{{ route('documentation.api') }}" class="list-group-item list-group-item-action">API</a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h1>Basic Tutorials</h1>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        These tutorials will guide you through the most common tasks you'll perform in Task Manager. They're perfect for new users who want to learn the basics.
                    </div>

                    <!-- Tutorial Navigation -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card border-light">
                                <div class="card-body">
                                    <h3>Quick Navigation</h3>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <a href="#create-tasks" class="btn btn-outline-primary w-100 mb-2">Creating Tasks</a>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="#manage-tasks" class="btn btn-outline-primary w-100 mb-2">Managing Tasks</a>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="#task-lists" class="btn btn-outline-primary w-100 mb-2">Task Lists</a>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="#filtering" class="btn btn-outline-primary w-100 mb-2">Filtering &amp; Sorting</a>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="#task-comments" class="btn btn-outline-primary w-100 mb-2">Task Comments</a>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="#reporting" class="btn btn-outline-primary w-100 mb-2">Basic Reporting</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Creating Tasks -->
                    <section id="create-tasks" class="mb-5">
                        <div class="card">
                            <div class="card-header">
                                <h2>Creating Tasks</h2>
                            </div>
                            <div class="card-body">
                                <p>Tasks are the core element of Task Manager. Here's how to create them:</p>

                                <div class="steps">
                                    <h4 class="mt-4">Method 1: Quick Add</h4>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <ol>
                                                <li>From any page, click the <span class="badge bg-primary">+ New Task</span> button in the navigation bar</li>
                                                <li>Enter a task title (required)</li>
                                                <li>Optionally select:
                                                    <ul>
                                                        <li>Priority (Low, Medium, High)</li>
                                                        <li>Due date</li>
                                                        <li>Assignee</li>
                                                    </ul>
                                                </li>
                                                <li>Click <span class="badge bg-success">Create</span> to add the task</li>
                                            </ol>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card">
                                                <div class="card-body bg-light">
                                                    <p><strong>Pro Tip:</strong> Use keyboard shortcut <kbd>N</kbd> from any page to open the Quick Add dialog.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <h4 class="mt-4">Method 2: Full Task Creation</h4>
                                    <div class="row">
                                        <div class="col-md-7">
                                            <ol>
                                                <li>Navigate to <span class="text-primary">Tasks</span> in the sidebar</li>
                                                <li>Click <span class="badge bg-primary">Create Task</span></li>
                                                <li>Complete all relevant fields:
                                                    <ul>
                                                        <li><strong>Title:</strong> Clear, descriptive task name</li>
                                                        <li><strong>Description:</strong> Details about requirements</li>
                                                        <li><strong>Status:</strong> Initial status (default: Pending)</li>
                                                        <li><strong>Priority:</strong> Task importance</li>
                                                        <li><strong>Assignee:</strong> Person responsible</li>
                                                        <li><strong>Due Date:</strong> Completion deadline</li>
                                                        <li><strong>Features:</strong> Associated features</li>
                                                        <li><strong>Phase:</strong> Project phase</li>
                                                        <li><strong>Tags:</strong> Categorization labels</li>
                                                    </ul>
                                                </li>
                                                <li>Click <span class="badge bg-success">Create Task</span> to save</li>
                                            </ol>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="card">
                                                <div class="card-header">Important Fields</div>
                                                <div class="card-body bg-light">
                                                    <dl>
                                                        <dt>Title</dt>
                                                        <dd>Keep it concise but descriptive</dd>
                                                        
                                                        <dt>Description</dt>
                                                        <dd>Include acceptance criteria when possible</dd>
                                                        
                                                        <dt>Priority</dt>
                                                        <dd>Affects sorting and visibility</dd>
                                                        
                                                        <dt>Tags</dt>
                                                        <dd>Make filtering easier later</dd>
                                                    </dl>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <h4 class="mt-4">Method 3: Bulk Import</h4>
                                    <p>For creating multiple tasks at once:</p>
                                    <ol>
                                        <li>Navigate to <span class="text-primary">Tasks</span> in the sidebar</li>
                                        <li>Click <span class="badge bg-secondary">Import</span></li>
                                        <li>Select import format:
                                            <ul>
                                                <li><strong>CSV:</strong> Upload a CSV file with columns for task attributes</li>
                                                <li><strong>JSON:</strong> Use Task Manager's JSON format</li>
                                            </ul>
                                        </li>
                                        <li>Map fields if using CSV</li>
                                        <li>Click <span class="badge bg-success">Import Tasks</span></li>
                                    </ol>
                                    
                                    <div class="card mt-3">
                                        <div class="card-header">CSV Template</div>
                                        <div class="card-body bg-light">
                                            <pre>title,description,priority,status,due_date,assignee
"Setup database","Install and configure PostgreSQL","high","pending","2023-12-15","john@example.com"
"Create login page","Implement user authentication","medium","pending","2023-12-20","sarah@example.com"</pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Managing Tasks -->
                    <section id="manage-tasks" class="mb-5">
                        <div class="card">
                            <div class="card-header">
                                <h2>Managing Tasks</h2>
                            </div>
                            <div class="card-body">
                                <p>Learn how to update, edit, and track your tasks effectively:</p>
                                
                                <h4 class="mt-4">Viewing Task Details</h4>
                                <p>To see complete information about a task:</p>
                                <ol>
                                    <li>Go to <span class="text-primary">Tasks</span> in the sidebar</li>
                                    <li>Click on any task title in the list</li>
                                    <li>The task detail page shows all information including:
                                        <ul>
                                            <li>All basic fields (title, description, etc.)</li>
                                            <li>Activity history</li>
                                            <li>Comments</li>
                                            <li>Attachments</li>
                                            <li>Related tasks</li>
                                        </ul>
                                    </li>
                                </ol>

                                <h4 class="mt-4">Updating Task Status</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p>Method 1: Quick Status Update</p>
                                        <ol>
                                            <li>From the task list, find the Status column</li>
                                            <li>Click on the current status</li>
                                            <li>Select the new status from the dropdown</li>
                                            <li>Changes are saved automatically</li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <p>Method 2: From Task Detail</p>
                                        <ol>
                                            <li>Open the task detail page</li>
                                            <li>Click the <span class="badge bg-secondary">Change Status</span> button</li>
                                            <li>Select the new status</li>
                                            <li>Optionally add a comment about the status change</li>
                                            <li>Click <span class="badge bg-success">Update</span></li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="card mt-4 mb-4">
                                    <div class="card-header">Understanding Task Statuses</div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Status</th>
                                                        <th>Description</th>
                                                        <th>When to Use</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><span class="badge bg-secondary">Pending</span></td>
                                                        <td>Task has been created but work hasn't started</td>
                                                        <td>For new tasks that are not yet being worked on</td>
                                                    </tr>
                                                    <tr>
                                                        <td><span class="badge bg-primary">In Progress</span></td>
                                                        <td>Work has begun on the task</td>
                                                        <td>When you're actively working on the task</td>
                                                    </tr>
                                                    <tr>
                                                        <td><span class="badge bg-warning">Under Review</span></td>
                                                        <td>Task is completed and awaiting review</td>
                                                        <td>After completing work but before final approval</td>
                                                    </tr>
                                                    <tr>
                                                        <td><span class="badge bg-success">Completed</span></td>
                                                        <td>Task is fully completed and approved</td>
                                                        <td>After task passes all reviews and is finalized</td>
                                                    </tr>
                                                    <tr>
                                                        <td><span class="badge bg-danger">Blocked</span></td>
                                                        <td>Task cannot proceed due to external factors</td>
                                                        <td>When something prevents progress on the task</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <h4 class="mt-4">Editing Task Details</h4>
                                <p>To modify task information:</p>
                                <ol>
                                    <li>Open the task detail page</li>
                                    <li>Click the <span class="badge bg-primary">Edit</span> button</li>
                                    <li>Modify any fields as needed</li>
                                    <li>Click <span class="badge bg-success">Save Changes</span></li>
                                </ol>
                                
                                <div class="card mt-3">
                                    <div class="card-body bg-light">
                                        <p><strong>Note:</strong> All changes are logged in the task's activity history, making it easy to track what changed, when, and by whom.</p>
                                    </div>
                                </div>

                                <h4 class="mt-4">Deleting Tasks</h4>
                                <p>If you need to remove a task:</p>
                                <ol>
                                    <li>Open the task detail page</li>
                                    <li>Click <span class="badge bg-danger">Delete</span></li>
                                    <li>Confirm the deletion in the prompt</li>
                                </ol>
                                
                                <div class="alert alert-warning">
                                    <strong>Warning:</strong> Task deletion is permanent and cannot be undone. Consider marking tasks as completed instead of deleting them to maintain a complete project history.
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Task Lists -->
                    <section id="task-lists" class="mb-5">
                        <div class="card">
                            <div class="card-header">
                                <h2>Working with Task Lists</h2>
                            </div>
                            <div class="card-body">
                                <p>Task Manager provides different ways to view and organize your tasks:</p>

                                <h4 class="mt-4">List View</h4>
                                <p>The default view shows tasks in a tabular format:</p>
                                <ul>
                                    <li>Navigate to <span class="text-primary">Tasks</span> in the sidebar</li>
                                    <li>Tasks are displayed in a table with columns for key attributes</li>
                                    <li>Click any column header to sort by that attribute</li>
                                    <li>Use the view selector in the top-right to switch views</li>
                                </ul>

                                <h4 class="mt-4">Kanban Board</h4>
                                <p>Visualize tasks by status in a board format:</p>
                                <ol>
                                    <li>Navigate to <span class="text-primary">Tasks</span> in the sidebar</li>
                                    <li>Click <span class="badge bg-secondary">Board</span> in the view selector</li>
                                    <li>Tasks are grouped into columns by status</li>
                                    <li>Drag and drop tasks between columns to update their status</li>
                                    <li>Click on any task card to view or edit details</li>
                                </ol>
                                
                                <div class="card mt-3 mb-4">
                                    <div class="card-body bg-light">
                                        <p><strong>Pro Tip:</strong> The Kanban board is ideal for daily standups or when you want a visual overview of task progress across your team.</p>
                                    </div>
                                </div>

                                <h4 class="mt-4">Calendar View</h4>
                                <p>View tasks organized by due dates:</p>
                                <ol>
                                    <li>Navigate to <span class="text-primary">Tasks</span> in the sidebar</li>
                                    <li>Click <span class="badge bg-secondary">Calendar</span> in the view selector</li>
                                    <li>Tasks appear on their due dates in a monthly calendar</li>
                                    <li>Click on a date to see all tasks due that day</li>
                                    <li>Click on any task to view or edit details</li>
                                    <li>Drag and drop tasks to change due dates</li>
                                </ol>
                                
                                <h4 class="mt-4">Saved Views</h4>
                                <p>Create custom views for frequent task combinations:</p>
                                <ol>
                                    <li>Apply filters and sorting as needed</li>
                                    <li>Click <span class="badge bg-primary">Save View</span></li>
                                    <li>Give your view a name (e.g., "High Priority Tasks")</li>
                                    <li>Choose whether to make it private or share with team</li>
                                    <li>Click <span class="badge bg-success">Save</span></li>
                                </ol>
                                <p>To access saved views:</p>
                                <ul>
                                    <li>Click <span class="badge bg-secondary">Views</span> in the sidebar</li>
                                    <li>Select your saved view from the dropdown</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <!-- Filtering and Sorting -->
                    <section id="filtering" class="mb-5">
                        <div class="card">
                            <div class="card-header">
                                <h2>Filtering &amp; Sorting Tasks</h2>
                            </div>
                            <div class="card-body">
                                <p>Find exactly the tasks you need with powerful filtering options:</p>

                                <h4 class="mt-4">Basic Filtering</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p>Use the filter bar above the task list:</p>
                                        <ol>
                                            <li>Navigate to <span class="text-primary">Tasks</span> in the sidebar</li>
                                            <li>Click <span class="badge bg-secondary">Filter</span></li>
                                            <li>Select filters from the dropdown:
                                                <ul>
                                                    <li>Status</li>
                                                    <li>Priority</li>
                                                    <li>Assignee</li>
                                                    <li>Due date range</li>
                                                    <li>Tags</li>
                                                </ul>
                                            </li>
                                            <li>Click <span class="badge bg-primary">Apply Filters</span></li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body bg-light">
                                                <p><strong>Common Filter Combinations:</strong></p>
                                                <ul>
                                                    <li>"My Tasks": Assignee = [Your Name]</li>
                                                    <li>"Due Soon": Due Date = Next 7 Days</li>
                                                    <li>"High Priority Backlog": Status = Pending, Priority = High</li>
                                                    <li>"Ready for Review": Status = Under Review</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <h4 class="mt-4">Advanced Filtering</h4>
                                <p>For more complex queries:</p>
                                <ol>
                                    <li>Click <span class="badge bg-secondary">Advanced Filter</span></li>
                                    <li>Build your query using AND/OR conditions</li>
                                    <li>Add multiple filter groups for complex logic</li>
                                    <li>Click <span class="badge bg-primary">Apply</span></li>
                                </ol>
                                
                                <div class="card mt-3">
                                    <div class="card-header">Example Advanced Filter</div>
                                    <div class="card-body bg-light">
                                        <p>Find tasks that match:</p>
                                        <pre>(Status = "In Progress" OR Status = "Under Review") 
  AND 
(Priority = "High") 
  AND 
(Due Date < "2023-12-31")
  AND
(Tags CONTAINS "frontend" OR Tags CONTAINS "API")</pre>
                                    </div>
                                </div>

                                <h4 class="mt-4">Search</h4>
                                <p>Find tasks by keyword:</p>
                                <ol>
                                    <li>Use the search box at the top of any task list</li>
                                    <li>Enter keywords from task title, description, or comments</li>
                                    <li>Press Enter to search</li>
                                    <li>Combine with filters for more specific results</li>
                                </ol>
                                
                                <div class="card mt-3">
                                    <div class="card-body bg-light">
                                        <p><strong>Search Tip:</strong> Search supports basic operators like quotation marks for exact phrases and minus sign to exclude terms.</p>
                                        <p>Examples:</p>
                                        <ul>
                                            <li><code>"login page"</code> - Find tasks with this exact phrase</li>
                                            <li><code>database -migration</code> - Find tasks about databases but not migrations</li>
                                        </ul>
                                    </div>
                                </div>

                                <h4 class="mt-4">Sorting Tasks</h4>
                                <p>Arrange tasks in your preferred order:</p>
                                <ul>
                                    <li>Click any column header to sort by that attribute</li>
                                    <li>Click again to reverse the sort order</li>
                                    <li>Hold Shift and click multiple columns for multi-level sorting</li>
                                </ul>
                                
                                <div class="card mt-3">
                                    <div class="card-body bg-light">
                                        <p><strong>Common Sorting Patterns:</strong></p>
                                        <ul>
                                            <li>Priority (descending) then Due Date (ascending): Shows most important and urgent tasks first</li>
                                            <li>Status then Assignee: Groups tasks by status, then by team member</li>
                                            <li>Created Date (descending): Shows newest tasks first</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Task Comments -->
                    <section id="task-comments" class="mb-5">
                        <div class="card">
                            <div class="card-header">
                                <h2>Working with Task Comments</h2>
                            </div>
                            <div class="card-body">
                                <p>Comments help teams collaborate and keep track of discussions related to each task:</p>

                                <h4 class="mt-4">Adding Comments</h4>
                                <ol>
                                    <li>Open the task detail page</li>
                                    <li>Scroll to the Comments section</li>
                                    <li>Type your comment in the text box</li>
                                    <li>Use the formatting toolbar for text formatting:
                                        <ul>
                                            <li>Bold, italic, underline</li>
                                            <li>Lists (bullet and numbered)</li>
                                            <li>Code snippets</li>
                                            <li>Links</li>
                                        </ul>
                                    </li>
                                    <li>Click <span class="badge bg-primary">Post Comment</span></li>
                                </ol>

                                <div class="card mt-3 mb-4">
                                    <div class="card-body bg-light">
                                        <p><strong>Markdown Support:</strong> Comments support Markdown syntax for formatting:</p>
                                        <ul>
                                            <li><code>**bold**</code> for <strong>bold text</strong></li>
                                            <li><code>*italic*</code> for <em>italic text</em></li>
                                            <li><code>- item</code> for bullet lists</li>
                                            <li><code>`code`</code> for <code>inline code</code></li>
                                            <li><code>```code block```</code> for code blocks</li>
                                        </ul>
                                    </div>
                                </div>

                                <h4 class="mt-4">Mentioning Team Members</h4>
                                <p>To notify specific people:</p>
                                <ol>
                                    <li>Type @ in your comment</li>
                                    <li>Select a team member from the popup list</li>
                                    <li>They'll receive a notification about the mention</li>
                                </ol>

                                <h4 class="mt-4">Attaching Files</h4>
                                <p>Share relevant documents, images, or files:</p>
                                <ol>
                                    <li>Click the paperclip icon in the comment box</li>
                                    <li>Select files from your computer</li>
                                    <li>Optionally add a description</li>
                                    <li>Files will be attached to your comment</li>
                                </ol>
                                
                                <div class="alert alert-info mt-3">
                                    <p><strong>Supported file types:</strong> Images (PNG, JPG, GIF), Documents (PDF, DOCX, TXT), and other common formats up to 10MB per file.</p>
                                </div>

                                <h4 class="mt-4">Editing and Deleting Comments</h4>
                                <p>To modify your comments after posting:</p>
                                <ul>
                                    <li>Hover over your comment</li>
                                    <li>Click the three dots (...) to open the actions menu</li>
                                    <li>Select <span class="text-primary">Edit</span> or <span class="text-danger">Delete</span></li>
                                    <li>For edits, make your changes and click <span class="badge bg-success">Update</span></li>
                                    <li>For deletions, confirm your choice</li>
                                </ul>
                                
                                <div class="card mt-3">
                                    <div class="card-body bg-light">
                                        <p><strong>Note:</strong> Edited comments show an "(edited)" indicator. Comment history is available to administrators.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Basic Reporting -->
                    <section id="reporting" class="mb-5">
                        <div class="card">
                            <div class="card-header">
                                <h2>Basic Reporting</h2>
                            </div>
                            <div class="card-body">
                                <p>Task Manager includes simple reporting tools to help you analyze task data:</p>

                                <h4 class="mt-4">Dashboard Overview</h4>
                                <p>The main dashboard provides at-a-glance metrics:</p>
                                <ol>
                                    <li>Navigate to <span class="text-primary">Dashboard</span> in the sidebar</li>
                                    <li>View key metrics:
                                        <ul>
                                            <li>Total tasks by status</li>
                                            <li>Tasks due soon</li>
                                            <li>Recent activity</li>
                                            <li>Overdue tasks</li>
                                        </ul>
                                    </li>
                                    <li>Use the date range selector to adjust the time period</li>
                                </ol>

                                <h4 class="mt-4">Task Report</h4>
                                <p>For more detailed analysis:</p>
                                <ol>
                                    <li>Navigate to <span class="text-primary">Reports</span> in the sidebar</li>
                                    <li>Select <span class="badge bg-secondary">Task Report</span></li>
                                    <li>Choose report parameters:
                                        <ul>
                                            <li>Date range</li>
                                            <li>User(s)</li>
                                            <li>Task status</li>
                                            <li>Priority</li>
                                        </ul>
                                    </li>
                                    <li>Click <span class="badge bg-primary">Generate Report</span></li>
                                </ol>

                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <h4>Exporting Reports</h4>
                                        <p>Share or save report data:</p>
                                        <ol>
                                            <li>Generate any report</li>
                                            <li>Click <span class="badge bg-secondary">Export</span></li>
                                            <li>Choose your preferred format:
                                                <ul>
                                                    <li>CSV (for spreadsheet analysis)</li>
                                                    <li>PDF (for sharing and printing)</li>
                                                    <li>JSON (for data integration)</li>
                                                </ul>
                                            </li>
                                            <li>Open or save the exported file</li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <h4>Scheduling Reports</h4>
                                        <p>Automate report generation:</p>
                                        <ol>
                                            <li>Configure any report</li>
                                            <li>Click <span class="badge bg-primary">Schedule</span></li>
                                            <li>Set frequency (daily, weekly, monthly)</li>
                                            <li>Add email recipients</li>
                                            <li>Click <span class="badge bg-success">Save Schedule</span></li>
                                        </ol>
                                    </div>
                                </div>

                                <div class="card mt-4">
                                    <div class="card-header">Common Report Types</div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Report</th>
                                                        <th>Description</th>
                                                        <th>Best For</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Task Status Summary</td>
                                                        <td>Count of tasks by status</td>
                                                        <td>Overall project health</td>
                                                    </tr>
                                                    <tr>
                                                        <td>User Workload</td>
                                                        <td>Tasks assigned per user</td>
                                                        <td>Team capacity planning</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Completion Rate</td>
                                                        <td>Tasks completed vs. created over time</td>
                                                        <td>Team productivity</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Overdue Tasks</td>
                                                        <td>Tasks past their due date</td>
                                                        <td>Risk assessment</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Next Steps -->
                    <div class="card mt-5">
                        <div class="card-body">
                            <h2>Next Steps</h2>
                            <p>Now that you've mastered the basics, continue your learning journey:</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-graduation-cap fa-3x mb-3"></i>
                                            <h5>Advanced Tutorials</h5>
                                            <p>Learn advanced features like task dependencies, automation, and integrations</p>
                                            <a href="{{ route('documentation.advanced-tutorials') }}" class="btn btn-sm btn-outline-primary">Go to Advanced Tutorials</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-book fa-3x mb-3"></i>
                                            <h5>User Guide</h5>
                                            <p>Comprehensive reference of all Task Manager features</p>
                                            <a href="{{ route('documentation.user-guide') }}" class="btn btn-sm btn-outline-primary">View User Guide</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card h-100">
                                        <div class="card-body text-center">
                                            <i class="fab fa-github fa-3x mb-3"></i>
                                            <h5>GitHub Integration</h5>
                                            <p>Connect Task Manager with your GitHub repositories</p>
                                            <a href="{{ route('documentation.github') }}" class="btn btn-sm btn-outline-primary">Set Up GitHub</a>
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