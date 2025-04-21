@extends('tasks.layout')

@section('title', 'Task Dashboard')

@section('content')
<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tasks text-primary me-2"></i>Task Dashboard
        </h1>
        <div>
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> New Task
            </a>
            <a href="{{ route('tasks.report') }}" class="btn btn-outline-primary ms-2">
                <i class="fas fa-chart-bar me-1"></i> Reports
            </a>
            <a href="{{ route('tasks.index') }}?sync_to_github=all" class="btn btn-dark ms-2">
                <i class="fab fa-github me-1"></i> Sync to GitHub
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Tasks</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalTasks ?? count($tasks) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
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
                                Completed Tasks</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $completedTasks }}</div>
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
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">{{ $inProgressTasks }}</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $inProgressPercentage }}%"></div>
                                    </div>
                                </div>
                            </div>
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
                                Pending Tasks</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pendingTasks }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                        </div>
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
                    <h6 class="m-0 font-weight-bold text-primary">Tasks</h6>
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
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('tasks.index') }}">All Tasks</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">By Status</h6></li>
                                <li><a class="dropdown-item" href="{{ route('tasks.index', ['status' => 'pending']) }}">Pending</a></li>
                                <li><a class="dropdown-item" href="{{ route('tasks.index', ['status' => 'in-progress']) }}">In Progress</a></li>
                                <li><a class="dropdown-item" href="{{ route('tasks.index', ['status' => 'review']) }}">Review</a></li>
                                <li><a class="dropdown-item" href="{{ route('tasks.index', ['status' => 'completed']) }}">Completed</a></li>
                                <li><a class="dropdown-item" href="{{ route('tasks.index', ['status' => 'blocked']) }}">Blocked</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">By Priority</h6></li>
                                <li><a class="dropdown-item" href="{{ route('tasks.index', ['priority' => 'high']) }}">High</a></li>
                                <li><a class="dropdown-item" href="{{ route('tasks.index', ['priority' => 'medium']) }}">Medium</a></li>
                                <li><a class="dropdown-item" href="{{ route('tasks.index', ['priority' => 'low']) }}">Low</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header">By Assignee</h6></li>
                                <li><a class="dropdown-item" href="{{ route('tasks.index', ['assignee' => 'user']) }}">User</a></li>
                                <li><a class="dropdown-item" href="{{ route('tasks.index', ['assignee' => 'ai']) }}">AI</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">#</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Assignee</th>
                                    <th>Due Date</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasks as $task)
                                <tr>
                                    <td class="ps-3">{{ $task->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="task-icon me-2">
                                                @if($task->status === 'completed')
                                                    <i class="fas fa-check-circle text-success"></i>
                                                @elseif($task->status === 'in-progress')
                                                    <i class="fas fa-spinner text-info"></i>
                                                @elseif($task->status === 'blocked')
                                                    <i class="fas fa-ban text-danger"></i>
                                                @elseif($task->status === 'review')
                                                    <i class="fas fa-search text-primary"></i>
                                                @else
                                                    <i class="fas fa-circle text-secondary"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <a href="{{ route('tasks.show', $task->id) }}" class="fw-medium text-dark">
                                                    {{ $task->title }}
                                                </a>
                                                @if(isset($task->version) && $task->version)
                                                    <span class="badge bg-secondary ms-1">v{{ $task->version }}</span>
                                                @endif
                                                <div class="small text-muted">
                                                    @if(isset($task->related_feature) && $task->related_feature)
                                                        <span class="text-muted">{{ $task->related_feature }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            @if($task->status == 'completed') bg-success 
                                            @elseif($task->status == 'in-progress') bg-info 
                                            @elseif($task->status == 'blocked') bg-danger
                                            @elseif($task->status == 'review') bg-primary
                                            @else bg-secondary @endif">
                                            {{ ucfirst($task->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            @if($task->priority == 'high') bg-danger 
                                            @elseif($task->priority == 'medium') bg-warning text-dark
                                            @else bg-info @endif">
                                            {{ ucfirst($task->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge @if($task->assignee == 'ai') bg-purple @else bg-primary @endif">
                                            {{ ucfirst($task->assignee) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if(isset($task->due_date) && $task->due_date)
                                            <span class="
                                                @if($task->due_date->isPast() && $task->status != 'completed') 
                                                    text-danger fw-bold
                                                @elseif($task->due_date->isToday()) 
                                                    text-warning fw-bold
                                                @endif
                                            ">
                                                {{ $task->due_date->format('M d, Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group">
                                            <a href="{{ route('tasks.show', $task->id) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               data-bs-toggle="tooltip" 
                                               title="View Task">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('tasks.edit', $task->id) }}" 
                                               class="btn btn-sm btn-outline-secondary"
                                               data-bs-toggle="tooltip" 
                                               title="Edit Task">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteTaskModal{{ $task->id }}"
                                                    data-bs-toggle="tooltip" 
                                                    title="Delete Task">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteTaskModal{{ $task->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Delete Task</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete the task: <strong>{{ $task->title }}</strong>?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                            <h5>No tasks found</h5>
                                            <p class="text-muted">There are no tasks that match your criteria.</p>
                                            <a href="{{ route('tasks.create') }}" class="btn btn-primary mt-2">
                                                <i class="fas fa-plus-circle me-1"></i> Create New Task
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
</style>
@endsection 