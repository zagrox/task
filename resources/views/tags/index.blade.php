@extends('tasks.layout')

@section('title', 'Manage Tags')

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Tags</h5>
                    <a href="{{ route('tags.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Create Tag
                    </a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (count($tags) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Color</th>
                                        <th>Description</th>
                                        <th>Tasks</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tags as $tag)
                                        <tr>
                                            <td>
                                                <span class="badge" style="background-color: {{ $tag->color }};">{{ $tag->name }}</span>
                                            </td>
                                            <td>
                                                <span class="color-box" style="display: inline-block; width: 18px; height: 18px; background-color: {{ $tag->color }}; border-radius: 3px;"></span>
                                                {{ $tag->color }}
                                            </td>
                                            <td>{{ $tag->description ?? 'No description' }}</td>
                                            <td>{{ $tag->tasks->count() }}</td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('tags.edit', $tag->id) }}" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('tags.destroy', $tag->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this tag?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            No tags found. <a href="{{ route('tags.create') }}">Create your first tag</a>.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 