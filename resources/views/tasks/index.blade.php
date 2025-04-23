@extends('tasks.layout')

@section('title', 'Task Dashboard')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center mb-2 mb-md-0">
            <h2 class="fw-bold m-0 me-3">Task Dashboard</h2>
            @if(request()->has('status') || request()->has('priority') || request()->has('assignee') || request()->has('repository') || request()->has('sort'))
            <div class="badge bg-primary px-3 py-2">
                Filtered View
                <a href="{{ route('tasks.index') }}" class="text-white ms-2" title="Clear all filters">
                    <i class="fas fa-times-circle"></i>
                </a>
            </div>
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> New Task
            </a>
            <div class="dropdown d-inline-block">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="moreActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-h"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="moreActionsDropdown">
                    <li><a class="dropdown-item" href="{{ route('tags.index') }}"><i class="fas fa-tags me-2"></i> Manage Tags</a></li>
                    <li><a class="dropdown-item" href="{{ route('tasks.report') }}"><i class="fas fa-chart-bar me-2"></i> Reports</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('tasks.index') }}?sync_to_github=all"><i class="fab fa-github me-2"></i> Sync to GitHub</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small text-uppercase">Total Tasks</div>
                            <div class="mt-1 d-flex align-items-baseline">
                                <h3 class="fw-bold mb-0">{{ $totalTasks ?? count($tasks) }}</h3>
                                <div class="ms-2 small text-muted">tasks</div>
                            </div>
                        </div>
                        <div class="rounded-circle p-2 bg-primary bg-opacity-10">
                            <i class="fas fa-tasks text-primary"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small text-uppercase">Completed</div>
                            <div class="mt-1 d-flex align-items-baseline">
                                <h3 class="fw-bold mb-0">{{ $completedTasks }}</h3>
                                @php
                                    $completedPercent = $totalTasks > 0 ? round(($completedTasks / $totalTasks * 100), 1) : 0;
                                @endphp
                                <div class="ms-2 small text-success">{{ $completedPercent }}%</div>
                            </div>
                        </div>
                        <div class="rounded-circle p-2 bg-success bg-opacity-10">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $completedPercent }}%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small text-uppercase">In Progress</div>
                            <div class="mt-1 d-flex align-items-baseline">
                                <h3 class="fw-bold mb-0">{{ $inProgressTasks }}</h3>
                                @php
                                    $inProgressPercent = $totalTasks > 0 ? round(($inProgressTasks / $totalTasks * 100), 1) : 0;
                                @endphp
                                <div class="ms-2 small text-warning">{{ $inProgressPercent }}%</div>
                            </div>
                        </div>
                        <div class="rounded-circle p-2 bg-warning bg-opacity-10">
                            <i class="fas fa-spinner text-warning"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $inProgressPercentage }}%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small text-uppercase">Pending</div>
                            <div class="mt-1 d-flex align-items-baseline">
                                <h3 class="fw-bold mb-0">{{ $pendingTasks }}</h3>
                                @php
                                    $pendingPercent = $totalTasks > 0 ? round(($pendingTasks / $totalTasks * 100), 1) : 0;
                                @endphp
                                <div class="ms-2 small text-info">{{ $pendingPercent }}%</div>
                            </div>
                        </div>
                        <div class="rounded-circle p-2 bg-info bg-opacity-10">
                            <i class="fas fa-hourglass-half text-info"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 4px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $pendingPercent }}%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task List Section -->
    <div class="row">
        <div class="col-12">
            <!-- Task List Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <h5 class="m-0 fw-bold text-primary">Tasks</h5>
                            <div class="ms-3 d-none d-md-flex">
                                <span class="badge bg-success rounded-pill me-1">{{ $completedTasks }}</span>
                                <span class="text-muted small me-3">Completed</span>
                                
                                <span class="badge bg-info rounded-pill me-1">{{ $inProgressTasks }}</span>
                                <span class="text-muted small me-3">In Progress</span>
                                
                                <span class="badge bg-secondary rounded-pill me-1">{{ $pendingTasks }}</span>
                                <span class="text-muted small me-3">Pending</span>
                                
                                <span class="badge bg-danger rounded-pill me-1">{{ $blockedTasks }}</span>
                                <span class="text-muted small">Blocked</span>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap mt-2 mt-md-0 gap-2">
                            <!-- Repository Filter Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-code-branch me-1"></i> Repository
                                    @if(request()->has('repository'))
                                    <span class="badge bg-primary ms-1">Active</span>
                                    @endif
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item {{ !request()->has('repository') ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('repository', 'page'), [])) }}">All Repositories</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    @foreach($repositories as $repo)
                                    <li><a class="dropdown-item {{ request('repository') == $repo->id ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('repository', 'page'), ['repository' => $repo->id])) }}">
                                        <span class="badge rounded-pill" style="background-color: {{ $repo->color ?? '#6c757d' }}">
                                            <i class="fas fa-code-branch me-1"></i>
                                        </span>
                                        {{ $repo->name }}
                                    </a></li>
                                    @endforeach
                                </ul>
                            </div>
                            
                            <!-- Status Filter Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-filter me-1"></i> Status
                                    @if(request()->has('status'))
                                    <span class="badge bg-primary ms-1">Active</span>
                                    @endif
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item {{ !request()->has('status') ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('status', 'page'), [])) }}">All Statuses</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item {{ request('status') == 'pending' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}">Pending</a></li>
                                    <li><a class="dropdown-item {{ request('status') == 'in-progress' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('status', 'page'), ['status' => 'in-progress'])) }}">In Progress</a></li>
                                    <li><a class="dropdown-item {{ request('status') == 'review' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('status', 'page'), ['status' => 'review'])) }}">Review</a></li>
                                    <li><a class="dropdown-item {{ request('status') == 'completed' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('status', 'page'), ['status' => 'completed'])) }}">Completed</a></li>
                                    <li><a class="dropdown-item {{ request('status') == 'blocked' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('status', 'page'), ['status' => 'blocked'])) }}">Blocked</a></li>
                                </ul>
                            </div>
                            
                            <!-- Priority Filter Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-flag me-1"></i> Priority
                                    @if(request()->has('priority'))
                                    <span class="badge bg-primary ms-1">Active</span>
                                    @endif
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item {{ !request()->has('priority') ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('priority', 'page'), [])) }}">All Priorities</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item {{ request('priority') == 'high' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('priority', 'page'), ['priority' => 'high'])) }}">High</a></li>
                                    <li><a class="dropdown-item {{ request('priority') == 'medium' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('priority', 'page'), ['priority' => 'medium'])) }}">Medium</a></li>
                                    <li><a class="dropdown-item {{ request('priority') == 'low' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('priority', 'page'), ['priority' => 'low'])) }}">Low</a></li>
                                </ul>
                            </div>
                            
                            <!-- Sort Order Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-sort me-1"></i> Sort
                                    @if(request()->has('sort'))
                                    <span class="badge bg-primary ms-1">Active</span>
                                    @endif
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item {{ request('sort') == 'newest' || !request('sort') ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('sort', 'page'), ['sort' => 'newest'])) }}">Newest First</a></li>
                                    <li><a class="dropdown-item {{ request('sort') == 'updated' ? 'active' : '' }}" href="{{ route('tasks.index', array_merge(request()->except('sort', 'page'), ['sort' => 'updated'])) }}">Recently Updated</a></li>
                                </ul>
                            </div>
                            
                            @if(request()->has('status') || request()->has('priority') || request()->has('assignee') || request()->has('repository') || request()->has('sort'))
                            <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-danger">
                                <i class="fas fa-times me-1"></i> Clear
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" width="5%">#</th>
                                    <th width="40%">Title</th>
                                    <th width="15%">Repository</th>
                                    <th width="10%" class="text-center">Status</th>
                                    <th width="10%" class="text-center">Priority</th>
                                    <th width="10%" class="text-center">Assignee</th>
                                    <th width="15%">Due Date</th>
                                    <th class="text-end pe-3" width="10%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tasks as $task)
                                <tr>
                                    <td class="ps-3 fw-medium">{{ $task['id'] }}</td>
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
                                                <a href="{{ route('tasks.show', $task['id']) }}" class="fw-medium text-dark text-decoration-none task-title">
                                                    {{ $task['title'] }}
                                                </a>
                                                @if(isset($task['version']) && $task['version'])
                                                    <span class="badge bg-secondary ms-1">v{{ $task['version'] }}</span>
                                                @endif
                                                <div class="small text-muted mt-1">
                                                    @if(isset($task['related_feature']) && $task['related_feature'])
                                                        <span class="badge bg-light text-dark me-1">{{ $task['related_feature'] }}</span>
                                                    @endif
                                                    @if(isset($task['created_at']) && $task['created_at'])
                                                        <span class="text-muted small">
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
                                        @elseif(isset($task['repository_id']) && $task['repository_id'])
                                            @php
                                                $repository = \App\Models\Repository::find($task['repository_id']);
                                            @endphp
                                            @if($repository)
                                                <a href="{{ route('repositories.show', $repository->id) }}" class="badge text-decoration-none" style="background-color: {{ $repository->color ?? '#343a40' }};">
                                                    <i class="fas fa-code-branch me-1"></i>{{ $repository->name }}
                                                </a>
                                            @else
                                                <span class="text-muted small">Repository #{{ $task['repository_id'] }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted small">No repository</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge 
                                            @if($task['status'] == 'completed') bg-success 
                                            @elseif($task['status'] == 'in-progress') bg-info 
                                            @elseif($task['status'] == 'blocked') bg-danger
                                            @elseif($task['status'] == 'review') bg-primary
                                            @else bg-secondary @endif">
                                            {{ ucfirst($task['status']) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge 
                                            @if($task['priority'] == 'high') bg-danger 
                                            @elseif($task['priority'] == 'medium') bg-warning 
                                            @elseif($task['priority'] == 'critical') bg-dark
                                            @else bg-info @endif">
                                            {{ ucfirst($task['priority']) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
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
                                        <div class="btn-group">
                                            <a href="{{ route('tasks.show', $task['id']) }}" class="btn btn-sm btn-primary" title="View Task">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('tasks.edit', $task['id']) }}" class="btn btn-sm btn-outline-primary" title="Edit Task">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="empty-state">
                                            <img src="{{ asset('img/empty-tasks.svg') }}" alt="No tasks" class="img-fluid mb-3" style="max-height: 120px;">
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
    <div class="row g-3">
        <div class="col-lg-6">
            <!-- Task Statistics Chart -->
            <div class="card shadow-sm border-0 mb-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="m-0 fw-bold text-primary">Task Distribution</h5>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="chart-container mb-3">
                        <canvas id="taskStatusChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-center flex-wrap gap-3 mt-2">
                        <div class="text-center">
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge-dot me-2" style="background-color: #28a745;"></span>
                                <span class="small">Completed</span>
                            </div>
                            <div class="fw-bold">{{ $completedTasks }}</div>
                        </div>
                        <div class="text-center">
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge-dot me-2" style="background-color: #ffc107;"></span>
                                <span class="small">In Progress</span>
                            </div>
                            <div class="fw-bold">{{ $inProgressTasks }}</div>
                        </div>
                        <div class="text-center">
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge-dot me-2" style="background-color: #6c757d;"></span>
                                <span class="small">Pending</span>
                            </div>
                            <div class="fw-bold">{{ $pendingTasks }}</div>
                        </div>
                        <div class="text-center">
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge-dot me-2" style="background-color: #dc3545;"></span>
                                <span class="small">Blocked</span>
                            </div>
                            <div class="fw-bold">{{ $blockedTasks }}</div>
                        </div>
                        <div class="text-center">
                            <div class="d-flex align-items-center mb-1">
                                <span class="badge-dot me-2" style="background-color: #17a2b8;"></span>
                                <span class="small">Review</span>
                            </div>
                            <div class="fw-bold">{{ $reviewTasks }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <!-- Priority Distribution -->
            <div class="card shadow-sm border-0 mb-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="m-0 fw-bold text-primary">Priority Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="progress-tracker py-2">
                        <div class="progress-item mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-danger me-2">High</span>
                                    <span class="text-dark">Priority Tasks</span>
                                </div>
                                <span class="badge bg-light text-dark fw-normal px-3 py-2">{{ $highPriorityTasks }}</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-danger" style="width: {{ $highPriorityPercentage }}%" role="progressbar"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span class="text-muted small">{{ round($highPriorityPercentage) }}% of total</span>
                                <a href="{{ route('tasks.index', ['priority' => 'high']) }}" class="text-decoration-none small">View tasks</a>
                            </div>
                        </div>
                        
                        <div class="progress-item mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-warning text-dark me-2">Medium</span>
                                    <span class="text-dark">Priority Tasks</span>
                                </div>
                                <span class="badge bg-light text-dark fw-normal px-3 py-2">{{ $mediumPriorityTasks }}</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-warning" style="width: {{ $mediumPriorityPercentage }}%" role="progressbar"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span class="text-muted small">{{ round($mediumPriorityPercentage) }}% of total</span>
                                <a href="{{ route('tasks.index', ['priority' => 'medium']) }}" class="text-decoration-none small">View tasks</a>
                            </div>
                        </div>
                        
                        <div class="progress-item">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-info me-2">Low</span>
                                    <span class="text-dark">Priority Tasks</span>
                                </div>
                                <span class="badge bg-light text-dark fw-normal px-3 py-2">{{ $lowPriorityTasks }}</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-info" style="width: {{ $lowPriorityPercentage }}%" role="progressbar"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span class="text-muted small">{{ round($lowPriorityPercentage) }}% of total</span>
                                <a href="{{ route('tasks.index', ['priority' => 'low']) }}" class="text-decoration-none small">View tasks</a>
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
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.raw;
                            const percentage = Math.round((value / total) * 100);
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
});
</script>

<style>
    .badge-dot {
        display: inline-block;
        width: 12px;
        height: 12px;
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
    
    .progress {
        overflow: hidden;
        height: 8px;
        margin-bottom: 1rem;
        border-radius: 1rem;
        background-color: #eaecf4;
    }
    
    .progress-bar {
        border-radius: 1rem;
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