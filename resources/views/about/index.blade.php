@extends('tasks.layout')

@section('title', 'About App')

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">About Task Manager</h1>
    
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <ul class="nav nav-tabs" id="aboutTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="evolution-tab" data-bs-toggle="tab" data-bs-target="#evolution" type="button" role="tab" aria-controls="evolution" aria-selected="true">
                        <i class="fas fa-history me-2"></i>Evolution
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="goals-tab" data-bs-toggle="tab" data-bs-target="#goals" type="button" role="tab" aria-controls="goals" aria-selected="false">
                        <i class="fas fa-bullseye me-2"></i>Goals
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="features-tab" data-bs-toggle="tab" data-bs-target="#features" type="button" role="tab" aria-controls="features" aria-selected="false">
                        <i class="fas fa-star me-2"></i>Features
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="technical-tab" data-bs-toggle="tab" data-bs-target="#technical" type="button" role="tab" aria-controls="technical" aria-selected="false">
                        <i class="fas fa-code me-2"></i>Technical
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="workflow-tab" data-bs-toggle="tab" data-bs-target="#workflow" type="button" role="tab" aria-controls="workflow" aria-selected="false">
                        <i class="fas fa-sync-alt me-2"></i>Dev Workflow
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ai-assistant-tab" data-bs-toggle="tab" data-bs-target="#ai-assistant" type="button" role="tab" aria-controls="ai-assistant" aria-selected="false">
                        <i class="fas fa-robot me-2"></i>AI Assistant
                    </button>
                </li>
            </ul>
            
            <div class="tab-content p-4" id="aboutTabsContent">
                <!-- Evolution Tab -->
                <div class="tab-pane fade show active" id="evolution" role="tabpanel" aria-labelledby="evolution-tab">
                    <h3 class="mb-4">Project Evolution</h3>
                    
                    <div class="timeline">
                        <div class="timeline-item mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">1. Initial Concept & Planning</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li>Identified need for a task management system with AI capabilities</li>
                                        <li>Established Laravel as the framework of choice</li>
                                        <li>Created initial project structure and repository</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="timeline-item mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">2. Core Architecture Implementation</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li>Designed database schema for tasks, users, and related entities</li>
                                        <li>Implemented MVC architecture with controllers, models, and views</li>
                                        <li>Created RESTful API endpoints for task management</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="timeline-item mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">3. Feature Implementation</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li>Developed task CRUD operations</li>
                                        <li>Implemented version control system</li>
                                        <li>Created AI-assisted task generation and processing</li>
                                        <li>Added reporting and visualization features</li>
                                        <li>Built GitHub integration for issue synchronization</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="timeline-item mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">4. Enhancement & Refinement</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li>Modernized UI with responsive design</li>
                                        <li>Improved data visualization in reporting</li>
                                        <li>Enhanced AI capabilities with ZagroxAI integration</li>
                                        <li>Streamlined workflow with database migration</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Goals Tab -->
                <div class="tab-pane fade" id="goals" role="tabpanel" aria-labelledby="goals-tab">
                    <h3 class="mb-4">Project Goals</h3>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary p-3 rounded-circle text-white me-3">
                                            <i class="fas fa-tasks"></i>
                                        </div>
                                        <h4 class="mb-0">Efficient Task Management</h4>
                                    </div>
                                    <ul>
                                        <li>Centralize task tracking and management</li>
                                        <li>Support various task types and workflows</li>
                                        <li>Enable task dependencies and relationships</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-success p-3 rounded-circle text-white me-3">
                                            <i class="fas fa-robot"></i>
                                        </div>
                                        <h4 class="mb-0">AI Integration</h4>
                                    </div>
                                    <ul>
                                        <li>Automate routine task creation and management</li>
                                        <li>Utilize AI for task prioritization and assignment</li>
                                        <li>Generate intelligent insights from task data</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-info p-3 rounded-circle text-white me-3">
                                            <i class="fas fa-code-branch"></i>
                                        </div>
                                        <h4 class="mb-0">Developer-Friendly Workflows</h4>
                                    </div>
                                    <ul>
                                        <li>Seamless GitHub issue integration</li>
                                        <li>Version tracking and management</li>
                                        <li>Documentation and knowledge sharing</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-warning p-3 rounded-circle text-white me-3">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                        <h4 class="mb-0">Data-Driven Decision Making</h4>
                                    </div>
                                    <ul>
                                        <li>Comprehensive reporting dashboard</li>
                                        <li>Visual representation of project metrics</li>
                                        <li>Trend analysis and forecasting</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Features Tab -->
                <div class="tab-pane fade" id="features" role="tabpanel" aria-labelledby="features-tab">
                    <h3 class="mb-4">Core Features</h3>
                    
                    <div class="accordion mb-4" id="featuresAccordion">
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingTaskManagement">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTaskManagement" aria-expanded="true" aria-controls="collapseTaskManagement">
                                    <i class="fas fa-clipboard-list me-2"></i> Task Management
                                </button>
                            </h2>
                            <div id="collapseTaskManagement" class="accordion-collapse collapse show" aria-labelledby="headingTaskManagement" data-bs-parent="#featuresAccordion">
                                <div class="accordion-body">
                                    <ul>
                                        <li>Task creation, editing, and deletion</li>
                                        <li>Status tracking (pending, in-progress, completed, blocked, review)</li>
                                        <li>Priority levels (low, medium, high, critical)</li>
                                        <li>Assignment to users or AI</li>
                                        <li>Due date management</li>
                                        <li>Dependencies between tasks</li>
                                        <li>Tagging system for categorization</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingAIIntegration">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAIIntegration" aria-expanded="false" aria-controls="collapseAIIntegration">
                                    <i class="fas fa-robot me-2"></i> AI Integration (ZagroxAI)
                                </button>
                            </h2>
                            <div id="collapseAIIntegration" class="accordion-collapse collapse" aria-labelledby="headingAIIntegration" data-bs-parent="#featuresAccordion">
                                <div class="accordion-body">
                                    <ul>
                                        <li>Automatic task generation based on code analysis</li>
                                        <li>AI-based task processing and updates</li>
                                        <li>Intelligent task assignment</li>
                                        <li>Learning from completion patterns</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingReporting">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReporting" aria-expanded="false" aria-controls="collapseReporting">
                                    <i class="fas fa-chart-bar me-2"></i> Reporting & Visualization
                                </button>
                            </h2>
                            <div id="collapseReporting" class="accordion-collapse collapse" aria-labelledby="headingReporting" data-bs-parent="#featuresAccordion">
                                <div class="accordion-body">
                                    <ul>
                                        <li>Task status distribution charts</li>
                                        <li>Priority-based visualizations</li>
                                        <li>Feature and phase tracking</li>
                                        <li>Version-based reporting</li>
                                        <li>Deadline tracking (overdue, due today, upcoming)</li>
                                        <li>Completion rate metrics</li>
                                        <li>Filter-based reporting</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingVersionControl">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVersionControl" aria-expanded="false" aria-controls="collapseVersionControl">
                                    <i class="fas fa-code-branch me-2"></i> Version Control
                                </button>
                            </h2>
                            <div id="collapseVersionControl" class="accordion-collapse collapse" aria-labelledby="headingVersionControl" data-bs-parent="#featuresAccordion">
                                <div class="accordion-body">
                                    <ul>
                                        <li>Semantic versioning system (major.minor.patch)</li>
                                        <li>Release notes tracking</li>
                                        <li>Version history management</li>
                                        <li>Automatic task creation for version updates</li>
                                        <li>Git integration for commits and tags</li>
                                        <li>Database backups by version</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingGitHub">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGitHub" aria-expanded="false" aria-controls="collapseGitHub">
                                    <i class="fab fa-github me-2"></i> GitHub Integration
                                </button>
                            </h2>
                            <div id="collapseGitHub" class="accordion-collapse collapse" aria-labelledby="headingGitHub" data-bs-parent="#featuresAccordion">
                                <div class="accordion-body">
                                    <ul>
                                        <li>Task synchronization with GitHub issues</li>
                                        <li>Webhook support for real-time updates</li>
                                        <li>Pull request integration</li>
                                        <li>Commit message task references</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingDocumentation">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDocumentation" aria-expanded="false" aria-controls="collapseDocumentation">
                                    <i class="fas fa-book me-2"></i> Documentation
                                </button>
                            </h2>
                            <div id="collapseDocumentation" class="accordion-collapse collapse" aria-labelledby="headingDocumentation" data-bs-parent="#featuresAccordion">
                                <div class="accordion-body">
                                    <ul>
                                        <li>User guides</li>
                                        <li>API documentation</li>
                                        <li>Getting started guides</li>
                                        <li>Integration tutorials</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Technical Tab -->
                <div class="tab-pane fade" id="technical" role="tabpanel" aria-labelledby="technical-tab">
                    <h3 class="mb-4">Technical Architecture</h3>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-server me-2"></i> Backend</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li><strong>Framework:</strong> Laravel PHP</li>
                                        <li><strong>Database:</strong> MySQL/SQLite</li>
                                        <li><strong>Authentication:</strong> Laravel built-in auth</li>
                                        <li><strong>Task Storage:</strong> Database + JSON file backup</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-desktop me-2"></i> Frontend</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li><strong>Framework:</strong> Laravel Blade templates with Bootstrap</li>
                                        <li><strong>JavaScript:</strong> Chart.js for visualizations</li>
                                        <li><strong>CSS:</strong> Bootstrap with custom styling</li>
                                        <li><strong>Responsive Design:</strong> Mobile-first approach</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-plug me-2"></i> Integration Points</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li><strong>GitHub API:</strong> Issue synchronization</li>
                                        <li><strong>Git CLI:</strong> Version management</li>
                                        <li><strong>Command Line:</strong> Task generation and processing</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-warning text-white">
                                    <h5 class="mb-0"><i class="fas fa-rocket me-2"></i> Deployment & Operations</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li><strong>Version Updates:</strong> Artisan commands for version management</li>
                                        <li><strong>Database Migrations:</strong> Schema version control</li>
                                        <li><strong>Task Seeding:</strong> Sample data generation</li>
                                        <li><strong>Scheduled Jobs:</strong> Automated task processing and updates</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Dev Workflow Tab -->
                <div class="tab-pane fade" id="workflow" role="tabpanel" aria-labelledby="workflow-tab">
                    <h3 class="mb-4">Development Workflow</h3>
                    
                    <div class="flow-diagram mb-4">
                        <div class="row g-3">
                            <div class="col-md">
                                <div class="card h-100 border-0 shadow-sm bg-light">
                                    <div class="card-body text-center">
                                        <div class="rounded-circle bg-primary text-white mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <h2 class="mb-0">1</h2>
                                        </div>
                                        <h5>Feature Planning</h5>
                                        <ul class="text-start mb-0">
                                            <li>Identify requirements</li>
                                            <li>Create tasks in system</li>
                                            <li>Assign to developers or AI</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md">
                                <div class="card h-100 border-0 shadow-sm bg-light">
                                    <div class="card-body text-center">
                                        <div class="rounded-circle bg-primary text-white mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <h2 class="mb-0">2</h2>
                                        </div>
                                        <h5>Implementation</h5>
                                        <ul class="text-start mb-0">
                                            <li>Develop features according to tasks</li>
                                            <li>Update task status during development</li>
                                            <li>Document changes</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md">
                                <div class="card h-100 border-0 shadow-sm bg-light">
                                    <div class="card-body text-center">
                                        <div class="rounded-circle bg-primary text-white mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <h2 class="mb-0">3</h2>
                                        </div>
                                        <h5>Testing & QA</h5>
                                        <ul class="text-start mb-0">
                                            <li>Run automated tests</li>
                                            <li>Verify feature functionality</li>
                                            <li>Update task progress</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md">
                                <div class="card h-100 border-0 shadow-sm bg-light">
                                    <div class="card-body text-center">
                                        <div class="rounded-circle bg-primary text-white mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <h2 class="mb-0">4</h2>
                                        </div>
                                        <h5>Version Release</h5>
                                        <ul class="text-start mb-0">
                                            <li>Use version:update command</li>
                                            <li>Document changes in release notes</li>
                                            <li>Create Git tags and commits</li>
                                            <li>Update application version</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md">
                                <div class="card h-100 border-0 shadow-sm bg-light">
                                    <div class="card-body text-center">
                                        <div class="rounded-circle bg-primary text-white mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <h2 class="mb-0">5</h2>
                                        </div>
                                        <h5>Deployment</h5>
                                        <ul class="text-start mb-0">
                                            <li>Run database migrations</li>
                                            <li>Update production environment</li>
                                            <li>Verify functionality</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info d-flex">
                        <div class="me-3">
                            <i class="fas fa-info-circle fa-2x"></i>
                        </div>
                        <div>
                            <h5>Development Approach</h5>
                            <p class="mb-0">This comprehensive approach ensures efficient task management, leverages AI for automation, provides valuable insights through reporting, and maintains version control for reliable deployment.</p>
                        </div>
                    </div>
                </div>
                
                <!-- AI Assistant Tab -->
                <div class="tab-pane fade" id="ai-assistant" role="tabpanel" aria-labelledby="ai-assistant-tab">
                    <h3 class="mb-4">ZagroxAI - Intelligent Task Assistant</h3>
                    
                    <div class="alert alert-primary mb-4">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fas fa-info-circle fa-2x"></i>
                            </div>
                            <div>
                                <h5>What is ZagroxAI?</h5>
                                <p class="mb-0">ZagroxAI is an artificial intelligence assistant integrated into the Task Manager to automate task management, create GitHub issues, and help with documentation and code contributions.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i> Core Capabilities</h5>
                                </div>
                                <div class="card-body">
                                    <ul>
                                        <li><strong>Task Analysis:</strong> Automatically discovers and creates tasks from code changes</li>
                                        <li><strong>GitHub Integration:</strong> Creates and manages GitHub issues for assigned tasks</li>
                                        <li><strong>Smart Assignment:</strong> Automatically assigns appropriate tasks based on priority and type</li>
                                        <li><strong>Documentation:</strong> Helps maintain and update documentation as code evolves</li>
                                        <li><strong>Collaboration:</strong> Works alongside human developers to improve productivity</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i> Configuration</h5>
                                </div>
                                <div class="card-body">
                                    <p>ZagroxAI can be configured through the dedicated settings page:</p>
                                    <ul>
                                        <li><strong>GitHub Settings:</strong> Username, repository, and authentication</li>
                                        <li><strong>Task Assignment Rules:</strong> Control which tasks are auto-assigned based on tags and priority</li>
                                        <li><strong>Integration Options:</strong> Enable/disable GitHub issue creation and PR creation</li>
                                        <li><strong>Workflow Settings:</strong> Configure how ZagroxAI interacts with your development workflow</li>
                                    </ul>
                                    <a href="{{ route('zagroxai.settings') }}" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="fas fa-cog me-1"></i> Configure ZagroxAI
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="mb-3">Available Commands</h4>
                    
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Command</th>
                                    <th>Description</th>
                                    <th>Options</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>php artisan tasks:generate-ai</code></td>
                                    <td>Generate AI tasks based on git commit analysis</td>
                                    <td>
                                        <ul class="mb-0">
                                            <li><code>--days=7</code>: Number of days to analyze</li>
                                            <li><code>--min-changes=5</code>: Minimum changes to trigger task generation</li>
                                            <li><code>--auto-assign=1</code>: Auto-assign tasks to ZagroxAI</li>
                                            <li><code>--create-issues=0</code>: Create GitHub issues for tasks</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>php artisan tasks:process-ai</code></td>
                                    <td>Process pending AI tasks</td>
                                    <td>
                                        <ul class="mb-0">
                                            <li><code>--limit=5</code>: Maximum number of tasks to process</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>php artisan tasks:sync-to-github</code></td>
                                    <td>Synchronize tasks to GitHub issues</td>
                                    <td>
                                        <ul class="mb-0">
                                            <li><code>--task-id=ID</code>: Sync specific task by ID</li>
                                            <li><code>--status=STATUS</code>: Sync tasks with specific status</li>
                                            <li><code>--all</code>: Sync all tasks</li>
                                            <li><code>--repository=REPO</code>: GitHub repository format (owner/repo)</li>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <h4 class="mb-3">Task Assignment Rules</h4>
                    
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <p>ZagroxAI automatically assigns tasks based on the following criteria:</p>
                            
                            <h5 class="mt-3">1. Task Types</h5>
                            <p>Tasks with these tags are commonly assigned to ZagroxAI:</p>
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge bg-secondary">documentation</span>
                                <span class="badge bg-secondary">testing</span>
                                <span class="badge bg-secondary">refactoring</span>
                                <span class="badge bg-secondary">optimization</span>
                                <span class="badge bg-secondary">dependency-update</span>
                            </div>
                            
                            <h5 class="mt-3">2. Priority Threshold</h5>
                            <p>Tasks with priority below the configured threshold (default: medium) can be auto-assigned to ZagroxAI.</p>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-lightbulb me-2"></i> You can customize these rules in the ZagroxAI settings page.
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="mb-3">GitHub Integration</h4>
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-dark text-white">
                                    <h5 class="mb-0"><i class="fab fa-github me-2"></i> Issue Management</h5>
                                </div>
                                <div class="card-body">
                                    <p>When ZagroxAI creates GitHub issues:</p>
                                    <ul>
                                        <li>Issues are assigned to the ZagroxAI GitHub user</li>
                                        <li>Task metadata is included in the issue description</li>
                                        <li>Labels are applied based on task properties (priority, status, etc.)</li>
                                        <li>An "ai-generated" label is added to identify AI-managed issues</li>
                                        <li>Changes to issues are synchronized back to the task system</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-code-branch me-2"></i> Pull Request Workflow</h5>
                                </div>
                                <div class="card-body">
                                    <p>ZagroxAI can participate in the pull request workflow:</p>
                                    <ul>
                                        <li>Create pull requests for completed AI tasks</li>
                                        <li>Review pull requests created by human developers</li>
                                        <li>Comment on code changes with suggestions</li>
                                        <li>Respond to review comments and update PRs</li>
                                        <li>Follow project-specific contribution guidelines</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-item {
    position: relative;
}

.timeline-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 20px;
    height: 100%;
    width: 2px;
    background-color: var(--primary-color);
    opacity: 0.3;
}

.timeline-item:last-child::before {
    height: 50%;
}

.timeline-item .card {
    margin-left: 40px;
}

.timeline-item .card::before {
    content: '';
    position: absolute;
    top: 15px;
    left: -32px;
    height: 20px;
    width: 20px;
    border-radius: 50%;
    background-color: var(--primary-color);
}
</style>
@endsection 