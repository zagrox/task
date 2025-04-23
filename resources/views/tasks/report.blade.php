@extends('tasks.layout')

@section('title', 'Task Reports')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold m-0">Task Insights</h2>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#filterModal">
                <i class="fas fa-filter me-2"></i>Filter
            </button>
            <a href="{{ route('tasks.report') }}" class="btn btn-outline-secondary">
                <i class="fas fa-sync-alt me-2"></i>Reset
            </a>
        </div>
    </div>
    
    <!-- Active filters display -->
    @if($selectedFeature != 'All' || $selectedPhase != 'All' || $selectedVersion != 'All')
    <div class="card bg-light border-0 shadow-sm mb-4">
        <div class="card-body py-2">
            <div class="d-flex align-items-center">
                <span class="text-muted me-2">Active filters:</span>
                @if($selectedFeature != 'All')
                    <span class="badge bg-info text-dark me-2 py-1 px-2">
                        Feature: {{ $selectedFeature }}
                    </span>
                @endif
                @if($selectedPhase != 'All')
                    <span class="badge bg-info text-dark me-2 py-1 px-2">
                        Phase: {{ $selectedPhase }}
                    </span>
                @endif
                @if($selectedVersion != 'All')
                    <span class="badge bg-info text-dark me-2 py-1 px-2">
                        Version: {{ $selectedVersion }}
                    </span>
                @endif
            </div>
        </div>
    </div>
    @endif
    
    <!-- Main stats cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-1">Total Tasks</h6>
                            <h2 class="mb-0 fw-bold">{{ $stats['total'] }}</h2>
                        </div>
                        <div class="p-2 rounded-circle bg-primary bg-opacity-10">
                            <i class="fas fa-tasks text-primary"></i>
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
                            <h2 class="mb-0 fw-bold">{{ $stats['completed'] }}</h2>
                        </div>
                        <div class="p-2 rounded-circle bg-success bg-opacity-10">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                    </div>
                    <div class="progress mt-4" style="height: 4px;">
                        @php
                            $completedPercent = $stats['total'] > 0 ? ($stats['completed'] / $stats['total'] * 100) : 0;
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
                            <h2 class="mb-0 fw-bold">{{ $stats['inProgress'] }}</h2>
                        </div>
                        <div class="p-2 rounded-circle bg-warning bg-opacity-10">
                            <i class="fas fa-spinner text-warning"></i>
                        </div>
                    </div>
                    <div class="progress mt-4" style="height: 4px;">
                        @php
                            $inProgressPercent = $stats['total'] > 0 ? ($stats['inProgress'] / $stats['total'] * 100) : 0;
                        @endphp
                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $inProgressPercent }}%;" aria-valuenow="{{ $inProgressPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
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
                            <h2 class="mb-0 fw-bold">{{ $stats['pending'] }}</h2>
                        </div>
                        <div class="p-2 rounded-circle bg-info bg-opacity-10">
                            <i class="fas fa-clock text-info"></i>
                        </div>
                    </div>
                    <div class="progress mt-4" style="height: 4px;">
                        @php
                            $pendingPercent = $stats['total'] > 0 ? ($stats['pending'] / $stats['total'] * 100) : 0;
                        @endphp
                        <div class="progress-bar bg-info" role="progressbar" style="width: {{ $pendingPercent }}%;" aria-valuenow="{{ $pendingPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-1">Blocked</h6>
                            <h2 class="mb-0 fw-bold">{{ $stats['blocked'] }}</h2>
                        </div>
                        <div class="p-2 rounded-circle bg-danger bg-opacity-10">
                            <i class="fas fa-ban text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-1">User Tasks</h6>
                            <h2 class="mb-0 fw-bold">{{ $stats['user'] }}</h2>
                        </div>
                        <div class="p-2 rounded-circle bg-secondary bg-opacity-10">
                            <i class="fas fa-user text-secondary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-2 col-sm-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-1">AI Tasks</h6>
                            <h2 class="mb-0 fw-bold">{{ $stats['ai'] }}</h2>
                        </div>
                        <div class="p-2 rounded-circle bg-dark bg-opacity-10">
                            <i class="fas fa-robot text-dark"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Task Completion Rate</h6>
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="progress" style="height: 8px;">
                                @php
                                    $completionRate = $stats['total'] > 0 ? ($stats['completed'] / $stats['total'] * 100) : 0;
                                @endphp
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $completionRate }}%;" 
                                     aria-valuenow="{{ $completionRate }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div class="ms-3">
                            <h4 class="fw-bold mb-0">{{ number_format($completionRate, 1) }}%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Status Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Priority Distribution</h5>
                </div>
                <div class="card-body">
                    <canvas id="priorityChart" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Tasks by Feature</h5>
                </div>
                <div class="card-body">
                    <canvas id="featureChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- More Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Tasks by Phase</h5>
                </div>
                <div class="card-body">
                    <canvas id="phaseChart" height="230"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Tasks by Version</h5>
                </div>
                <div class="card-body">
                    <canvas id="versionChart" height="230"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Deadline Tracking -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <div class="d-flex align-items-center">
                        <span class="badge rounded-pill bg-danger me-2">{{ count($overdue ?? []) }}</span>
                        <h5 class="mb-0">Overdue Tasks</h5>
                    </div>
                </div>
                <div class="card-body">
                    @if(count($overdue ?? []) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($overdue as $task)
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0 text-truncate" style="max-width: 200px;" title="{{ $task['title'] }}">{{ $task['title'] }}</h6>
                                        <span class="badge bg-{{ $task['priority'] == 'high' ? 'danger' : ($task['priority'] == 'medium' ? 'warning' : 'info') }} text-white">
                                            {{ ucfirst($task['priority']) }}
                                        </span>
                                    </div>
                                    <small class="text-danger">
                                        <i class="far fa-calendar-times me-1"></i>Due: {{ $task['due_date'] }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="far fa-check-circle fa-3x mb-3"></i>
                            <p>No overdue tasks</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <div class="d-flex align-items-center">
                        <span class="badge rounded-pill bg-warning text-dark me-2">{{ count($dueToday ?? []) }}</span>
                        <h5 class="mb-0">Due Today</h5>
                    </div>
                </div>
                <div class="card-body">
                    @if(count($dueToday ?? []) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($dueToday as $task)
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0 text-truncate" style="max-width: 200px;" title="{{ $task['title'] }}">{{ $task['title'] }}</h6>
                                        <span class="badge bg-{{ $task['status'] == 'in-progress' ? 'warning' : 'info' }} text-white">
                                            {{ ucfirst($task['status']) }}
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fas fa-exclamation-circle me-1"></i>Priority: {{ ucfirst($task['priority']) }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="far fa-calendar-check fa-3x mb-3"></i>
                            <p>No tasks due today</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Upcoming Deadlines</h5>
                </div>
                <div class="card-body">
                    @if(!empty($comingSoon))
                        <div class="accordion accordion-flush" id="upcomingAccordion">
                            @foreach($comingSoon as $date => $dateTasks)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading{{ $loop->index }}">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $loop->index }}" aria-expanded="false" aria-controls="collapse{{ $loop->index }}">
                                            <div class="d-flex align-items-center w-100">
                                                <div class="me-auto">{{ \Carbon\Carbon::parse($date)->format('D, M d') }}</div>
                                                <span class="badge bg-info rounded-pill ms-2">{{ count($dateTasks) }}</span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse{{ $loop->index }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $loop->index }}" data-bs-parent="#upcomingAccordion">
                                        <div class="accordion-body px-0">
                                            <div class="list-group list-group-flush">
                                                @foreach($dateTasks as $task)
                                                    <div class="list-group-item border-0 px-0">
                                                        <h6 class="mb-1 text-truncate" style="max-width: 250px;" title="{{ $task['title'] }}">{{ $task['title'] }}</h6>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <small class="text-muted">{{ ucfirst($task['priority']) }} priority</small>
                                                            <span class="badge bg-{{ $task['status'] == 'in-progress' ? 'warning' : 'info' }} text-white">
                                                                {{ ucfirst($task['status']) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="far fa-calendar fa-3x mb-3"></i>
                            <p>No upcoming deadlines in the next 7 days</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Filter Reports</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="GET" action="{{ route('tasks.report') }}" id="filterForm">
                    <div class="mb-3">
                        <label for="feature" class="form-label">Feature</label>
                        <select name="feature" id="feature" class="form-select">
                            @foreach($features as $feature)
                                <option value="{{ $feature }}" {{ $selectedFeature == $feature ? 'selected' : '' }}>{{ $feature }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="phase" class="form-label">Phase</label>
                        <select name="phase" id="phase" class="form-select">
                            @foreach($phases as $phase)
                                <option value="{{ $phase }}" {{ $selectedPhase == $phase ? 'selected' : '' }}>{{ $phase }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="version" class="form-label">Version</label>
                        <select name="version" id="version" class="form-select">
                            @foreach($versions as $version)
                                <option value="{{ $version }}" {{ $selectedVersion == $version ? 'selected' : '' }}>{{ $version }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('filterForm').submit()">Apply Filters</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Common chart options
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    padding: 15
                }
            }
        }
    };
    
    // Modern color palette
    const colorPalette = [
        '#4361ee', '#3a0ca3', '#7209b7', '#f72585', // Blues and purples
        '#4cc9f0', '#4895ef', '#560bad', '#b5179e', // More blues and purples
        '#f77f00', '#fcbf49', '#d62828', '#003049'  // Oranges, yellows, and reds
    ];
    
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusData = @json($byStatus);
    
    if (Object.values(statusData).some(value => value > 0)) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData).map(key => key.charAt(0).toUpperCase() + key.slice(1).replace('-', ' ')),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: [
                        '#4cc9f0', // Pending (Blue)
                        '#fcbf49', // In Progress (Yellow)
                        '#4ade80', // Completed (Green)
                        '#f87171', // Blocked (Red)
                        '#a78bfa'  // Review (Purple)
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                ...chartOptions,
                cutout: '70%',
                plugins: {
                    ...chartOptions.plugins,
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.formattedValue;
                                const total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                                const percentage = Math.round((context.raw * 100) / total);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    } else {
        document.getElementById('statusChart').parentNode.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><p class="text-center text-muted">No data available</p></div>';
    }
    
    // Priority Chart
    const priorityCtx = document.getElementById('priorityChart').getContext('2d');
    const priorityData = @json($byPriority);
    
    if (Object.values(priorityData).some(value => value > 0)) {
        new Chart(priorityCtx, {
            type: 'pie',
            data: {
                labels: Object.keys(priorityData).map(key => key.charAt(0).toUpperCase() + key.slice(1)),
                datasets: [{
                    data: Object.values(priorityData),
                    backgroundColor: [
                        '#4ade80', // Low (Green)
                        '#facc15', // Medium (Yellow)
                        '#f97316', // High (Orange)
                        '#ef4444'  // Critical (Red)
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.formattedValue;
                                const total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                                const percentage = Math.round((context.raw * 100) / total);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    } else {
        document.getElementById('priorityChart').parentNode.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><p class="text-center text-muted">No data available</p></div>';
    }
    
    // Feature Chart
    const featureCtx = document.getElementById('featureChart').getContext('2d');
    const featureData = @json($byFeature);
    
    if (Object.keys(featureData).length > 0) {
        new Chart(featureCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(featureData),
                datasets: [{
                    label: 'Tasks',
                    data: Object.values(featureData),
                    backgroundColor: '#4361ee',
                    borderRadius: 4,
                    borderWidth: 0
                }]
            },
            options: {
                ...chartOptions,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    } else {
        document.getElementById('featureChart').parentNode.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><p class="text-center text-muted">No data available</p></div>';
    }
    
    // Phase Chart
    const phaseCtx = document.getElementById('phaseChart').getContext('2d');
    const phaseData = @json($byPhase);
    
    if (Object.keys(phaseData).length > 0) {
        new Chart(phaseCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(phaseData),
                datasets: [{
                    label: 'Tasks',
                    data: Object.values(phaseData),
                    backgroundColor: colorPalette.slice(0, Object.keys(phaseData).length),
                    borderRadius: 4,
                    borderWidth: 0
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    } else {
        document.getElementById('phaseChart').parentNode.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><p class="text-center text-muted">No data available</p></div>';
    }
    
    // Version Chart
    const versionCtx = document.getElementById('versionChart').getContext('2d');
    const versionData = @json($byVersion);
    
    if (Object.keys(versionData).length > 0) {
        new Chart(versionCtx, {
            type: 'polarArea',
            data: {
                labels: Object.keys(versionData),
                datasets: [{
                    data: Object.values(versionData),
                    backgroundColor: colorPalette.slice(0, Object.keys(versionData).length),
                    borderWidth: 0
                }]
            },
            options: {
                ...chartOptions,
                plugins: {
                    ...chartOptions.plugins,
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.formattedValue;
                                const total = context.dataset.data.reduce((acc, data) => acc + data, 0);
                                const percentage = Math.round((context.raw * 100) / total);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                scales: {
                    r: {
                        ticks: {
                            display: false
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    } else {
        document.getElementById('versionChart').parentNode.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100"><p class="text-center text-muted">No data available</p></div>';
    }
});
</script>
@endsection 