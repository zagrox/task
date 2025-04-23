@extends('tasks.layout')

@section('title', 'ZagroxAI Settings')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">ZagroxAI Settings</h1>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">GitHub Integration Settings</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('zagroxai.settings.update') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="github_username">GitHub Username</label>
                                    <input type="text" class="form-control" id="github_username" name="github_username" 
                                        value="{{ $settings['github']['username'] }}" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="github_email">GitHub Email</label>
                                    <input type="email" class="form-control" id="github_email" name="github_email" 
                                        value="{{ $settings['github']['email'] }}" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="github_repository">GitHub Repository</label>
                                    <input type="text" class="form-control" id="github_repository" name="github_repository" 
                                        value="{{ $settings['github']['repository'] }}" required>
                                    <small class="form-text text-muted">Format: username/repository</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="github_token">GitHub Personal Access Token</label>
                                    <input type="password" class="form-control" id="github_token" name="github_token" 
                                        placeholder="Enter new token or leave blank to keep current">
                                    <small class="form-text text-muted">Token status: {{ empty($settings['github']['access_token']) ? 'Not configured' : 'Configured' }}</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="auto_assign_priority_threshold">Auto-Assign Priority Threshold</label>
                                    <select class="form-control" id="auto_assign_priority_threshold" name="auto_assign_priority_threshold" required>
                                        <option value="low" {{ $settings['tasks']['auto_assign_priority_threshold'] == 'low' ? 'selected' : '' }}>Low</option>
                                        <option value="medium" {{ $settings['tasks']['auto_assign_priority_threshold'] == 'medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="high" {{ $settings['tasks']['auto_assign_priority_threshold'] == 'high' ? 'selected' : '' }}>High</option>
                                        <option value="critical" {{ $settings['tasks']['auto_assign_priority_threshold'] == 'critical' ? 'selected' : '' }}>Critical</option>
                                    </select>
                                    <small class="form-text text-muted">Tasks with priority lower than this will be auto-assigned to AI</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="max_concurrent_tasks">Maximum Concurrent AI Tasks</label>
                                    <input type="number" class="form-control" id="max_concurrent_tasks" name="max_concurrent_tasks" 
                                        value="{{ $settings['tasks']['max_concurrent_tasks'] }}" min="1" max="20" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="default_branch">Default Branch</label>
                                    <input type="text" class="form-control" id="default_branch" name="default_branch" 
                                        value="{{ $settings['workflow']['default_branch'] }}" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Auto-Assign Task Types</label>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="type_documentation" name="auto_assign_types[]" value="documentation"
                                            {{ in_array('documentation', $settings['tasks']['auto_assign_types']) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="type_documentation">Documentation</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="type_testing" name="auto_assign_types[]" value="testing"
                                            {{ in_array('testing', $settings['tasks']['auto_assign_types']) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="type_testing">Testing</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="type_refactoring" name="auto_assign_types[]" value="refactoring"
                                            {{ in_array('refactoring', $settings['tasks']['auto_assign_types']) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="type_refactoring">Refactoring</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="type_optimization" name="auto_assign_types[]" value="optimization"
                                            {{ in_array('optimization', $settings['tasks']['auto_assign_types']) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="type_optimization">Optimization</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="type_dependency" name="auto_assign_types[]" value="dependency-update"
                                            {{ in_array('dependency-update', $settings['tasks']['auto_assign_types']) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="type_dependency">Dependency Updates</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <h5>Integration Settings</h5>
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="create_github_issues" name="create_github_issues" value="1"
                                            {{ $settings['integration']['create_github_issues'] ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="create_github_issues">Automatically create GitHub issues for AI tasks</label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="create_pull_requests" name="create_pull_requests" value="1"
                                            {{ $settings['integration']['create_pull_requests'] ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="create_pull_requests">Automatically create Pull Requests for completed AI tasks</label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="auto_label" name="auto_label" value="1"
                                            {{ $settings['integration']['auto_label'] ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="auto_label">Automatically add 'ai-generated' label to GitHub issues and PRs</label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="auto_review" name="auto_review" value="1"
                                            {{ $settings['workflow']['auto_review'] ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="auto_review">Automatically add ZagroxAI as a reviewer to PRs it didn't create</label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="auto_comment" name="auto_comment" value="1"
                                            {{ $settings['workflow']['auto_comment'] ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="auto_comment">Automatically comment on issues and PRs</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                            <a href="{{ route('zagroxai.dashboard') }}" class="btn btn-secondary ml-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">GitHub Webhook Setup</h5>
                </div>
                <div class="card-body">
                    <p>To enable two-way synchronization with GitHub, set up a webhook with these settings:</p>
                    
                    <div class="form-group">
                        <label>Payload URL</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="{{ url('/api/github/webhook') }}" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary copy-btn" data-clipboard-text="{{ url('/api/github/webhook') }}">
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Content Type</label>
                        <input type="text" class="form-control" value="application/json" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Events to Receive</label>
                        <ul>
                            <li>Issues</li>
                            <li>Issue comments</li>
                            <li>Pull requests</li>
                            <li>Push</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> You can set up a webhook secret in the .env file for enhanced security (ZAGROXAI_WEBHOOK_SECRET).
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize clipboard.js for copy button
        new ClipboardJS('.copy-btn');
        
        // Show success message when copy button clicked
        $('.copy-btn').click(function() {
            alert('Copied to clipboard!');
        });
    });
</script>
@endpush
@endsection