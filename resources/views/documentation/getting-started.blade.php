@extends('tasks.layout')

@section('title', 'Getting Started - Documentation')

@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            <div class="list-group">
                <a href="{{ route('documentation.index') }}" class="list-group-item list-group-item-action">Overview</a>
                <a href="{{ route('documentation.getting-started') }}" class="list-group-item list-group-item-action active">Getting Started</a>
                <a href="{{ route('documentation.basic-tutorials') }}" class="list-group-item list-group-item-action">Basic Tutorials</a>
                <a href="{{ route('documentation.advanced-tutorials') }}" class="list-group-item list-group-item-action">Advanced Tutorials</a>
                <a href="{{ route('documentation.user-guide') }}" class="list-group-item list-group-item-action">User Guide</a>
                <a href="{{ route('documentation.integration') }}" class="list-group-item list-group-item-action">Integration</a>
                <a href="{{ route('documentation.github') }}" class="list-group-item list-group-item-action">GitHub</a>
                <a href="{{ route('documentation.api') }}" class="list-group-item list-group-item-action">API</a>
            </div>
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h1>Getting Started with Task Manager</h1>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        Task Manager is a powerful Laravel-based application designed to help teams organize, track, and manage tasks efficiently. 
                        This guide will walk you through the installation process and help you get up and running quickly.
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h3 fw-bold mb-3" id="requirements">System Requirements</h2>
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item bg-transparent">PHP 8.0 or higher</li>
                                <li class="list-group-item bg-transparent">Composer</li>
                                <li class="list-group-item bg-transparent">MySQL, PostgreSQL, or SQLite database</li>
                                <li class="list-group-item bg-transparent">Node.js and NPM (for frontend assets)</li>
                                <li class="list-group-item bg-transparent">Git</li>
                            </ul>
                            <p class="text-muted fst-italic">
                                <i class="fas fa-info-circle me-1"></i> For optimal performance, we recommend using MySQL or PostgreSQL in production environments.
                            </p>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h3 fw-bold mb-3" id="installation">Installation</h2>
                            
                            <h3 class="h5 fw-bold mb-3">1. Clone the Repository</h3>
                            <div class="bg-light p-3 rounded mb-4 font-monospace small">
                                git clone https://github.com/your-organization/task-manager.git<br>
                                cd task-manager
                            </div>
                            
                            <h3 class="h5 fw-bold mb-3">2. Install Dependencies</h3>
                            <div class="bg-light p-3 rounded mb-4 font-monospace small">
                                composer install<br>
                                npm install<br>
                                npm run dev
                            </div>
                            
                            <h3 class="h5 fw-bold mb-3">3. Configure Environment</h3>
                            <div class="bg-light p-3 rounded mb-2 font-monospace small">
                                cp .env.example .env<br>
                                php artisan key:generate
                            </div>
                            <p class="mb-3 text-muted">
                                Open the .env file and configure your database connection:
                            </p>
                            <div class="bg-light p-3 rounded mb-4 font-monospace small">
                                DB_CONNECTION=mysql<br>
                                DB_HOST=127.0.0.1<br>
                                DB_PORT=3306<br>
                                DB_DATABASE=task_manager<br>
                                DB_USERNAME=root<br>
                                DB_PASSWORD=
                            </div>
                            
                            <h3 class="h5 fw-bold mb-3">4. Run Migrations</h3>
                            <div class="bg-light p-3 rounded mb-4 font-monospace small">
                                php artisan migrate
                            </div>
                            
                            <h3 class="h5 fw-bold mb-3">5. Seed the Database (Optional)</h3>
                            <div class="bg-light p-3 rounded mb-4 font-monospace small">
                                php artisan db:seed
                            </div>
                            
                            <h3 class="h5 fw-bold mb-3">6. Start the Development Server</h3>
                            <div class="bg-light p-3 rounded mb-4 font-monospace small">
                                php artisan serve
                            </div>
                            <p class="text-muted">
                                Your application should now be running at <a href="http://localhost:8000" class="text-primary text-decoration-none" target="_blank">http://localhost:8000</a>
                            </p>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h3 fw-bold mb-3" id="first-steps">First Steps</h2>
                            <p class="mb-3">
                                Now that you have installed Task Manager, here are some first steps to get you started:
                            </p>
                            <ol class="mb-4">
                                <li class="mb-2">Create a user account by registering on the login page</li>
                                <li class="mb-2">Explore the dashboard to get familiar with the interface</li>
                                <li class="mb-2">Create your first task by clicking the "New Task" button</li>
                                <li class="mb-2">Set up task categories and tags to organize your work</li>
                                <li class="mb-2">Invite team members to collaborate on your tasks</li>
                            </ol>
                            <p class="mb-3">
                                For more detailed instructions on how to use Task Manager, check out the <a href="{{ route('documentation.user-guide') }}" class="text-primary text-decoration-none">User Guide</a>.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                        <div></div>
                        <a href="{{ route('documentation.user-guide') }}" class="btn btn-primary">
                            Next: User Guide <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 