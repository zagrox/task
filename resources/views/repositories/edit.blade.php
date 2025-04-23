@extends('tasks.layout')

@section('title', 'Edit ' . $repository->name)

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('repositories.index') }}">Repositories</a></li>
                <li class="breadcrumb-item"><a href="{{ route('repositories.show', $repository) }}">{{ $repository->name }}</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Edit Repository
        </div>
        <div class="card-body">
            <form action="{{ route('repositories.update', $repository) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-3">
                    <label for="name" class="form-label">Repository Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $repository->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $repository->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="color" class="form-label">Color</label>
                    <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror" id="color" name="color" value="{{ old('color', $repository->color) }}" required>
                    @error('color')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="github_repo" class="form-label">GitHub Repository (optional)</label>
                    <div class="input-group">
                        <span class="input-group-text">github.com/</span>
                        <input type="text" class="form-control @error('github_repo') is-invalid @enderror" id="github_repo" name="github_repo" value="{{ old('github_repo', $repository->github_repo) }}" placeholder="username/repository">
                    </div>
                    <div class="form-text">Format: username/repository (e.g., octocat/Hello-World)</div>
                    @error('github_repo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="d-flex justify-content-between">
                    <div>
                        <a href="{{ route('repositories.show', $repository) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Update Repository</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Section -->
    <div class="card mb-4 border-danger">
        <div class="card-header bg-danger text-white">
            <i class="fas fa-trash me-1"></i>
            Remove Repository from Task Manager
        </div>
        <div class="card-body">
            <p class="mb-3">This will only remove the repository from Task Manager's listing. This action does not affect the actual Git repository.</p>
            <p class="mb-3">You cannot remove a repository that has tasks. To remove this repository from Task Manager, first remove all tasks or reassign them to another repository.</p>
            
            <form action="{{ route('repositories.destroy', $repository) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this repository from Task Manager? This will not affect the actual Git repository.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Remove from Task Manager</button>
            </form>
        </div>
    </div>
</div>
@endsection 