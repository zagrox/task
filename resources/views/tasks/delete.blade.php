@extends('tasks.layout')

@section('title', 'Delete Task - ' . $task['title'])

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <h2>Delete Task #{{ $task['id'] }}</h2>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Confirm Deletion</h5>
                </div>
                <div class="card-body">
                    <p class="lead">Are you sure you want to delete this task?</p>
                    
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> This action cannot be undone. All data related to this task will be permanently removed.
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Task Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-md-3"><strong>ID:</strong></div>
                                <div class="col-md-9">#{{ $task['id'] }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-3"><strong>Title:</strong></div>
                                <div class="col-md-9">{{ $task['title'] }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-3"><strong>Status:</strong></div>
                                <div class="col-md-9">
                                    <span class="badge status-badge status-{{ $task['status'] }}">
                                        {{ ucfirst($task['status']) }}
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-3"><strong>Priority:</strong></div>
                                <div class="col-md-9">
                                    <span class="badge bg-{{ $task['priority'] == 'high' ? 'danger' : ($task['priority'] == 'medium' ? 'warning' : 'primary') }}">
                                        {{ ucfirst($task['priority']) }}
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-3"><strong>Assignee:</strong></div>
                                <div class="col-md-9">{{ ucfirst($task['assignee']) }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-3"><strong>Created At:</strong></div>
                                <div class="col-md-9">{{ \Carbon\Carbon::parse($task['created_at'])->format('Y-m-d H:i') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <form action="{{ route('tasks.destroy', $task['id']) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('tasks.show', $task['id']) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection 