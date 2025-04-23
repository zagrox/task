@extends('tasks.layout')

@section('title', 'Create Repository')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('repositories.index') }}">Repositories</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create Repository</li>
            </ol>
        </nav>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-code-branch me-1"></i>
            Create Repository
        </div>
        <div class="card-body">
            <form action="{{ route('repositories.store') }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label for="name" class="form-label">Repository Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="color" class="form-label">Color</label>
                    <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror" id="color" name="color" value="{{ old('color', '#6c757d') }}" required>
                    @error('color')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="github_repo" class="form-label">GitHub Repository (optional)</label>
                    <div class="input-group">
                        <span class="input-group-text">github.com/</span>
                        <input type="text" class="form-control @error('github_repo') is-invalid @enderror" id="github_repo" name="github_repo" value="{{ old('github_repo') }}" placeholder="username/repository">
                    </div>
                    <div class="form-text">Format: username/repository (e.g., octocat/Hello-World)</div>
                    @error('github_repo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="{{ route('repositories.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Repository</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection 