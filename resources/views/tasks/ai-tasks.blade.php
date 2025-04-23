@extends('layouts.app')

@section('title', 'AI Tasks')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1>AI Task Management</h1>
            <p class="lead">Tasks automatically assigned to AI for processing and automation</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total AI Tasks</h5>
                    <h2 class="display-4">{{ $totalTasks }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Pending</h5>
                    <h2 class="display-4">{{ $pendingTasks }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">In Progress</h5>
                    <h2 class="display-4">{{ $inProgressTasks }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Completed</h5>
                    <h2 class="display-4">{{ $completedTasks }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3>AI Assigned Tasks</h3>
                </div>
                <div class="card-body">
                    @if($tasks->isEmpty())
                        <div class="alert alert-info">
                            No tasks currently assigned to AI.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Due Date</th>
                                        <th>Progress</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tasks as $task)
                                    <tr>
                                        <td>{{ $task->id }}</td>
                                        <td>
                                            <a href="{{ route('tasks.show', $task->id) }}">
                                                {{ $task->title }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $task->status == 'completed' ? 'success' : ($task->status == 'in-progress' ? 'info' : 'warning') }}">
                                                {{ ucfirst($task->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $task->priority == 'high' ? 'danger' : ($task->priority == 'medium' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst($task->priority) }}
                                            </span>
                                        </td>
                                        <td>{{ $task->due_date ? $task->due_date->format('Y-m-d') : 'N/A' }}</td>
                                        <td>
                                            <div class="progress">
                                                <div class="progress-bar" role="progressbar" style="width: {{ $task->progress }}%;" 
                                                    aria-valuenow="{{ $task->progress }}" aria-valuemin="0" aria-valuemax="100">
                                                    {{ $task->progress }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm btn-primary">View</a>
                                            <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 