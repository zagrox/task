@extends('tasks.layout')

@section('title', 'Documentation')

@section('content')
<div class="container py-4">
    <!-- Hero Section -->
    <div class="card bg-primary text-white mb-5 border-0 rounded-3 shadow">
        <div class="card-body p-5">
            <h1 class="display-5 fw-bold mb-3">Task Manager Documentation</h1>
            <p class="lead mb-4">Everything you need to know about using and integrating with Task Manager</p>
            <div class="d-flex flex-wrap gap-2">
                <a href="#getting-started" class="btn btn-light text-primary">Get Started</a>
                <a href="{{ route('tasks.index') }}" class="btn btn-outline-light">Back to App</a>
            </div>
        </div>
    </div>
    
    <!-- Quick Navigation -->
    <div class="card mb-5 shadow-sm">
        <div class="card-header bg-white">
            <h4 class="mb-0">Quick Navigation</h4>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <a href="#getting-started" class="btn btn-outline-secondary">Getting Started</a>
                <a href="#basic-tutorials" class="btn btn-outline-secondary">Basic Tutorials</a>
                <a href="#advanced-tutorials" class="btn btn-outline-secondary">Advanced Tutorials</a>
                <a href="#user-guide" class="btn btn-outline-secondary">User Guide</a>
                <a href="#integration" class="btn btn-outline-secondary">Integration</a>
                <a href="#github" class="btn btn-outline-secondary">GitHub</a>
                <a href="#api-docs" class="btn btn-outline-secondary">API Documentation</a>
                <a href="#faq" class="btn btn-outline-secondary">FAQ</a>
                <a href="#help" class="btn btn-outline-secondary">Get Help</a>
            </div>
        </div>
    </div>
    
    <!-- Documentation Cards -->
    <div id="documentation-sections" class="row mb-5 g-4">
        <div class="col-md-4">
            <div id="getting-started" class="card h-100 shadow-sm">
                <div class="card-header bg-primary"></div>
                <div class="card-body p-4">
                    <div class="mb-3 text-center">
                        <span class="text-primary d-inline-block p-3 bg-primary bg-opacity-10 rounded-circle">
                            <i class="fas fa-bolt fa-2x"></i>
                        </span>
                    </div>
                    <h2 class="card-title h3 mb-3 text-center">Getting Started</h2>
                    <p class="card-text text-muted mb-4">Learn how to install and set up the Task Manager application on your system.</p>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-primary me-2"></i>
                            <span>Installation requirements</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-primary me-2"></i>
                            <span>Configuration steps</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-primary me-2"></i>
                            <span>Initial setup</span>
                        </li>
                    </ul>
                    <a href="{{ route('documentation.getting-started') }}" class="text-decoration-none text-primary d-block text-center">
                        Read Getting Started
                        <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div id="basic-tutorials" class="card h-100 shadow-sm">
                <div class="card-header bg-info"></div>
                <div class="card-body p-4">
                    <div class="mb-3 text-center">
                        <span class="text-info d-inline-block p-3 bg-info bg-opacity-10 rounded-circle">
                            <i class="fas fa-graduation-cap fa-2x"></i>
                        </span>
                    </div>
                    <h2 class="card-title h3 mb-3 text-center">Basic Tutorials</h2>
                    <p class="card-text text-muted mb-4">Step-by-step guides for common tasks in the Task Manager application.</p>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-info me-2"></i>
                            <span>Creating and managing tasks</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-info me-2"></i>
                            <span>Working with task lists</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-info me-2"></i>
                            <span>Using filters and reports</span>
                        </li>
                    </ul>
                    <a href="{{ route('documentation.basic-tutorials') }}" class="text-decoration-none text-info d-block text-center">
                        View Basic Tutorials
                        <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div id="advanced-tutorials" class="card h-100 shadow-sm">
                <div class="card-header bg-warning"></div>
                <div class="card-body p-4">
                    <div class="mb-3 text-center">
                        <span class="text-warning d-inline-block p-3 bg-warning bg-opacity-10 rounded-circle">
                            <i class="fas fa-brain fa-2x"></i>
                        </span>
                    </div>
                    <h2 class="card-title h3 mb-3 text-center">Advanced Tutorials</h2>
                    <p class="card-text text-muted mb-4">Master complex features and become a Task Manager power user.</p>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-warning me-2"></i>
                            <span>Task dependencies</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-warning me-2"></i>
                            <span>Custom fields and templates</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-warning me-2"></i>
                            <span>Workflow automation</span>
                        </li>
                    </ul>
                    <a href="{{ route('documentation.advanced-tutorials') }}" class="text-decoration-none text-warning d-block text-center">
                        Explore Advanced Tutorials
                        <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div id="user-guide" class="card h-100 shadow-sm">
                <div class="card-header bg-success"></div>
                <div class="card-body p-4">
                    <div class="mb-3 text-center">
                        <span class="text-success d-inline-block p-3 bg-success bg-opacity-10 rounded-circle">
                            <i class="fas fa-book fa-2x"></i>
                        </span>
                    </div>
                    <h2 class="card-title h3 mb-3 text-center">User Guide</h2>
                    <p class="card-text text-muted mb-4">Explore the features and learn how to use the Task Manager application effectively.</p>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Managing tasks</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Filtering and searching</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <span>Reports and analytics</span>
                        </li>
                    </ul>
                    <a href="{{ route('documentation.user-guide') }}" class="text-decoration-none text-success d-block text-center">
                        Explore User Guide
                        <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div id="integration" class="card h-100 shadow-sm">
                <div class="card-header bg-danger"></div>
                <div class="card-body p-4">
                    <div class="mb-3 text-center">
                        <span class="text-danger d-inline-block p-3 bg-danger bg-opacity-10 rounded-circle">
                            <i class="fas fa-plug fa-2x"></i>
                        </span>
                    </div>
                    <h2 class="card-title h3 mb-3 text-center">Integration</h2>
                    <p class="card-text text-muted mb-4">Connect Task Manager with other tools and systems in your workflow.</p>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-danger me-2"></i>
                            <span>API Integration</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-danger me-2"></i>
                            <span>Webhooks</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-danger me-2"></i>
                            <span>OAuth2 Authentication</span>
                        </li>
                    </ul>
                    <a href="{{ route('documentation.integration') }}" class="text-decoration-none text-danger d-block text-center">
                        View Integration Guide
                        <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div id="github" class="card h-100 shadow-sm">
                <div class="card-header bg-dark"></div>
                <div class="card-body p-4">
                    <div class="mb-3 text-center">
                        <span class="text-dark d-inline-block p-3 bg-dark bg-opacity-10 rounded-circle">
                            <i class="fab fa-github fa-2x"></i>
                        </span>
                    </div>
                    <h2 class="card-title h3 mb-3 text-center">GitHub Integration</h2>
                    <p class="card-text text-muted mb-4">Seamlessly connect Task Manager with your GitHub repositories.</p>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-dark me-2"></i>
                            <span>Sync with GitHub issues</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-dark me-2"></i>
                            <span>Automated workflows</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-dark me-2"></i>
                            <span>GitHub comment commands</span>
                        </li>
                    </ul>
                    <a href="{{ route('documentation.github') }}" class="text-decoration-none text-dark d-block text-center">
                        Explore GitHub Integration
                        <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div id="api-docs" class="card h-100 shadow-sm">
                <div class="card-header bg-purple"></div>
                <div class="card-body p-4">
                    <div class="mb-3 text-center">
                        <span class="text-purple d-inline-block p-3 bg-purple bg-opacity-10 rounded-circle">
                            <i class="fas fa-code fa-2x"></i>
                        </span>
                    </div>
                    <h2 class="card-title h3 mb-3 text-center">API Documentation</h2>
                    <p class="card-text text-muted mb-4">Integrate Task Manager with other applications using our RESTful API.</p>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-purple me-2"></i>
                            <span>Authentication</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-purple me-2"></i>
                            <span>Endpoints reference</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check-circle text-purple me-2"></i>
                            <span>Webhooks</span>
                        </li>
                    </ul>
                    <a href="{{ route('documentation.api') }}" class="text-decoration-none text-purple d-block text-center">
                        View API Docs
                        <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- FAQ Section -->
    <div id="faq" class="card mb-5 shadow-sm">
        <div class="card-header bg-white">
            <div class="d-flex align-items-center">
                <span class="bg-warning bg-opacity-10 p-2 rounded-circle me-3">
                    <i class="fas fa-question-circle text-warning"></i>
                </span>
                <h2 class="h3 mb-0">Frequently Asked Questions</h2>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item border-0 border-bottom">
                    <h3 class="accordion-header" id="headingOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                            How do I get support for Task Manager?
                        </button>
                    </h3>
                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            You can reach our support team by emailing support@taskmanager.com or by using the in-app support
                            feature located in the settings menu. We typically respond within 24 hours on business days.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item border-0 border-bottom">
                    <h3 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            Is there a limit to how many tasks I can create?
                        </button>
                    </h3>
                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            The free tier allows up to 100 active tasks. Premium plans offer unlimited tasks and additional
                            features like advanced reporting, team collaboration tools, and priority support.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item border-0 border-bottom">
                    <h3 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            Can I import tasks from other task management tools?
                        </button>
                    </h3>
                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            Yes, Task Manager supports importing tasks from CSV files, Trello, Asana, and Jira. Visit the 
                            Import/Export section in the settings to start the import process.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item border-0 border-bottom">
                    <h3 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            Does Task Manager work offline?
                        </button>
                    </h3>
                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            The web application requires an internet connection, but we offer mobile apps for iOS and Android
                            with offline functionality. Changes made offline will sync when you reconnect to the internet.
                        </div>
                    </div>
                </div>
                
                <div class="accordion-item border-0">
                    <h3 class="accordion-header" id="headingFive">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                            Is my data secure?
                        </button>
                    </h3>
                    <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            We take security seriously. All data is encrypted in transit and at rest. We use industry-standard
                            security practices including regular security audits and two-factor authentication options.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Help Section -->
    <div id="help" class="card bg-light border-0 rounded-3">
        <div class="card-body p-4">
            <div class="d-flex align-items-center mb-4">
                <span class="bg-info bg-opacity-10 p-2 rounded-circle me-3">
                    <i class="fas fa-headset text-info"></i>
                </span>
                <h2 class="h3 mb-0">Need More Help?</h2>
            </div>
            <p class="card-text text-muted mb-4">
                If you can't find what you're looking for in our documentation, our support team is here to help.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="#" class="btn btn-primary">
                    <i class="fas fa-envelope me-2"></i> Contact Support
                </a>
                <a href="#" class="btn btn-secondary">
                    <i class="fas fa-question-circle me-2"></i> Knowledge Base
                </a>
                <a href="#" class="btn btn-success">
                    <i class="fas fa-comments me-2"></i> Community Forum
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Bootstrap accordion handles toggling for us, no custom script needed
    // Added a custom style for purple text/background since it's not in Bootstrap by default
    document.addEventListener('DOMContentLoaded', function() {
        const style = document.createElement('style');
        style.textContent = `
            .text-purple { color: #6f42c1 !important; }
            .bg-purple { background-color: #6f42c1 !important; }
            .text-purple.bg-opacity-10 { background-color: rgba(111, 66, 193, 0.1) !important; }
        `;
        document.head.appendChild(style);
    });
</script>
@endpush 