@extends('tasks.layout')

@section('title', 'AI Settings')

@section('content')
<div class="container">
    <h1 class="mb-4">
        <i class="fas fa-robot me-2 text-primary"></i>
        AI Settings
    </h1>
    
    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <ul class="nav nav-tabs" id="aiSettingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="assistant-tab" data-bs-toggle="tab" data-bs-target="#assistant" type="button" role="tab" aria-controls="assistant" aria-selected="true">
                        <i class="fas fa-robot me-2"></i> AI Assistant
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="task-generation-tab" data-bs-toggle="tab" data-bs-target="#task-generation" type="button" role="tab" aria-controls="task-generation" aria-selected="false">
                        <i class="fas fa-tasks me-2"></i> Task Generation
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="github-tab" data-bs-toggle="tab" data-bs-target="#github" type="button" role="tab" aria-controls="github" aria-selected="false">
                        <i class="fab fa-github me-2"></i> GitHub Integration
                    </button>
                </li>
            </ul>
            
            <div class="tab-content p-4" id="aiSettingsTabsContent">
                <!-- AI Assistant Settings -->
                <div class="tab-pane fade show active" id="assistant" role="tabpanel" aria-labelledby="assistant-tab">
                    <form action="{{ route('ai.settings.update-assistant') }}" method="POST">
                        @csrf
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="assistant_name" class="form-label">Assistant Name</label>
                                    <input type="text" class="form-control" id="assistant_name" name="name" value="{{ $aiAssistantConfig['assistant']['name'] ?? 'Task Dev Assistant' }}" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="assistant_version" class="form-label">Version</label>
                                    <input type="text" class="form-control" id="assistant_version" name="version" value="{{ $aiAssistantConfig['assistant']['version'] ?? '1.0.0' }}" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>About AI Assistant</strong>
                                    <p class="mt-2 mb-0">The AI assistant helps with various tasks in your project. Configure its responsibilities and behavior here.</p>
                                </div>
                            </div>
                        </div>
                        
                        <h4 class="mb-3">
                            <i class="fas fa-clipboard-list me-2 text-primary"></i>
                            Responsibilities
                        </h4>
                        
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered" id="responsibilitiesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Frequency</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($aiAssistantConfig['assistant']['responsibilities'] ?? [] as $index => $responsibility)
                                    <tr>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="responsibilities[{{ $index }}][id]" value="{{ $responsibility['id'] }}" required>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="responsibilities[{{ $index }}][type]" value="{{ $responsibility['type'] }}" required>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm" name="responsibilities[{{ $index }}][check_frequency]">
                                                <option value="continuous" {{ ($responsibility['check_frequency'] ?? '') == 'continuous' ? 'selected' : '' }}>Continuous</option>
                                                <option value="hourly" {{ ($responsibility['check_frequency'] ?? '') == 'hourly' ? 'selected' : '' }}>Hourly</option>
                                                <option value="daily" {{ ($responsibility['check_frequency'] ?? '') == 'daily' ? 'selected' : '' }}>Daily</option>
                                                <option value="weekly" {{ ($responsibility['check_frequency'] ?? '') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option value="monthly" {{ ($responsibility['check_frequency'] ?? '') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="responsibilities[{{ $index }}][description]" value="{{ $responsibility['description'] }}" required>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-responsibility">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mb-4">
                            <button type="button" class="btn btn-success" id="addResponsibility">
                                <i class="fas fa-plus me-2"></i>Add Responsibility
                            </button>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save AI Assistant Settings
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Task Generation Settings -->
                <div class="tab-pane fade" id="task-generation" role="tabpanel" aria-labelledby="task-generation-tab">
                    <form action="{{ route('ai.settings.update-task-generation') }}" method="POST">
                        @csrf
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="days" class="form-label">Days to Analyze</label>
                                    <input type="number" class="form-control" id="days" name="days" value="{{ $aiTaskGenerationConfig['days'] ?? 7 }}" min="1" max="30" required>
                                    <div class="form-text">Number of days of git history to analyze for task generation</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="min_changes" class="form-label">Minimum Changes</label>
                                    <input type="number" class="form-control" id="min_changes" name="min_changes" value="{{ $aiTaskGenerationConfig['min_changes'] ?? 5 }}" min="1" max="100" required>
                                    <div class="form-text">Minimum number of file changes needed to generate a task</div>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="auto_schedule" name="auto_schedule" value="1" {{ ($aiTaskGenerationConfig['auto_schedule'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="auto_schedule">Automatically Schedule Task Generation</label>
                                </div>
                                
                                <div class="mb-3" id="scheduleFrequencyGroup">
                                    <label for="schedule_frequency" class="form-label">Schedule Frequency</label>
                                    <select class="form-select" id="schedule_frequency" name="schedule_frequency">
                                        <option value="daily" {{ ($aiTaskGenerationConfig['schedule_frequency'] ?? '') == 'daily' ? 'selected' : '' }}>Daily</option>
                                        <option value="weekly" {{ ($aiTaskGenerationConfig['schedule_frequency'] ?? '') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                        <option value="monthly" {{ ($aiTaskGenerationConfig['schedule_frequency'] ?? '') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>About AI Task Generation</strong>
                                    <p class="mt-2">The AI Task Generator analyzes git history to automatically create tasks based on development patterns.</p>
                                    <p class="mb-0">It identifies frequently changed files and areas of code that might need refactoring or improvement.</p>
                                </div>
                                
                                <div class="card bg-light mt-3">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-terminal me-2"></i>
                                            Manual Generation
                                        </h5>
                                        <p class="card-text">You can manually trigger the AI task generation process anytime.</p>
                                        <a href="{{ route('tasks.generate-ai') }}" class="btn btn-primary">
                                            <i class="fas fa-robot me-2"></i>Generate AI Tasks Now
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="card bg-light mt-3">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-cogs me-2"></i>
                                            AI Task Processing
                                        </h5>
                                        <p class="card-text">Process pending tasks assigned to AI automatically.</p>
                                        <form action="{{ route('tasks.process-ai') }}" method="POST" class="d-flex flex-column gap-2">
                                            @csrf
                                            <div class="mb-2">
                                                <label for="limit" class="form-label">Maximum Tasks to Process</label>
                                                <input type="number" class="form-control" id="limit" name="limit" value="5" min="1" max="20">
                                                <div class="form-text">Number of tasks to process in one batch</div>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-play me-2"></i>Process AI Tasks Now
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Task Generation Settings
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- GitHub Integration Settings -->
                <div class="tab-pane fade" id="github" role="tabpanel" aria-labelledby="github-tab">
                    <form action="{{ route('ai.settings.update-github') }}" method="POST">
                        @csrf
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="repository" class="form-label">GitHub Repository</label>
                                    <input type="text" class="form-control" id="repository" name="repository" value="{{ $githubConfig['repository'] ?? '' }}" placeholder="username/repository" required>
                                    <div class="form-text">Format: username/repository</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="access_token" class="form-label">GitHub Access Token</label>
                                    <input type="password" class="form-control" id="access_token" name="access_token" value="{{ $githubConfig['access_token'] ?? '' }}">
                                    <div class="form-text">Leave empty to keep the current token</div>
                                </div>
                                
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="auto_sync" name="auto_sync" value="1" {{ ($githubConfig['auto_sync'] ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="auto_sync">Automatically Sync Tasks</label>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="sync_direction" class="form-label">Sync Direction</label>
                                    <select class="form-select" id="sync_direction" name="sync_direction">
                                        <option value="both" {{ ($githubConfig['sync_direction'] ?? '') == 'both' ? 'selected' : '' }}>Both Ways</option>
                                        <option value="to_github" {{ ($githubConfig['sync_direction'] ?? '') == 'to_github' ? 'selected' : '' }}>Tasks to GitHub Only</option>
                                        <option value="from_github" {{ ($githubConfig['sync_direction'] ?? '') == 'from_github' ? 'selected' : '' }}>GitHub to Tasks Only</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>About GitHub Integration</strong>
                                    <p class="mt-2">The GitHub Integration synchronizes tasks with GitHub issues.</p>
                                    <p class="mb-0">You'll need a GitHub Personal Access Token with appropriate permissions.</p>
                                </div>
                                
                                <div class="card bg-light mt-3">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fab fa-github me-2"></i>
                                            Manual Synchronization
                                        </h5>
                                        <p class="card-text">You can manually trigger the synchronization process anytime.</p>
                                        <a href="{{ route('tasks.sync-github') }}" class="btn btn-primary">
                                            <i class="fas fa-sync me-2"></i>Sync with GitHub Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save GitHub Integration Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Handle Auto Schedule toggle
        const autoScheduleCheckbox = document.getElementById('auto_schedule');
        const scheduleFrequencyGroup = document.getElementById('scheduleFrequencyGroup');
        
        function toggleScheduleFrequency() {
            scheduleFrequencyGroup.style.display = autoScheduleCheckbox.checked ? 'block' : 'none';
        }
        
        autoScheduleCheckbox.addEventListener('change', toggleScheduleFrequency);
        toggleScheduleFrequency(); // Initial state
        
        // Add Responsibility Button
        const addResponsibilityBtn = document.getElementById('addResponsibility');
        const responsibilitiesTable = document.querySelector('#responsibilitiesTable tbody');
        
        addResponsibilityBtn.addEventListener('click', function() {
            const rowCount = responsibilitiesTable.rows.length;
            const newRow = document.createElement('tr');
            
            newRow.innerHTML = `
                <td>
                    <input type="text" class="form-control form-control-sm" name="responsibilities[${rowCount}][id]" value="AI-${String(rowCount+1).padStart(3, '0')}" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="responsibilities[${rowCount}][type]" required>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="responsibilities[${rowCount}][check_frequency]">
                        <option value="continuous">Continuous</option>
                        <option value="hourly">Hourly</option>
                        <option value="daily" selected>Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="responsibilities[${rowCount}][description]" required>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-responsibility">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            
            responsibilitiesTable.appendChild(newRow);
            
            // Add event listener to the new remove button
            newRow.querySelector('.remove-responsibility').addEventListener('click', function() {
                responsibilitiesTable.removeChild(newRow);
            });
        });
        
        // Remove Responsibility Buttons
        document.querySelectorAll('.remove-responsibility').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('tr').remove();
            });
        });
    });
</script>
@endsection 