@extends('tasks.layout')

@section('title', 'Task Dashboard')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold m-0">Task Dashboard</h2>
        <div>
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> New Task
            </a>
            <a href="{{ route('tags.index') }}" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-tags me-1"></i> Tags
            </a>
            <a href="{{ route('tasks.report') }}" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-chart-bar me-1"></i> Reports
            </a>
            <a href="{{ route('tasks.index') }}?sync_to_github=all" class="btn btn-outline-dark ms-2">
                <i class="fab fa-github me-1"></i> Sync to GitHub
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-1">Total Tasks</h6>
                            <h2 class="mb-0 fw-bold">{{ $totalTasks ?? count($tasks) }}</h2>
                        </div>
                        <div class="p-2 rounded-circle bg-primary bg-opacity-10">
                            <i class="fas fa-clipboard-list text-primary"></i>
                        </div>
                    </div>
                    <div class="progress mt-4" style="height: 4px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-1">Completed</h6>
                            <h2 class="mb-0 fw-bold">{{ $completedTasks }}</h2>
                        </div>
                        <div class="p-2 rounded-circle bg-success bg-opacity-10">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                    </div>
                    <div class="progress mt-4" style="height: 4px;">
                        @php
                            $completedPercent = $totalTasks > 0 ? ($completedTasks / $totalTasks * 100) : 0;
                        @endphp
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $completedPercent }}%;" aria-valuenow="{{ $completedPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-1">In Progress</h6>
                            <h2 class="mb-0 fw-bold">{{ $inProgressTasks }}</h2>
                        </div>
                        <div class="p-2 rounded-circle bg-warning bg-opacity-10">
                            <i class="fas fa-spinner text-warning"></i>
                        </div>
                    </div>
                    <div class="progress mt-4" style="height: 4px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $inProgressPercentage }}%;" aria-valuenow="{{ $inProgressPercentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-1">Pending</h6>
                            <h2 class="mb-0 fw-bold">{{ $pendingTasks }}</h2>
                        </div>
                        <div class="p-2 rounded-circle bg-info bg-opacity-10">
                            <i class="fas fa-hourglass-half text-info"></i>
                        </div>
                    </div>
                    <div class="progress mt-4" style="height: 4px;">
                        @php
                            $pendingPercent = $totalTasks > 0 ? ($pendingTasks / $totalTasks * 100) : 0;
                        @endphp
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $pendingPercent }}%;" aria-valuenow="{{ $pendingPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task List Section -->
    <div class="row">
        <div class="col-12">
            <!-- Task List Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary">Tasks</h6>
                    <div class="d-flex">
                        <div class="me-3">
                            <span class="badge bg-success me-1">{{ $completedTasks }}</span> Completed
                            <span class="badge bg-info mx-1">{{ $inProgressTasks }}</span> In Progress
                            <span class="badge bg-secondary mx-1">{{ $pendingTasks }}</span> Pending
                            <span class="badge bg-danger mx-1">{{ $blockedTasks }}</span> Blocked
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-filter me-1"></i> Filter
                                @if(request()->has('status') || request()->has('priority') || request()->has('assignee') || request()->has('sort'))
                                <span class="badge bg-primary ms-1">Active</span>
                                @endif
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item {{ !request()->has('status') && !request()->has('priority') && !request()->has('assignee') && !request()->has('sort') ? 'active' : '' }}" href="{{ route('tasks.index') }}">All Tasks</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">By Status</h6></li>
                                <li><a class="dropdown-item {{ request('status') == 'pending' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}">Pending</a></li>
                                <li><a class="dropdown-item {{ request('status') == 'in-progress' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('status', 'page'), ['status' => 'in-progress'])) }}">In Progress</a></li>
                                <li><a class="dropdown-item {{ request('status') == 'review' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('status', 'page'), ['status' => 'review'])) }}">Review</a></li>
                                <li><a class="dropdown-item {{ request('status') == 'completed' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('status', 'page'), ['status' => 'completed'])) }}">Completed</a></li>
                                <li><a class="dropdown-item {{ request('status') == 'blocked' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('status', 'page'), ['status' => 'blocked'])) }}">Blocked</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">By Priority</h6></li>
                                <li><a class="dropdown-item {{ request('priority') == 'high' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('priority', 'page'), ['priority' => 'high'])) }}">High</a></li>
                                <li><a class="dropdown-item {{ request('priority') == 'medium' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('priority', 'page'), ['priority' => 'medium'])) }}">Medium</a></li>
                                <li><a class="dropdown-item {{ request('priority') == 'low' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('priority', 'page'), ['priority' => 'low'])) }}">Low</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">By Assignee</h6></li>
                                <li><a class="dropdown-item {{ request('assignee') == 'user' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('assignee', 'page'), ['assignee' => 'user'])) }}">User</a></li>
                                <li><a class="dropdown-item {{ request('assignee') == 'ai' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('assignee', 'page'), ['assignee' => 'ai'])) }}">AI</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">By Recency</h6></li>
                                <li><a class="dropdown-item {{ request('sort') == 'newest' || !request('sort') ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('sort', 'page'), ['sort' => 'newest'])) }}">Newest First</a></li>
                                <li><a class="dropdown-item {{ request('sort') == 'updated' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('sort', 'page'), ['sort' => 'updated'])) }}">Recently Updated</a></li>
                                @if(request()->has('status') || request()->has('priority') || request()->has('assignee') || request()->has('sort'))
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="{{ route('tasks.index') }}"><i class="fas fa-times me-1"></i> Clear All Filters</a></li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" width="5%">#</th>
                                    <th width="40%">Title</th>
                                    <th width="15%">Repository</th>
                                    <th width="10%">Status</th>
                                    <th width="10%">Priority</th>
                                    <th width="10%">Assignee</th>
                                    <th width="15%">Due Date</th>
                                    <th class="text-end pe-3" width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasks as $task)
                                <tr>
                                    <td class="ps-3">{{ $task['id'] }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="task-icon me-2">
                                                @if($task['status'] === 'completed')
                                                    <i class="fas fa-check-circle text-success"></i>
                                                @elseif($task['status'] === 'in-progress')
                                                    <i class="fas fa-spinner text-info"></i>
                                                @elseif($task['status'] === 'blocked')
                                                    <i class="fas fa-ban text-danger"></i>
                                                @elseif($task['status'] === 'review')
                                                    <i class="fas fa-search text-primary"></i>
                                                @else
                                                    <i class="fas fa-circle text-secondary"></i>
                                                @endif
                                            </div>
                                            <div class="task-title-container">
                                                <a href="{{ route('tasks.show', $task['id']) }}" class="fw-medium text-dark task-title">
                                                    {{ $task['title'] }}
                                                </a>
                                                @if(isset($task['version']) && $task['version'])
                                                    <span class="badge bg-secondary ms-1">v{{ $task['version'] }}</span>
                                                @endif
                                                <div class="small text-muted">
                                                    @if(isset($task['related_feature']) && $task['related_feature'])
                                                        <span class="text-muted">{{ $task['related_feature'] }}</span>
                                                    @endif
                                                    @if(isset($task['created_at']) && $task['created_at'])
                                                        <span class="text-muted ms-2">
                                                            <i class="far fa-clock me-1"></i>{{ \Carbon\Carbon::parse($task['created_at'])->diffForHumans() }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if(isset($task['repository']) && $task['repository'])
                                            <a href="{{ route('repositories.show', $task['repository']['id'] ?? 0) }}" class="badge bg-dark text-decoration-none">
                                                <i class="fas fa-code-branch me-1"></i>{{ $task['repository']['name'] ?? $task['repository'] }}
                                            </a>
                                        @else
                                            <span class="text-muted small">No repository</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge 
                                            @if($task['status'] == 'completed') bg-success 
                                            @elseif($task['status'] == 'in-progress') bg-info 
                                            @elseif($task['status'] == 'blocked') bg-danger
                                            @elseif($task['status'] == 'review') bg-primary
                                            @else bg-secondary @endif">
                                            {{ ucfirst($task['status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            @if($task['priority'] == 'high') bg-danger 
                                            @elseif($task['priority'] == 'medium') bg-warning 
                                            @elseif($task['priority'] == 'critical') bg-dark
                                            @else bg-info @endif">
                                            {{ ucfirst($task['priority']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge @if($task['assignee'] == 'ai') bg-purple @else bg-primary @endif">
                                            {{ ucfirst($task['assignee']) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if(isset($task['due_date']) && $task['due_date'])
                                            <span class="@if(strtotime($task['due_date']) < strtotime('today') && $task['status'] != 'completed') text-danger @endif">
                                                {{ \Carbon\Carbon::parse($task['due_date'])->format('M d, Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <a href="{{ route('tasks.show', $task['id']) }}" class="btn btn-sm btn-outline-primary" title="View Task">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No tasks found</h5>
                                            <p class="text-muted mb-3">Get started by creating your first task</p>
                                            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                                                <i class="fas fa-plus-circle me-1"></i> New Task
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if(count($tasks) > 0)
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            @if(isset($pagination))
                                Showing {{ ($pagination['current_page']-1) * $pagination['per_page'] + 1 }} to 
                                {{ min($pagination['current_page'] * $pagination['per_page'], $pagination['total']) }} 
                                of {{ $pagination['total'] }} entries
                            @else
                                Showing 1 to {{ count($tasks) }} of {{ count($tasks) }} entries
                            @endif
                            <span class="ms-2 text-muted small">(20 per page)</span>
                        </div>
                        <div class="d-flex align-items-center">
                            @php
                                // No need for filtering here since we have upcomingTasks from the controller
                                $upcomingTasksFiltered = $upcomingTasks;
                            @endphp
                            <button type="button" class="btn btn-sm btn-outline-info me-2" data-bs-toggle="tooltip" title="Upcoming Tasks (Due in 7 days)">
                                <i class="fas fa-calendar-day me-1"></i> {{ count($upcomingTasksFiltered) }} Upcoming
                            </button>
                            <a href="{{ route('tasks.report') }}" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-chart-bar me-1"></i> Reports
                            </a>
                        </div>
                    </div>
                    
                    @if(isset($pagination) && $pagination['total_pages'] > 1)
                    <div class="d-flex justify-content-center mt-3">
                        <nav aria-label="Task pagination">
                            <ul class="pagination">
                                <li class="page-item {{ $pagination['current_page'] == 1 ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ route('tasks.index', array_merge(request()->except('page'), ['page' => 1])) }}" aria-label="First">
                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item {{ $pagination['current_page'] == 1 ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ route('tasks.index', array_merge(request()->except('page'), ['page' => max($pagination['current_page'] - 1, 1)])) }}" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                @php
                                    $start = max(1, $pagination['current_page'] - 2);
                                    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                                    
                                    if ($start > 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                @endphp
                                
                                @for($i = $start; $i <= $end; $i++)
                                    <li class="page-item {{ $pagination['current_page'] == $i ? 'active' : '' }}">
                                        <a class="page-link" href="{{ route('tasks.index', array_merge(request()->except('page'), ['page' => $i])) }}">{{ $i }}</a>
                                    </li>
                                @endfor
                                
                                @php
                                    if ($end < $pagination['total_pages']) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                @endphp
                                
                                <li class="page-item {{ $pagination['current_page'] == $pagination['total_pages'] ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ route('tasks.index', array_merge(request()->except('page'), ['page' => min($pagination['current_page'] + 1, $pagination['total_pages'])])) }}" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                                <li class="page-item {{ $pagination['current_page'] == $pagination['total_pages'] ? 'disabled' : '' }}">
                                    <a class="page-link" href="{{ route('tasks.index', array_merge(request()->except('page'), ['page' => $pagination['total_pages']])) }}" aria-label="Last">
                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Charts and Statistics (Condensed) -->
    <div class="row">
        <div class="col-lg-6">
            <!-- Task Statistics Chart -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Task Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container mb-4">
                        <canvas id="taskStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <!-- Priority Distribution -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Priority Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="progress-tracker">
                        <div class="progress-item">
                            <div class="d-flex justify-content-between mb-1">
                                <span>High Priority</span>
                                <span class="text-danger">{{ $highPriorityTasks }}</span>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-danger" style="width: {{ $highPriorityPercentage }}%"></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Medium Priority</span>
                                <span class="text-warning">{{ $mediumPriorityTasks }}</span>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: {{ $mediumPriorityPercentage }}%"></div>
                            </div>
                        </div>
                        <div class="progress-item">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Low Priority</span>
                                <span class="text-info">{{ $lowPriorityTasks }}</span>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-info" style="width: {{ $lowPriorityPercentage }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Task Status Chart
    const statusCtx = document.getElementById('taskStatusChart').getContext('2d');
    
    @php
        // Use the pre-calculated values from the controller
        $completedCount = $completedTasks;
        $inProgressCount = $inProgressTasks;
        $pendingCount = $pendingTasks;
        $blockedCount = $blockedTasks;
        $reviewCount = $reviewTasks;
    @endphp
    
    const statusData = {
        labels: ['Completed', 'In Progress', 'Pending', 'Blocked', 'Review'],
        datasets: [{
            data: [
                {{ $completedCount }},
                {{ $inProgressCount }},
                {{ $pendingCount }},
                {{ $blockedCount }},
                {{ $reviewCount }}
            ],
            backgroundColor: ['#28a745', '#ffc107', '#6c757d', '#dc3545', '#17a2b8'],
            borderWidth: 0
        }]
    };
    
    new Chart(statusCtx, {
        type: 'doughnut',
        data: statusData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '70%'
        }
    });
    
    // Any other charts or JS functionality
});
</script>

<style>
    .badge-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }
    
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 2rem;
    }
    
    .bg-purple {
        background-color: #6f42c1;
    }
    
    .chart-container {
        position: relative;
        height: 200px;
    }
    
    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }
    
    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }
    
    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
    }
    
    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }
    
    .progress {
        overflow: hidden;
        height: 8px;
        margin-bottom: 1rem;
        border-radius: 0.25rem;
        background-color: #eaecf4;
    }
    
    .task-title-container {
        max-width: 100%;
    }
    
    .task-title {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        word-break: break-word;
        line-height: 1.3;
    }
</style>
@endsection 