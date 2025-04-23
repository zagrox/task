@extends('tasks.layout')

@section('title', $repository->name . ' - Repository')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('repositories.index') }}">Repositories</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $repository->name }}</li>
                </ol>
            </nav>
            <h1 class="my-2">
                <span class="badge me-2" style="background-color: {{ $repository->color ?? '#6c757d' }};">
                    <i class="fas fa-code-branch"></i>
                </span>
                {{ $repository->name }}
            </h1>
            @if($repository->description)
                <p class="text-muted">{{ $repository->description }}</p>
            @endif
        </div>
        <div>
            <a href="{{ route('tasks.create', ['repository' => $repository->id]) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Task
            </a>
        </div>
    </div>

    <!-- Repository Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Total Tasks</h5>
                            <p class="display-4 mb-0">{{ $stats['task_count'] }}</p>
                        </div>
                        <i class="fas fa-tasks fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Completed</h5>
                            <p class="display-4 mb-0">{{ $stats['completed_count'] }}</p>
                        </div>
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">In Progress</h5>
                            <p class="display-4 mb-0">{{ $stats['in_progress_count'] }}</p>
                        </div>
                        <i class="fas fa-spinner fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Pending</h5>
                            <p class="display-4 mb-0">{{ $stats['pending_count'] }}</p>
                        </div>
                        <i class="fas fa-clock fa-3x opacity-50"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <div class="progress w-100">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $stats['completion_rate'] }}%;"
                             aria-valuenow="{{ $stats['completion_rate'] }}" aria-valuemin="0" aria-valuemax="100">
                            {{ $stats['completion_rate'] }}% Complete
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Tasks List -->
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-tasks me-1"></i>
                    Tasks
                </div>
                <div class="card-body">
                    @if(count($tasks) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Due Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tasks as $task)
                                        <tr>
                                            <td>
                                                <a href="{{ route('tasks.show', $task->id) }}">{{ $task->title }}</a>
                                                @if($task->github_issue)
                                                    <a href="{{ $task->github_issue->issue_url }}" target="_blank" class="ms-2">
                                                        <i class="fab fa-github text-dark" title="GitHub Issue #{{ $task->github_issue->issue_number }}"></i>
                                                    </a>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $statusClass = 'secondary';
                                                    if ($task->status == 'completed') $statusClass = 'success';
                                                    elseif ($task->status == 'in-progress') $statusClass = 'warning';
                                                    elseif ($task->status == 'pending') $statusClass = 'danger';
                                                @endphp
                                                <span class="badge bg-{{ $statusClass }}">
                                                    {{ ucfirst($task->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $priorityClass = 'info';
                                                    if ($task->priority == 'high') $priorityClass = 'danger';
                                                    elseif ($task->priority == 'medium') $priorityClass = 'warning';
                                                    elseif ($task->priority == 'low') $priorityClass = 'success';
                                                @endphp
                                                <span class="badge bg-{{ $priorityClass }}">
                                                    {{ ucfirst($task->priority) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($task->due_date)
                                                    {{ \Carbon\Carbon::parse($task->due_date)->format('M d, Y') }}
                                                @else
                                                    <span class="text-muted">None</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @if(!$task->github_issue)
                                                        <a href="{{ route('tasks.github.sync', $task->id) }}" class="btn btn-sm btn-dark">
                                                            <i class="fab fa-github"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        {{ $tasks->links() }}
                    @else
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-tasks fa-3x text-gray-300"></i>
                            </div>
                            <h5>No tasks found for this repository</h5>
                            <p>Create a new task assigned to this repository.</p>
                            <a href="{{ route('tasks.create', ['repository' => $repository->id]) }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i> Create Task
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- GitHub Integration -->
        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fab fa-github me-1"></i>
                    GitHub Integration
                </div>
                <div class="card-body">
                    @if(!empty($issues))
                        <div class="mb-3">
                            <strong>GitHub Repository:</strong> 
                            <a href="https://github.com/{{ $repository->github_repo }}" target="_blank" class="ms-1">
                                {{ $repository->github_repo }}
                                <i class="fas fa-external-link-alt fa-xs"></i>
                            </a>
                        </div>
                        
                        <h6 class="mb-3">Recent Issues</h6>
                        <div class="list-group">
                            @foreach($issues as $issue)
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <a href="{{ $issue['html_url'] }}" target="_blank">
                                                #{{ $issue['number'] }} {{ $issue['title'] }}
                                            </a>
                                        </h6>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($issue['created_at'])->diffForHumans() }}</small>
                                    </div>
                                    <div class="d-flex mt-2">
                                        @foreach($issue['labels'] as $label)
                                            <span class="badge me-1" style="background-color: #{{ $label['color'] }};">
                                                {{ $label['name'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                    @if(!isset($issue['task_id']))
                                        <a href="{{ route('tasks.github.import', ['repo' => $repository->github_repo, 'issue_number' => $issue['number']]) }}" 
                                           class="btn btn-sm btn-outline-primary mt-2">
                                            <i class="fas fa-file-import me-1"></i> Import as Task
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fab fa-github fa-3x text-gray-300"></i>
                            </div>
                            <h5>No GitHub Integration</h5>
                            <p>Connect this repository to GitHub to sync tasks with issues.</p>
                            
                            @if(!$repository->github_repo)
                                <form action="{{ route('repositories.github.connect', $repository->id) }}" method="POST" class="mt-3">
                                    @csrf
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">github.com/</span>
                                        <input type="text" name="github_repo" class="form-control" placeholder="owner/repo" required>
                                        <button class="btn btn-primary" type="submit">Connect</button>
                                    </div>
                                </form>
                            @else
                                <a href="{{ route('repositories.github.sync', $repository->id) }}" class="btn btn-primary">
                                    <i class="fas fa-sync me-1"></i> Sync with GitHub
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Task Distribution -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Task Distribution
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="mb-2">Status Distribution</h6>
                        <canvas id="statusChart" width="100%" height="100"></canvas>
                    </div>
                    <div class="mb-4">
                        <h6 class="mb-2">Priority Distribution</h6>
                        <canvas id="priorityChart" width="100%" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'In Progress', 'Pending'],
            datasets: [{
                data: [
                    {{ $stats['completed_count'] }}, 
                    {{ $stats['in_progress_count'] }}, 
                    {{ $stats['pending_count'] }}
                ],
                backgroundColor: [
                    '#28a745', // success
                    '#ffc107', // warning
                    '#dc3545'  // danger
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Priority Chart
    const priorityCtx = document.getElementById('priorityChart').getContext('2d');
    const priorityChart = new Chart(priorityCtx, {
        type: 'doughnut',
        data: {
            labels: ['High', 'Medium', 'Low', 'None'],
            datasets: [{
                data: [
                    {{ $stats['priority_high'] ?? 0 }}, 
                    {{ $stats['priority_medium'] ?? 0 }}, 
                    {{ $stats['priority_low'] ?? 0 }},
                    {{ $stats['priority_none'] ?? 0 }}
                ],
                backgroundColor: [
                    '#dc3545', // danger
                    '#ffc107', // warning
                    '#28a745', // success
                    '#6c757d'  // secondary
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>
@endpush
@endsection 