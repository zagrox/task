@extends('tasks.layout')

@section('title', 'Edit Task')

@section('content')
<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-edit text-primary me-2"></i>Edit Task
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tasks.show', $task['id']) }}">Task #{{ $task['id'] }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('tasks.show', $task['id']) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Task
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Task Information</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('tasks.update', $task['id']) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                       id="title" name="title" value="{{ old('title', $task['title']) }}" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="version" class="form-label">Version</label>
                                <input type="text" class="form-control @error('version') is-invalid @enderror" 
                                       id="version" name="version" value="{{ old('version', $task['version'] ?? '') }}" 
                                       placeholder="e.g., 1.0.0">
                                @error('version')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="4">{{ old('description', $task['description']) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" name="status" required>
                                    <option value="pending" {{ old('status', $task['status']) == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="in-progress" {{ old('status', $task['status']) == 'in-progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="review" {{ old('status', $task['status']) == 'review' ? 'selected' : '' }}>Review</option>
                                    <option value="blocked" {{ old('status', $task['status']) == 'blocked' ? 'selected' : '' }}>Blocked</option>
                                    <option value="completed" {{ old('status', $task['status']) == 'completed' ? 'selected' : '' }}>Completed</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="priority" class="form-label">Priority <span class="text-danger">*</span></label>
                                <select class="form-select @error('priority') is-invalid @enderror" 
                                        id="priority" name="priority" required>
                                    <option value="low" {{ old('priority', $task['priority']) == 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ old('priority', $task['priority']) == 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ old('priority', $task['priority']) == 'high' ? 'selected' : '' }}>High</option>
                                </select>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="assignee" class="form-label">Assignee <span class="text-danger">*</span></label>
                                <select class="form-select @error('assignee') is-invalid @enderror" 
                                        id="assignee" name="assignee" required>
                                    <option value="user" {{ old('assignee', $task['assignee']) == 'user' ? 'selected' : '' }}>User</option>
                                    <option value="ai" {{ old('assignee', $task['assignee']) == 'ai' ? 'selected' : '' }}>AI</option>
                                </select>
                                @error('assignee')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">Due Date</label>
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror" 
                                       id="due_date" name="due_date" value="{{ old('due_date', $task['due_date'] ?? '') }}">
                                @error('due_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="progress" class="form-label">Progress (%)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control @error('progress') is-invalid @enderror" 
                                           id="progress" name="progress" min="0" max="100" 
                                           value="{{ old('progress', $task['progress']) }}">
                                    <span class="input-group-text">%</span>
                                </div>
                                @error('progress')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="related_feature" class="form-label">Related Feature</label>
                                <input type="text" class="form-control @error('related_feature') is-invalid @enderror" 
                                       id="related_feature" name="related_feature" 
                                       value="{{ old('related_feature', $task['related_feature'] ?? '') }}">
                                @error('related_feature')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="related_phase" class="form-label">Related Phase</label>
                                <input type="text" class="form-control @error('related_phase') is-invalid @enderror" 
                                       id="related_phase" name="related_phase" 
                                       value="{{ old('related_phase', $task['related_phase'] ?? '') }}">
                                @error('related_phase')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="estimated_hours" class="form-label">Estimated Hours</label>
                                <input type="number" step="0.5" class="form-control @error('estimated_hours') is-invalid @enderror" 
                                       id="estimated_hours" name="estimated_hours" 
                                       value="{{ old('estimated_hours', $task['estimated_hours'] ?? '') }}">
                                @error('estimated_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="actual_hours" class="form-label">Actual Hours</label>
                                <input type="number" step="0.5" class="form-control @error('actual_hours') is-invalid @enderror" 
                                       id="actual_hours" name="actual_hours" 
                                       value="{{ old('actual_hours', $task['actual_hours'] ?? '') }}">
                                @error('actual_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags</label>
                            <input type="text" class="form-control @error('tags') is-invalid @enderror" 
                                   id="tags" name="tags" value="{{ old('tags', isset($task['tags']) ? (is_array($task['tags']) ? implode(', ', $task['tags']) : $task['tags']) : '') }}" 
                                   placeholder="Separate tags with commas">
                            <div class="form-text">Enter tags separated by commas (e.g., frontend, bug, feature)</div>
                            @error('tags')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('tasks.show', $task['id']) }}" class="btn btn-outline-secondary me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Help & Guidelines</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            <strong>Status:</strong> Update the current state of the task.
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            <strong>Priority:</strong> Set the task's importance level.
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            <strong>Progress:</strong> Enter percentage of completion (0-100%).
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            <strong>Version:</strong> Indicate which software version this task is for.
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-info-circle text-info me-2"></i>
                            <strong>Tags:</strong> Add tags to categorize tasks (comma-separated).
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Additional Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('tasks.show', $task['id']) }}" class="btn btn-outline-primary">
                            <i class="fas fa-eye me-1"></i> View Task Details
                        </a>
                        <form action="{{ route('tasks.destroy', $task['id']) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100" 
                                    onclick="return confirm('Are you sure you want to delete this task? This action cannot be undone.')">
                                <i class="fas fa-trash me-1"></i> Delete Task
                            </button>
                        </form>
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
@endsection 