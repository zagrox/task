@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row">
        <!-- Documentation Sidebar -->
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Documentation</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('docs.getting-started') }}" class="list-group-item list-group-item-action {{ Request::routeIs('docs.getting-started') ? 'active' : '' }}">
                        <i class="fas fa-book me-2"></i>Getting Started
                    </a>
                    <a href="{{ route('docs.user-guide') }}" class="list-group-item list-group-item-action {{ Request::routeIs('docs.user-guide') ? 'active' : '' }}">
                        <i class="fas fa-user me-2"></i>User Guide
                    </a>
                    <a href="{{ route('docs.api') }}" class="list-group-item list-group-item-action {{ Request::routeIs('docs.api') ? 'active' : '' }}">
                        <i class="fas fa-code me-2"></i>API Reference
                    </a>
                    <a href="{{ route('docs.integration') }}" class="list-group-item list-group-item-action {{ Request::routeIs('docs.integration') ? 'active' : '' }}">
                        <i class="fas fa-plug me-2"></i>Integration Guide
                    </a>
                    <a href="{{ route('docs.faq') }}" class="list-group-item list-group-item-action {{ Request::routeIs('docs.faq') ? 'active' : '' }}">
                        <i class="fas fa-question-circle me-2"></i>FAQ
                    </a>
                </div>
            </div>
        </div>

        <!-- Documentation Content -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    @yield('doc-content')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 