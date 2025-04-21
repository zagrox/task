@extends('tasks.layout')

@section('title', 'Task Reports')

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <h2>Task Reports Dashboard</h2>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="metrics-card bg-primary text-white">
                <h3>{{ $metadata['total_tasks'] ?? 0 }}</h3>
                <p>Total Tasks</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metrics-card bg-success text-white">
                <h3>{{ $metadata['completed_tasks'] ?? 0 }}</h3>
                <p>Completed</p>
                <div class="progress bg-light">
                    @php
                    $completion = $metadata['total_tasks'] > 0 
                        ? round(($metadata['completed_tasks'] / $metadata['total_tasks']) * 100) 
                        : 0;
                    @endphp
                    <div class="progress-bar bg-white" role="progressbar" style="width: {{ $completion }}%;" 
                         aria-valuenow="{{ $completion }}" aria-valuemin="0" aria-valuemax="100">
                        {{ $completion }}%
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metrics-card bg-info text-white">
                <h3>{{ $metadata['user_tasks'] ?? 0 }}</h3>
                <p>User Tasks</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="metrics-card bg-warning text-white">
                <h3>{{ $metadata['ai_tasks'] ?? 0 }}</h3>
                <p>AI Tasks</p>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Status Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Priority Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="priorityChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Tasks by Feature</h5>
                </div>
                <div class="card-body">
                    @if(count($byFeature) > 0)
                        <canvas id="featureChart"></canvas>
                    @else
                        <p class="text-muted">No feature data available.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Tasks by Phase</h5>
                </div>
                <div class="card-body">
                    @if(count($byPhase) > 0)
                        <canvas id="phaseChart"></canvas>
                    @else
                        <p class="text-muted">No phase data available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Tasks by Version</h5>
                </div>
                <div class="card-body">
                    @if(count($byVersion) > 0)
                        <canvas id="versionChart"></canvas>
                    @else
                        <p class="text-muted">No version data available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Upcoming Deadlines</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="alert alert-danger">
                                <h6 class="alert-heading">Overdue Tasks ({{ count($overdue) }})</h6>
                                <hr>
                                @if(count($overdue) > 0)
                                    <ul class="list-unstyled mb-0">
                                        @foreach($overdue as $task)
                                            <li class="mb-1">
                                                <a href="{{ route('tasks.show', $task['id']) }}" class="text-danger">
                                                    #{{ $task['id'] }}: {{ $task['title'] }}
                                                </a>
                                                <small class="d-block text-muted">Due: {{ $task['due_date'] }}</small>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="mb-0">No overdue tasks.</p>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-warning">
                                <h6 class="alert-heading">Due Today ({{ count($dueToday) }})</h6>
                                <hr>
                                @if(count($dueToday) > 0)
                                    <ul class="list-unstyled mb-0">
                                        @foreach($dueToday as $task)
                                            <li class="mb-1">
                                                <a href="{{ route('tasks.show', $task['id']) }}">
                                                    #{{ $task['id'] }}: {{ $task['title'] }}
                                                </a>
                                                <span class="badge status-badge status-{{ $task['status'] }} float-end">
                                                    {{ ucfirst($task['status']) }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="mb-0">No tasks due today.</p>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">Upcoming ({{ array_sum(array_map('count', $comingSoon)) }})</h6>
                                <hr>
                                @if(count($comingSoon) > 0)
                                    <ul class="list-unstyled mb-0">
                                        @foreach($comingSoon as $date => $tasks)
                                            <li class="mb-2">
                                                <strong>{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</strong>
                                                <ul class="list-unstyled ps-3 mb-1">
                                                    @foreach($tasks as $task)
                                                        <li>
                                                            <a href="{{ route('tasks.show', $task['id']) }}">
                                                                #{{ $task['id'] }}: {{ $task['title'] }}
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="mb-0">No upcoming due dates.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Status Chart
        var statusCtx = document.getElementById('statusChart').getContext('2d');
        var statusData = @json($byStatus);
        
        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: Object.keys(statusData).map(status => status.charAt(0).toUpperCase() + status.slice(1)),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: [
                        '#6c757d', // pending
                        '#0d6efd', // in-progress
                        '#198754', // completed
                        '#dc3545'  // blocked
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.raw || 0;
                                var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                var percentage = Math.round((value / total) * 100);
                                return label + ': ' + value + ' tasks (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        
        // Priority Chart
        var priorityCtx = document.getElementById('priorityChart').getContext('2d');
        var priorityData = @json($byPriority);
        
        new Chart(priorityCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(priorityData).map(priority => priority.charAt(0).toUpperCase() + priority.slice(1)),
                datasets: [{
                    label: 'Tasks',
                    data: Object.values(priorityData),
                    backgroundColor: [
                        '#0d6efd', // low
                        '#fd7e14', // medium
                        '#dc3545', // high
                        '#6610f2'  // critical
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Feature Chart
        @if(count($byFeature) > 0)
        var featureCtx = document.getElementById('featureChart').getContext('2d');
        var featureData = @json($byFeature);
        
        new Chart(featureCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(featureData),
                datasets: [{
                    data: Object.values(featureData),
                    backgroundColor: [
                        '#0d6efd',
                        '#6610f2',
                        '#fd7e14',
                        '#198754',
                        '#20c997',
                        '#0dcaf0',
                        '#6c757d',
                        '#dc3545'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
        @endif
        
        // Phase Chart
        @if(count($byPhase) > 0)
        var phaseCtx = document.getElementById('phaseChart').getContext('2d');
        var phaseData = @json($byPhase);
        
        new Chart(phaseCtx, {
            type: 'polarArea',
            data: {
                labels: Object.keys(phaseData),
                datasets: [{
                    data: Object.values(phaseData),
                    backgroundColor: [
                        'rgba(13, 110, 253, 0.7)',
                        'rgba(102, 16, 242, 0.7)',
                        'rgba(253, 126, 20, 0.7)',
                        'rgba(25, 135, 84, 0.7)',
                        'rgba(32, 201, 151, 0.7)',
                        'rgba(13, 202, 240, 0.7)',
                        'rgba(108, 117, 125, 0.7)',
                        'rgba(220, 53, 69, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });
        @endif
        
        // Version Chart
        @if(count($byVersion) > 0)
        var versionCtx = document.getElementById('versionChart').getContext('2d');
        var versionData = @json($byVersion);
        
        new Chart(versionCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(versionData),
                datasets: [{
                    label: 'Tasks',
                    data: Object.values(versionData),
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Task Distribution by Version'
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });
        @endif
    });
</script>
@endsection 