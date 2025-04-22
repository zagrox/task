@extends('tasks.layout')

@section('title', 'Repositories')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="my-4">Repositories</h1>
        <div>
            <a href="{{ route('repositories.github.sync-all') }}" class="btn btn-success me-2">
                <i class="fas fa-sync"></i> Sync Repositories from GitHub
            </a>
            <a href="{{ route('repositories.create') }}" class="btn btn-primary me-2">
                <i class="fas fa-plus"></i> New Repository
            </a>
            <a href="{{ route('tasks.create') }}" class="btn btn-outline-primary">
                <i class="fas fa-plus"></i> New Task
            </a>
        </div>
    </div>

    @if(empty(env('GITHUB_ORGANIZATION')))
    <div class="alert alert-warning mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Warning:</strong> GitHub organization is not configured. To sync repositories automatically, 
        please set <code>GITHUB_ORGANIZATION</code> in your <code>.env</code> file.
    </div>
    @endif

    <!-- Overall Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Total Repositories</h5>
                            <p class="display-4 mb-0">{{ $totalStats['repo_count'] }}</p>
                        </div>
                        <i class="fas fa-code-branch fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Overall Completion</h5>
                            <p class="display-4 mb-0">{{ $totalStats['completion_rate'] }}%</p>
                        </div>
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Total Tasks</h5>
                            <p class="display-4 mb-0">{{ $totalStats['task_count'] }}</p>
                        </div>
                        <i class="fas fa-tasks fa-3x opacity-50"></i>
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
                            <p class="display-4 mb-0">{{ $totalStats['in_progress_count'] }}</p>
                        </div>
                        <i class="fas fa-spinner fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Repositories List -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-code-branch me-1"></i>
            All Repositories
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Repository</th>
                            <th class="text-center">Total Tasks</th>
                            <th class="text-center">Completed</th>
                            <th class="text-center">In Progress</th>
                            <th class="text-center">Pending</th>
                            <th class="text-center">Completion Rate</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($repositories as $repo)
                            @php
                                $stats = $repoStats[$repo->id] ?? null;
                            @endphp
                            @if($stats)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-secondary me-2" style="background-color: {{ $repo->color ?? '#6c757d' }} !important;">
                                            <i class="fas fa-code-branch"></i>
                                        </span>
                                        <a href="{{ route('repositories.show', $repo->id) }}">{{ $repo->name }}</a>
                                    </div>
                                </td>
                                <td class="text-center">{{ $stats['task_count'] }}</td>
                                <td class="text-center text-success">{{ $stats['completed_count'] }}</td>
                                <td class="text-center text-warning">{{ $stats['in_progress_count'] }}</td>
                                <td class="text-center text-danger">{{ $stats['pending_count'] }}</td>
                                <td class="text-center">
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $stats['completion_rate'] }}%;"
                                             aria-valuenow="{{ $stats['completion_rate'] }}" aria-valuemin="0" aria-valuemax="100">
                                            {{ $stats['completion_rate'] }}%
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('repositories.show', $repo->id) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('repositories.edit', $repo->id) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Untagged Tasks -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-tasks me-1"></i>
            Untagged Tasks (No Repository)
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Total Tasks</h5>
                            <p class="display-5 mb-0">{{ $untaggedStats['task_count'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Completed</h5>
                            <p class="display-5 mb-0 text-success">{{ $untaggedStats['completed_count'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5 class="card-title">In Progress</h5>
                            <p class="display-5 mb-0 text-warning">{{ $untaggedStats['in_progress_count'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Completion Rate</h5>
                            <div class="progress mt-3">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $untaggedStats['completion_rate'] }}%;"
                                     aria-valuenow="{{ $untaggedStats['completion_rate'] }}" aria-valuemin="0" aria-valuemax="100">
                                    {{ $untaggedStats['completion_rate'] }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <a href="{{ route('tasks.index') }}" class="btn btn-outline-primary">View All Tasks</a>
            </div>
        </div>
    </div>
</div>
@endsection 