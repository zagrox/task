@extends('tasks.layout')

@section('title', 'Version Management')

@section('content')
<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-code-branch text-primary me-2"></i>Version Management
        </h1>
        @if($canPush)
        <div>
            <button id="push-button" class="btn btn-primary">
                <i class="fas fa-upload me-1"></i> Push to Repository
            </button>
        </div>
        @endif
    </div>

    <div class="row">
        <!-- Current Version Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Current Version</div>
                            <div class="h2 mb-0 font-weight-bold text-gray-800">{{ $versionData['current']['version'] }}</div>
                            <div class="mt-2 small text-muted">Released on {{ $versionData['current']['date'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tag fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="mb-0"><strong>Release Notes:</strong></p>
                        <p>{{ $versionData['current']['notes'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Git Status Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Git Status</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Branch: {{ $gitStatus['branch'] }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-code-branch fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <p class="mb-2"><strong>Unpushed Commits ({{ count($gitStatus['unpushedCommits']) }}):</strong></p>
                        @if(count($gitStatus['unpushedCommits']) > 0)
                            <ul class="list-group">
                                @foreach($gitStatus['unpushedCommits'] as $commit)
                                <li class="list-group-item py-2 px-3">
                                    <span class="badge bg-secondary me-2">{{ substr($commit['hash'], 0, 7) }}</span>
                                    {{ $commit['message'] }}
                                </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0">No unpushed commits.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Uncommitted Changes Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Uncommitted Changes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ count($gitStatus['uncommittedFiles']) }} Files
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-edit fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        @if(count($gitStatus['uncommittedFiles']) > 0)
                            <ul class="list-group small">
                                @foreach($gitStatus['uncommittedFiles'] as $file)
                                <li class="list-group-item py-1 px-3">
                                    <span class="badge 
                                        @if($file['status'] == 'M ') bg-info
                                        @elseif($file['status'] == 'A ') bg-success
                                        @elseif($file['status'] == 'D ') bg-danger
                                        @elseif($file['status'] == '??') bg-secondary
                                        @else bg-warning @endif me-2">
                                        {{ $file['status'] }}
                                    </span>
                                    {{ $file['file'] }}
                                </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0">No uncommitted changes.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Version History Card -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Version History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Version</th>
                                    <th>Release Date</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($versionData['history'] as $version)
                                <tr>
                                    <td><span class="badge bg-primary">{{ $version['version'] }}</span></td>
                                    <td>{{ $version['date'] }}</td>
                                    <td>{{ $version['notes'] }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center">No version history available.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Push Modal -->
    <div class="modal fade" id="pushModal" tabindex="-1" aria-labelledby="pushModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pushModalLabel">Push to Repository</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to push version <strong>{{ $versionData['current']['version'] }}</strong> to the repository?</p>
                    
                    <div id="push-status" class="alert d-none"></div>
                    
                    <div class="progress d-none" id="push-progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                    </div>
                    
                    <div id="push-output" class="mt-3 d-none">
                        <p><strong>Output:</strong></p>
                        <pre class="bg-light p-2 rounded small" style="max-height: 200px; overflow-y: auto;"></pre>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirm-push">Push to Repository</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const pushButton = document.getElementById('push-button');
        const confirmPushButton = document.getElementById('confirm-push');
        const pushModal = new bootstrap.Modal(document.getElementById('pushModal'));
        const pushStatus = document.getElementById('push-status');
        const pushProgress = document.getElementById('push-progress');
        const pushOutput = document.getElementById('push-output');
        const outputPre = pushOutput.querySelector('pre');
        
        // Show push modal when button is clicked
        if (pushButton) {
            pushButton.addEventListener('click', function() {
                // Reset modal state
                pushStatus.classList.add('d-none');
                pushProgress.classList.add('d-none');
                pushOutput.classList.add('d-none');
                outputPre.textContent = '';
                
                // Show modal
                pushModal.show();
            });
        }
        
        // Handle push confirmation
        if (confirmPushButton) {
            confirmPushButton.addEventListener('click', function() {
                // Show progress
                pushProgress.classList.remove('d-none');
                confirmPushButton.disabled = true;
                
                // Make Ajax request
                fetch('{{ route("tasks.versions.push") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Hide progress
                    pushProgress.classList.add('d-none');
                    
                    // Show status
                    pushStatus.classList.remove('d-none');
                    pushStatus.classList.add(data.success ? 'alert-success' : 'alert-danger');
                    pushStatus.textContent = data.message;
                    
                    // Show output if available
                    if (data.output && data.output.length > 0) {
                        pushOutput.classList.remove('d-none');
                        outputPre.textContent = data.output.join('\n');
                    }
                    
                    // Reload page after success
                    if (data.success) {
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        confirmPushButton.disabled = false;
                    }
                })
                .catch(error => {
                    pushProgress.classList.add('d-none');
                    pushStatus.classList.remove('d-none');
                    pushStatus.classList.add('alert-danger');
                    pushStatus.textContent = 'Error: ' + error.message;
                    confirmPushButton.disabled = false;
                });
            });
        }
    });
</script>
@endsection 