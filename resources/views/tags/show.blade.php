@extends('tasks.layout')

@section('title', 'Tag Details: ' . $tag->name)

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Tag Details</h5>
                    <div>
                        <a href="{{ route('tags.edit', $tag->id) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('tags.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="tag-preview mb-3">
                        <span class="badge p-2" style="background-color: {{ $tag->color }}; font-size: 1.2rem;">
                            {{ $tag->name }}
                        </span>
                    </div>

                    <table class="table">
                        <tbody>
                            <tr>
                                <th style="width: 30%">Name:</th>
                                <td>{{ $tag->name }}</td>
                            </tr>
                            <tr>
                                <th>Color:</th>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="color-box me-2" style="display: inline-block; width: 18px; height: 18px; background-color: {{ $tag->color }}; border-radius: 3px;"></span>
                                        {{ $tag->color }}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Description:</th>
                                <td>{{ $tag->description ?? 'No description provided' }}</td>
                            </tr>
                            <tr>
                                <th>Tasks Count:</th>
                                <td>{{ $tag->tasks->count() }}</td>
                            </tr>
                            <tr>
                                <th>Created:</th>
                                <td>{{ $tag->created_at->format('M d, Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Last Updated:</th>
                                <td>{{ $tag->updated_at->format('M d, Y H:i') }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <form action="{{ route('tags.destroy', $tag->id) }}" method="POST" class="mt-3">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Are you sure you want to delete this tag? This will remove the tag from all associated tasks.')">
                            <i class="fas fa-trash"></i> Delete Tag
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Tasks with this Tag</h5>
                </div>
                <div class="card-body">
                    @if ($tag->tasks->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Due Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tag->tasks as $task)
                                        <tr>
                                            <td>{{ $task->id }}</td>
                                            <td>{{ $task->title }}</td>
                                            <td>
                                                <span class="badge 
                                                    @if ($task->status == 'pending') bg-warning
                                                    @elseif ($task->status == 'in-progress') bg-primary
                                                    @elseif ($task->status == 'completed') bg-success
                                                    @elseif ($task->status == 'blocked') bg-danger
                                                    @elseif ($task->status == 'review') bg-info
                                                    @endif">
                                                    {{ ucfirst($task->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge 
                                                    @if ($task->priority == 'low') bg-success
                                                    @elseif ($task->priority == 'medium') bg-warning
                                                    @elseif ($task->priority == 'high') bg-danger
                                                    @elseif ($task->priority == 'critical') bg-dark
                                                    @endif">
                                                    {{ ucfirst($task->priority) }}
                                                </span>
                                            </td>
                                            <td>{{ $task->due_date ? $task->due_date->format('M d, Y') : 'No due date' }}</td>
                                            <td>
                                                <a href="{{ route('tasks.show', $task->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            No tasks are using this tag yet.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 