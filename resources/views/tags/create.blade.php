@extends('tasks.layout')

@section('title', 'Create Tag')

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Create New Tag</h5>
                    <a href="{{ route('tags.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Tags
                    </a>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('tags.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">Tag Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                            <small class="text-muted">Choose a short, descriptive name for your tag</small>
                        </div>

                        <div class="mb-3">
                            <label for="color" class="form-label">Color <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="color" name="color" value="{{ old('color', '#3498db') }}">
                                <input type="text" class="form-control" id="colorHex" value="{{ old('color', '#3498db') }}" onchange="document.getElementById('color').value = this.value">
                            </div>
                            <small class="text-muted">Choose a color for your tag</small>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            <small class="text-muted">Optional description to explain the purpose of this tag</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_repository" name="is_repository" value="1" {{ old('is_repository') ? 'checked' : '' }} onchange="toggleRepositoryField()">
                                <label class="form-check-label" for="is_repository">
                                    This tag represents a GitHub repository
                                </label>
                            </div>
                            <small class="text-muted">Repository tags help organize tasks that should be synced to specific GitHub repositories</small>
                        </div>
                        
                        <div class="mb-3 repository-field" style="display: {{ old('is_repository') ? 'block' : 'none' }};">
                            <label for="repository_url" class="form-label">Repository URL</label>
                            <input type="text" class="form-control" id="repository_url" name="repository_url" value="{{ old('repository_url') }}" placeholder="e.g., username/repo-name">
                            <small class="text-muted">Enter the repository in the format username/repo-name</small>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Tag
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('color').addEventListener('input', function() {
        document.getElementById('colorHex').value = this.value;
    });
    
    function toggleRepositoryField() {
        const isRepository = document.getElementById('is_repository').checked;
        const repoFields = document.querySelectorAll('.repository-field');
        
        repoFields.forEach(field => {
            field.style.display = isRepository ? 'block' : 'none';
        });
    }
</script>
@endsection 