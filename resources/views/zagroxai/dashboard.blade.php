@extends('tasks.layout')

@section('title', 'ZagroxAI Dashboard')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4">ZagroxAI Dashboard</h1>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total AI Tasks</h5>
                    <h2 class="card-text">{{ $stats['total'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Pending</h5>
                    <h2 class="card-text">{{ $stats['pending'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">In Progress</h5>
                    <h2 class="card-text">{{ $stats['in_progress'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Completed</h5>
                    <h2 class="card-text">{{ $stats['completed'] }}</h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">ZagroxAI GitHub Account</h5>
                    <a href="{{ route('zagroxai.settings') }}" class="btn btn-sm btn-outline-primary">Settings</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Username:</strong> {{ config('zagroxai.github.username') }}</p>
                            <p><strong>Email:</strong> {{ config('zagroxai.github.email') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Repository:</strong> {{ config('zagroxai.github.repository') }}</p>
                            <p><strong>API Connected:</strong> {{ config('zagroxai.github.access_token') ? 'Yes' : 'No' }}</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="https://github.com/{{ config('zagroxai.github.username') }}" target="_blank" class="btn btn-dark">
                            <i class="fab fa-github"></i> View GitHub Profile
                        </a>
                        @if(config('zagroxai.github.repository'))
                        <a href="https://github.com/{{ config('zagroxai.github.repository') }}" target="_blank" class="btn btn-dark ml-2">
                            <i class="fab fa-github"></i> View Repository
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">AI Task Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-primary" id="process-tasks" data-url="{{ route('zagroxai.process') }}">
                                Process Pending AI Tasks
                            </button>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('tasks.generate-ai') }}" class="btn btn-success">
                                Generate New AI Tasks
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">AI Tasks</h5>
        </div>
        <div class="card-body">
            @if(count($aiTasks) > 0)
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Feature</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($aiTasks as $task)
                        <tr>
                            <td>{{ $task['id'] }}</td>
                            <td>{{ $task['title'] }}</td>
                            <td>
                                <span class="badge 
                                    @if($task['status'] == 'completed') badge-success
                                    @elseif($task['status'] == 'in-progress') badge-info
                                    @elseif($task['status'] == 'pending') badge-warning
                                    @else badge-secondary
                                    @endif
                                ">
                                    {{ $task['status'] }}
                                </span>
                            </td>
                            <td>
                                <span class="badge 
                                    @if($task['priority'] == 'high') badge-danger
                                    @elseif($task['priority'] == 'medium') badge-warning
                                    @else badge-primary
                                    @endif
                                ">
                                    {{ $task['priority'] }}
                                </span>
                            </td>
                            <td>{{ $task['related_feature'] ?? 'Unknown' }}</td>
                            <td>{{ $task['due_date'] ?? 'Not set' }}</td>
                            <td>
                                <a href="{{ route('tasks.show', $task['id']) }}" class="btn btn-sm btn-primary">View</a>
                                <button class="btn btn-sm btn-success sync-task" data-id="{{ $task['id'] }}" data-url="{{ route('zagroxai.sync', $task['id']) }}">Sync to GitHub</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p>No AI tasks found.</p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Process tasks button
        $('#process-tasks').click(function() {
            const url = $(this).data('url');
            
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    limit: 5
                },
                success: function(response) {
                    if (response.success) {
                        alert(`Processed ${response.processed} tasks. ${response.errors} errors. ${response.pending} tasks remaining.`);
                        location.reload();
                    }
                },
                error: function() {
                    alert('Failed to process tasks. Please try again.');
                }
            });
        });
        
        // Sync task to GitHub
        $('.sync-task').click(function() {
            const url = $(this).data('url');
            const taskId = $(this).data('id');
            
            // Create a form and submit it
            const form = $('<form></form>');
            form.attr('method', 'POST');
            form.attr('action', url);
            form.append($('<input>').attr('type', 'hidden').attr('name', '_token').val('{{ csrf_token() }}'));
            $('body').append(form);
            form.submit();
        });
    });
</script>
@endpush
@endsection