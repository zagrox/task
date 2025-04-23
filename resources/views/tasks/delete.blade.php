@extends('tasks.layout')

@section('title', 'Confirm Delete Task')

@section('content')
<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-trash-alt text-danger me-2"></i>Confirm Delete Task
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Delete Task</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-danger">Delete Confirmation</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Are you sure you want to delete the following task? This action cannot be undone.
            </div>
            
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 150px">ID</th>
                        <td>{{ $task->id }}</td>
                    </tr>
                    <tr>
                        <th>Title</th>
                        <td>{{ $task->title }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge bg-{{ $task->status === 'completed' ? 'success' : ($task->status === 'in-progress' ? 'primary' : 'warning') }}">
                                {{ ucfirst($task->status) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Priority</th>
                        <td>
                            <span class="badge bg-{{ $task->priority === 'high' ? 'danger' : ($task->priority === 'medium' ? 'warning' : 'info') }}">
                                {{ ucfirst($task->priority) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Assignee</th>
                        <td>{{ $task->assignee }}</td>
                    </tr>
                    <tr>
                        <th>Created</th>
                        <td>{{ \Carbon\Carbon::parse($task->created_at)->format('M d, Y H:i') }}</td>
                    </tr>
                </table>
            </div>
            
            <form action="{{ route('tasks.destroy', $task->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="d-flex justify-content-between">
                    <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> Confirm Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection 