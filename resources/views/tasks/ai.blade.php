@extends('tasks.layout')

@section('title', 'AI Tasks')

@section('content')
<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-robot text-primary me-2"></i>AI Tasks
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">AI Tasks</li>
                </ol>
            </nav>
        </div>
        <button class="btn btn-primary generate-tasks-btn" id="generateTasksBtn">
            <i class="fas fa-sync me-1"></i> Generate AI Tasks
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total AI Tasks</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalAiTasks">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-robot fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Completed</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="completedAiTasks">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                In Progress</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="inProgressAiTasks">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-spinner fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="pendingAiTasks">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Tasks Section -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">AI-Generated Tasks</h6>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item filter-item" data-filter="all" href="#">All Tasks</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header">By Status</h6></li>
                    <li><a class="dropdown-item filter-item" data-filter="pending" href="#">Pending</a></li>
                    <li><a class="dropdown-item filter-item" data-filter="in-progress" href="#">In Progress</a></li>
                    <li><a class="dropdown-item filter-item" data-filter="completed" href="#">Completed</a></li>
                </ul>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="aiTasksTable">
                        <!-- AI tasks will be loaded here -->
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading AI tasks...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer" id="aiTasksFooter" style="display: none;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span id="aiTasksCount">0</span> tasks found
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="completeAllBtn">
                        <i class="fas fa-check-double me-1"></i> Complete All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Task System Information -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">How AI Tasks Work</h6>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <h5><i class="fas fa-magic text-primary me-2"></i>Automatic Task Generation</h5>
                <p>The system automatically generates tasks based on code changes detected in the repository. Tasks are created for:</p>
                <ul>
                    <li><strong>UI Improvements:</strong> When view files are modified</li>
                    <li><strong>Code Refactoring:</strong> When controllers or models are changed</li>
                    <li><strong>Bug Fixes:</strong> When commit messages contain "fix" or "bug"</li>
                </ul>
            </div>

            <div class="mb-4">
                <h5><i class="fas fa-code-branch text-primary me-2"></i>Auto-Completion via Commits</h5>
                <p>Tasks can be automatically completed by including special keywords in your commit messages:</p>
                <div class="bg-light p-3 border rounded">
                    <code>git commit -m "Fixed navbar styling, closes task #123"</code>
                </div>
                <p class="mt-2 mb-0">The system will detect <code>closes task #123</code> and automatically mark that task as completed.</p>
            </div>

            <div>
                <h5><i class="fas fa-terminal text-primary me-2"></i>Manual Task Generation</h5>
                <p>You can manually generate AI tasks at any time by clicking the "Generate AI Tasks" button above or running:</p>
                <div class="bg-light p-3 border rounded">
                    <code>php artisan tasks:generate-ai --analyze-git</code>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Load AI tasks
        loadAiTasks();
        
        // Event listeners
        document.getElementById('generateTasksBtn').addEventListener('click', generateAiTasks);
        document.getElementById('completeAllBtn').addEventListener('click', completeAllTasks);
        
        // Filter event listeners
        document.querySelectorAll('.filter-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const filter = this.dataset.filter;
                filterTasks(filter);
            });
        });
    });
    
    function loadAiTasks() {
        fetch('/ai-tasks')
            .then(response => response.json())
            .then(data => {
                // Update stats
                document.getElementById('totalAiTasks').textContent = data.stats.total;
                document.getElementById('completedAiTasks').textContent = data.stats.completed;
                document.getElementById('inProgressAiTasks').textContent = data.stats.in_progress;
                document.getElementById('pendingAiTasks').textContent = data.stats.pending;
                
                // Update table
                renderTasksTable(data.ai_tasks);
            })
            .catch(error => {
                console.error('Error loading AI tasks:', error);
                document.getElementById('aiTasksTable').innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="empty-state">
                                <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                                <h5>Error loading AI tasks</h5>
                                <p class="text-muted">There was a problem loading the AI tasks. Please try again later.</p>
                            </div>
                        </td>
                    </tr>
                `;
            });
    }
    
    function renderTasksTable(tasks) {
        const tableBody = document.getElementById('aiTasksTable');
        
        if (tasks.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <div class="empty-state">
                            <i class="fas fa-robot fa-3x text-muted mb-3"></i>
                            <h5>No AI tasks found</h5>
                            <p class="text-muted">Click "Generate AI Tasks" to create tasks based on code changes.</p>
                        </div>
                    </td>
                </tr>
            `;
            document.getElementById('aiTasksFooter').style.display = 'none';
            return;
        }
        
        let html = '';
        
        tasks.forEach(task => {
            html += `
                <tr data-status="${task.status}" data-priority="${task.priority}">
                    <td>${task.id}</td>
                    <td>
                        <a href="/tasks/${task.id}" class="fw-medium text-dark">
                            ${task.title}
                        </a>
                        ${task.version ? `<span class="badge bg-secondary ms-1">v${task.version}</span>` : ''}
                        ${task.tags.length > 0 ? `
                            <div class="mt-1">
                                ${task.tags.map(tag => `<span class="badge bg-light text-dark">#${tag}</span>`).join(' ')}
                            </div>
                        ` : ''}
                    </td>
                    <td>
                        <span class="badge ${getStatusBadgeClass(task.status)}">
                            ${capitalizeFirst(task.status)}
                        </span>
                    </td>
                    <td>
                        <span class="badge ${getPriorityBadgeClass(task.priority)}">
                            ${capitalizeFirst(task.priority)}
                        </span>
                    </td>
                    <td>
                        ${task.due_date ? formatDate(task.due_date) : '<span class="text-muted">—</span>'}
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="/tasks/${task.id}" class="btn btn-sm btn-outline-primary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-success" 
                                    onclick="markTaskCompleted(${task.id})" 
                                    ${task.status === 'completed' ? 'disabled' : ''}
                                    title="Complete">
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        tableBody.innerHTML = html;
        document.getElementById('aiTasksCount').textContent = tasks.length;
        document.getElementById('aiTasksFooter').style.display = 'block';
    }
    
    function generateAiTasks() {
        const btn = document.getElementById('generateTasksBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...';
        
        // Make an AJAX call to run the artisan command
        fetch('/api/generate-ai-tasks', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', `Generated ${data.tasks_created} new AI tasks successfully!`);
                loadAiTasks(); // Reload tasks
            } else {
                showAlert('danger', data.message || 'Failed to generate AI tasks');
            }
        })
        .catch(error => {
            console.error('Error generating AI tasks:', error);
            showAlert('danger', 'Error: Failed to generate AI tasks');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync me-1"></i> Generate AI Tasks';
        });
    }
    
    function markTaskCompleted(taskId) {
        fetch(`/api/complete-task/${taskId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', `Task #${taskId} marked as completed`);
                loadAiTasks(); // Reload tasks
            } else {
                showAlert('danger', data.message || 'Failed to complete task');
            }
        })
        .catch(error => {
            console.error('Error completing task:', error);
            showAlert('danger', 'Error: Failed to complete task');
        });
    }
    
    function completeAllTasks() {
        if (!confirm('Are you sure you want to mark all visible AI tasks as completed?')) {
            return;
        }
        
        const taskRows = document.querySelectorAll('#aiTasksTable tr[data-status="pending"], #aiTasksTable tr[data-status="in-progress"]');
        const taskIds = Array.from(taskRows).map(row => {
            const taskId = row.querySelector('td:first-child').textContent;
            return parseInt(taskId, 10);
        });
        
        if (taskIds.length === 0) {
            showAlert('info', 'No pending or in-progress tasks to complete');
            return;
        }
        
        fetch('/api/complete-all-tasks', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ task_ids: taskIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', `${data.completed_count} tasks marked as completed`);
                loadAiTasks(); // Reload tasks
            } else {
                showAlert('danger', data.message || 'Failed to complete tasks');
            }
        })
        .catch(error => {
            console.error('Error completing tasks:', error);
            showAlert('danger', 'Error: Failed to complete tasks');
        });
    }
    
    function filterTasks(filter) {
        const rows = document.querySelectorAll('#aiTasksTable tr[data-status]');
        
        rows.forEach(row => {
            if (filter === 'all' || row.dataset.status === filter) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update count
        const visibleRows = document.querySelectorAll('#aiTasksTable tr[data-status]:not([style*="display: none"])');
        document.getElementById('aiTasksCount').textContent = visibleRows.length;
    }
    
    // Helper functions
    function getStatusBadgeClass(status) {
        switch (status) {
            case 'completed': return 'bg-success';
            case 'in-progress': return 'bg-info';
            case 'review': return 'bg-primary';
            case 'blocked': return 'bg-danger';
            default: return 'bg-secondary';
        }
    }
    
    function getPriorityBadgeClass(priority) {
        switch (priority) {
            case 'high': return 'bg-danger';
            case 'medium': return 'bg-warning text-dark';
            default: return 'bg-info';
        }
    }
    
    function capitalizeFirst(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Insert at the top of the content
        document.querySelector('.container-fluid').prepend(alertDiv);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }, 5000);
    }
</script>
@endsection 