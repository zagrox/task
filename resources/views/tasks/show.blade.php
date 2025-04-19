@extends('tasks.layout')

@section('title', 'Task Details')

@section('content')
<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-clipboard-check text-primary me-2"></i>Task Details
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Task #{{ $task['id'] }}</li>
                </ol>
            </nav>
        </div>
        <div class="btn-toolbar">
            <div class="btn-group me-2">
                <a href="{{ route('tasks.edit', $task['id']) }}" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> Edit Task
                </a>
            </div>
            <!-- GitHub sync button -->
            <div class="btn-group me-2">
                <a href="{{ route('tasks.sync-to-github', $task['id']) }}" class="btn btn-dark">
                    <i class="fab fa-github me-1"></i> Sync to GitHub
                </a>
                
                @php
                    $githubIssue = \App\Models\GitHubIssue::where('task_id', $task['id'])->first();
                @endphp
                
                @if($githubIssue && $githubIssue->issue_url)
                    <a href="{{ $githubIssue->issue_url }}" target="_blank" class="btn btn-outline-dark">
                        #{{ $githubIssue->issue_number }}
                    </a>
                @endif
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteTaskModal">
                    <i class="fas fa-trash me-1"></i> Delete
                </button>
            </div>
        </div>
        
        <!-- Delete Modal -->
        <div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-labelledby="deleteTaskModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteTaskModalLabel">Delete Task</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this task? This action cannot be undone.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form action="{{ route('tasks.destroy', $task['id']) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Delete Task</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Task Details -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Task Information</h6>
                    <div>
                        <span class="badge 
                            @if($task['status'] == 'completed') bg-success 
                            @elseif($task['status'] == 'in-progress') bg-info 
                            @elseif($task['status'] == 'blocked') bg-danger
                            @elseif($task['status'] == 'review') bg-primary
                            @else bg-secondary @endif">
                            {{ ucfirst($task['status']) }}
                        </span>
                        <span class="badge 
                            @if($task['priority'] == 'high') bg-danger 
                            @elseif($task['priority'] == 'medium') bg-warning text-dark
                            @else bg-info @endif ms-1">
                            {{ ucfirst($task['priority']) }} Priority
                        </span>
                        @if(isset($task['version']) && $task['version'])
                            <span class="badge bg-secondary ms-1">v{{ $task['version'] }}</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="task-title-section mb-4">
                        <h3 class="card-title fw-bold">{{ $task['title'] }}</h3>
                        <div class="d-flex align-items-center text-muted small mb-2">
                            <span class="me-3">
                                <i class="fas fa-user-circle me-1"></i> Assigned to: 
                                <span class="fw-bold">{{ ucfirst($task['assignee']) }}</span>
                            </span>
                            @if(isset($task['due_date']) && $task['due_date'])
                            <span class="me-3">
                                <i class="fas fa-calendar me-1"></i> Due: 
                                <span class="@if(strtotime($task['due_date']) < strtotime('today') && $task['status'] != 'completed') text-danger fw-bold @endif">
                                    {{ date('M d, Y', strtotime($task['due_date'])) }}
                                </span>
                            </span>
                            @endif
                            <span>
                                <i class="fas fa-chart-pie me-1"></i> Progress: 
                                <span class="fw-bold">{{ $task['progress'] }}%</span>
                            </span>
                        </div>
                        
                        <div class="progress mb-3" style="height: 10px;">
                            <div class="progress-bar 
                                @if($task['status'] == 'completed') bg-success
                                @elseif($task['status'] == 'in-progress') bg-info
                                @elseif($task['status'] == 'blocked') bg-danger
                                @elseif($task['status'] == 'review') bg-primary
                                @else bg-secondary @endif" 
                                role="progressbar" 
                                style="width: {{ $task['progress'] }}%" 
                                aria-valuenow="{{ $task['progress'] }}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                    
                    <div class="task-description-section mb-4">
                        <h5 class="card-subtitle mb-2 fw-bold">Description</h5>
                        <div class="task-description p-3 bg-light rounded">
                            {!! nl2br(e($task['description'])) !!}
                        </div>
                    </div>
                    
                    <div class="row task-details-section mb-4">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header py-2 bg-light">
                                    <h6 class="m-0 fw-bold">Task Details</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        @if(isset($task['related_feature']) && $task['related_feature'])
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                            <span class="text-muted">Related Feature:</span>
                                            <span class="badge bg-primary-soft text-primary rounded-pill">{{ $task['related_feature'] }}</span>
                                        </li>
                                        @endif
                                        
                                        @if(isset($task['related_phase']) && $task['related_phase'])
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                            <span class="text-muted">Related Phase:</span>
                                            <span class="badge bg-primary-soft text-primary rounded-pill">{{ $task['related_phase'] }}</span>
                                        </li>
                                        @endif
                                        
                                        @if(isset($task['estimated_hours']) && $task['estimated_hours'])
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                            <span class="text-muted">Estimated Hours:</span>
                                            <span>{{ $task['estimated_hours'] }} hours</span>
                                        </li>
                                        @endif
                                        
                                        @if(isset($task['actual_hours']) && $task['actual_hours'])
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                            <span class="text-muted">Actual Hours:</span>
                                            <span>{{ $task['actual_hours'] }} hours</span>
                                        </li>
                                        @endif
                                        
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                            <span class="text-muted">Created:</span>
                                            <span>{{ date('M d, Y', strtotime($task['created_at'])) }}</span>
                                        </li>
                                        
                                        @if(isset($task['updated_at']) && $task['updated_at'] && $task['updated_at'] != $task['created_at'])
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                            <span class="text-muted">Last Updated:</span>
                                            <span>{{ date('M d, Y', strtotime($task['updated_at'])) }}</span>
                                        </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header py-2 bg-light">
                                    <h6 class="m-0 fw-bold">Tags & Time Tracking</h6>
                                </div>
                                <div class="card-body">
                                    @if(isset($task['tags']) && $task['tags'])
                                    <div class="mb-3">
                                        <h6 class="text-muted small text-uppercase mb-2">Tags</h6>
                                        <div class="task-tags">
                                            @foreach(is_array($task['tags']) ? $task['tags'] : explode(',', $task['tags']) as $tag)
                                                <a href="{{ route('tasks.index', ['tag' => trim($tag)]) }}" class="badge bg-light text-dark me-1 mb-1 py-2 px-3 rounded-pill">
                                                    #{{ trim($tag) }}
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                    
                                    @if(isset($task['estimated_hours']) && $task['estimated_hours'] && isset($task['actual_hours']) && $task['actual_hours'])
                                    <div>
                                        <h6 class="text-muted small text-uppercase mb-2">Time Tracking</h6>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="small">
                                                <span class="fw-bold">{{ $task['actual_hours'] }}</span> / {{ $task['estimated_hours'] }} hours
                                            </div>
                                            <div class="small text-muted">
                                                @if($task['actual_hours'] > $task['estimated_hours'])
                                                    <span class="text-danger">{{ round(($task['actual_hours'] / $task['estimated_hours']) * 100) - 100 }}% over estimate</span>
                                                @elseif($task['actual_hours'] == $task['estimated_hours'])
                                                    Exactly as estimated
                                                @else
                                                    <span class="text-success">{{ round(($task['actual_hours'] / $task['estimated_hours']) * 100) }}% of estimate</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar 
                                                @if($task['actual_hours'] > $task['estimated_hours']) bg-danger
                                                @elseif($task['actual_hours'] == $task['estimated_hours']) bg-success
                                                @else bg-info @endif" 
                                                role="progressbar" 
                                                style="width: {{ min(100, ($task['actual_hours'] / $task['estimated_hours']) * 100) }}%" 
                                                aria-valuenow="{{ min(100, ($task['actual_hours'] / $task['estimated_hours']) * 100) }}" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Task Notes Section -->
                    @if(isset($task['notes']) && !empty($task['notes']))
                    <div class="task-notes-section mb-4">
                        <h5 class="card-subtitle mb-3 fw-bold">Notes & Updates</h5>
                        <div class="task-notes">
                            @foreach($task['notes'] as $note)
                            <div class="task-note card mb-2">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold">{{ $note['author'] ?? 'System' }}</span>
                                        <span class="small text-muted">{{ date('M d, Y - h:i A', strtotime($note['timestamp'])) }}</span>
                                    </div>
                                    <p class="mb-0">{{ $note['content'] }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    <!-- Add Note Form -->
                    <div class="task-add-note-section">
                        <h5 class="card-subtitle mb-3 fw-bold">Add Note</h5>
                        <form action="{{ route('tasks.add-note', $task['id']) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <textarea class="form-control" id="note-content" name="content" rows="3" placeholder="Add a note about this task..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-comment-dots me-1"></i> Add Note
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Task Sidebar -->
        <div class="col-lg-4">
            <!-- Task Status Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Task Status</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('tasks.update', $task['id']) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="quick_update" value="true">
                        
                        <div class="mb-3">
                            <label for="status" class="form-label fw-bold">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="pending" @if($task['status'] == 'pending') selected @endif>Pending</option>
                                <option value="in-progress" @if($task['status'] == 'in-progress') selected @endif>In Progress</option>
                                <option value="review" @if($task['status'] == 'review') selected @endif>Review</option>
                                <option value="blocked" @if($task['status'] == 'blocked') selected @endif>Blocked</option>
                                <option value="completed" @if($task['status'] == 'completed') selected @endif>Completed</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="progress" class="form-label fw-bold">Progress (%)</label>
                            <input type="number" class="form-control" id="progress" name="progress" min="0" max="100" value="{{ $task['progress'] }}">
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i> Update Status
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Task Dependencies -->
            @if(isset($task['dependencies']) && !empty($task['dependencies']))
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Dependencies</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($task['dependencies'] as $depId)
                            @php
                                $depTask = null;
                                foreach($allTasks ?? [] as $t) {
                                    if($t['id'] == $depId) {
                                        $depTask = $t;
                                        break;
                                    }
                                }
                            @endphp
                            
                            @if($depTask)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <a href="{{ route('tasks.show', $depTask['id']) }}" class="text-decoration-none">
                                        #{{ $depTask['id'] }}: {{ $depTask['title'] }}
                                    </a>
                                    <div class="small text-muted">{{ ucfirst($depTask['status']) }}</div>
                                </div>
                                <span class="badge 
                                    @if($depTask['status'] == 'completed') bg-success 
                                    @elseif($depTask['status'] == 'in-progress') bg-info 
                                    @elseif($depTask['status'] == 'blocked') bg-danger
                                    @elseif($depTask['status'] == 'review') bg-primary
                                    @else bg-secondary @endif">
                                    {{ $depTask['progress'] }}%
                                </span>
                            </li>
                            @else
                            <li class="list-group-item text-muted">Task #{{ $depId }} (not found)</li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
            
            <!-- Related Tasks Card -->
            @if(!empty($relatedTasks))
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Related Tasks</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($relatedTasks as $relTask)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <a href="{{ route('tasks.show', $relTask['id']) }}" class="text-decoration-none">
                                    #{{ $relTask['id'] }}: {{ $relTask['title'] }}
                                </a>
                                <div class="small text-muted">{{ ucfirst($relTask['status']) }}</div>
                            </div>
                            <span class="badge 
                                @if($relTask['status'] == 'completed') bg-success 
                                @elseif($relTask['status'] == 'in-progress') bg-info 
                                @elseif($relTask['status'] == 'blocked') bg-danger
                                @elseif($relTask['status'] == 'review') bg-primary
                                @else bg-secondary @endif">
                                {{ $relTask['progress'] }}%
                            </span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
            
            <!-- Task Information Card -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Activity Timeline</h6>
                </div>
                <div class="card-body p-0">
                    <div class="timeline p-3">
                        <div class="timeline-item mb-3">
                            <div class="timeline-item-marker">
                                <div class="timeline-item-marker-text">{{ date('M d', strtotime($task['created_at'])) }}</div>
                                <div class="timeline-item-marker-indicator bg-primary"></div>
                            </div>
                            <div class="timeline-item-content">
                                Task was created
                                <div class="text-muted small">{{ date('M d, Y - h:i A', strtotime($task['created_at'])) }}</div>
                            </div>
                        </div>
                        
                        @if(isset($task['notes']) && !empty($task['notes']))
                            @foreach($task['notes'] as $note)
                            <div class="timeline-item mb-3">
                                <div class="timeline-item-marker">
                                    <div class="timeline-item-marker-text">{{ date('M d', strtotime($note['timestamp'])) }}</div>
                                    <div class="timeline-item-marker-indicator bg-info"></div>
                                </div>
                                <div class="timeline-item-content">
                                    Note added
                                    <div class="text-muted small">{{ date('M d, Y - h:i A', strtotime($note['timestamp'])) }}</div>
                                </div>
                            </div>
                            @endforeach
                        @endif
                        
                        @if($task['status'] == 'completed')
                        <div class="timeline-item">
                            <div class="timeline-item-marker">
                                <div class="timeline-item-marker-text">{{ date('M d', strtotime($task['updated_at'])) }}</div>
                                <div class="timeline-item-marker-indicator bg-success"></div>
                            </div>
                            <div class="timeline-item-content">
                                Task was completed
                                <div class="text-muted small">{{ date('M d, Y - h:i A', strtotime($task['updated_at'])) }}</div>
                            </div>
                        </div>
                        @endif
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
        // Update progress automatically when status changes
        const statusSelect = document.getElementById('status');
        const progressInput = document.getElementById('progress');
        
        statusSelect.addEventListener('change', function() {
            if (this.value === 'completed') {
                progressInput.value = 100;
            } else if (this.value === 'pending' && progressInput.value == 100) {
                progressInput.value = 0;
            }
        });
    });
</script>

<style>
    /* Task Page Specific Styles */
    .bg-primary-soft {
        background-color: rgba(78, 115, 223, 0.1);
    }
    
    .task-description {
        white-space: pre-line;
    }
    
    /* Timeline Styles */
    .timeline {
        position: relative;
    }
    
    .timeline-item {
        position: relative;
    }
    
    .timeline-item-marker {
        display: flex;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    
    .timeline-item-marker-text {
        width: 5rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6c757d;
    }
    
    .timeline-item-marker-indicator {
        height: 0.625rem;
        width: 0.625rem;
        border-radius: 100%;
        margin-right: 0.5rem;
    }
    
    .timeline-item-content {
        padding-left: 0.5rem;
        padding-bottom: 1.5rem;
        border-left: 1px solid #ddd;
        margin-left: 0.22rem;
    }
    
    .timeline-item:last-child .timeline-item-content {
        padding-bottom: 0;
        border-left: 0;
    }
</style>
@endsection 