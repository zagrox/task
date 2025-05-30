<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Task Manager</title>
    
    <!-- Bootstrap 5.3.0 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.4.0 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #6c757d;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
            --dark-color: #5a5c69;
            --background-color: #f8f9fc;
            --border-color: #e3e6f0;
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--background-color);
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar-brand img {
            height: 30px;
            margin-right: 8px;
        }
        
        .navbar-brand {
            font-weight: 800;
            letter-spacing: 0.05em;
        }
        
        main {
            margin-top: 70px;
            flex: 1 0 auto;
            padding-bottom: 2rem;
        }
        
        footer {
            padding: 1.5rem 0;
            background-color: white;
            border-top: 1px solid var(--border-color);
            font-size: 0.875rem;
            color: var(--secondary-color);
            margin-top: 2rem;
        }
        
        /* Task cards styling */
        .task-card {
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .task-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }
        
        .task-card.priority-critical {
            border-left: 0.25rem solid var(--danger-color);
        }
        
        .task-card.priority-high {
            border-left: 0.25rem solid #e95420;
        }
        
        .task-card.priority-medium {
            border-left: 0.25rem solid var(--warning-color);
        }
        
        .task-card.priority-low {
            border-left: 0.25rem solid var(--info-color);
        }
        
        /* Status badges */
        .status-badge {
            font-size: 0.75rem;
            padding: 0.5em 0.8em;
            border-radius: 50rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-completed {
            background-color: var(--success-color);
            color: white;
        }
        
        .status-pending {
            background-color: var(--dark-color);
            color: white;
        }
        
        .status-in-progress {
            background-color: var(--primary-color);
            color: white;
        }
        
        .status-blocked {
            background-color: var(--danger-color);
            color: white;
        }
        
        /* Metrics cards */
        .metrics-card {
            border-radius: 0.375rem;
            border-left: 0.25rem solid;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 1.25rem;
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .metrics-card .icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            opacity: 0.4;
        }
        
        .metrics-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .metrics-card p {
            margin-bottom: 0;
            color: var(--secondary-color);
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        /* Table styles */
        .table {
            border-radius: 0.375rem;
            overflow: hidden;
        }
        
        .table thead th {
            background-color: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            color: #6e707e;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        
        .table tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        /* Progress bar */
        .progress {
            height: 0.625rem;
            border-radius: 1rem;
            background-color: #eaecf4;
        }
        
        /* Buttons */
        .btn {
            border-radius: 0.375rem;
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2653d4;
            border-color: #244ec9;
        }
        
        /* Priority row */
        tr.priority-critical {
            background-color: rgba(231, 74, 59, 0.05);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow">
        <div class="container">
            <a class="navbar-brand" href="{{ route('tasks.index') }}">
                <i class="fas fa-tasks me-2"></i>
                ZAGROX AI
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('tasks.index') ? 'active' : '' }}" href="{{ route('tasks.index') }}">
                            <i class="fas fa-list-ul me-1"></i> Tasks
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('zagroxai.*') ? 'active' : '' }}" href="{{ route('zagroxai.dashboard') }}">
                            <i class="fas fa-robot me-1"></i> AI
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('repositories.*') ? 'active' : '' }}" href="{{ route('repositories.index') }}">
                            <i class="fas fa-code-branch me-1"></i> Repos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('tasks.versions') ? 'active' : '' }}" href="{{ route('tasks.versions') }}">
                            <i class="fas fa-code-branch me-1"></i> Versions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('tasks.report') ? 'active' : '' }}" href="{{ route('tasks.report') }}">
                            <i class="fas fa-chart-bar me-1"></i> Insights
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('documentation.*') ? 'active' : '' }}" href="{{ route('documentation.index') }}">
                            <i class="fas fa-book me-1"></i> Docs
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> User
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item {{ request()->routeIs('user.settings') ? 'active' : '' }}" href="{{ route('user.settings') }}"><i class="fas fa-user-cog me-2"></i> Settings</a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('ai.settings') ? 'active' : '' }}" href="{{ route('ai.settings') }}"><i class="fas fa-robot me-2"></i> AI Settings</a></li>
                            <li><a class="dropdown-item {{ request()->routeIs('about.index') ? 'active' : '' }}" href="{{ route('about.index') }}"><i class="fas fa-info-circle me-2"></i> About App</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main content -->
    <main class="container py-4">
        <!-- Flash messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm mb-4" role="alert">
                <i class="fas fa-check-circle me-3 fa-lg"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm mb-4" role="alert">
                <i class="fas fa-exclamation-circle me-3 fa-lg"></i>
                <div>{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                    <div><strong>Please check the form for errors</strong></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <ul class="mb-0 ps-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        @yield('content')
    </main>
    
    <!-- Footer -->
    <footer class="bg-white">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; {{ date('Y') }} ZAGROX. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    @php
                        $versionData = json_decode(file_get_contents(base_path('version.json')), true);
                        $version = $versionData['major'] . '.' . $versionData['minor'] . '.' . $versionData['patch'];
                    @endphp
                    <p class="mb-0">Version {{ $version }}</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    
    @yield('scripts')
</body>
</html> 