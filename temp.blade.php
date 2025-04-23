@extends('tasks.layout')

@section('title', 'Repositories')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="my-4">Repositories</h1>
        <div>
            <a href="{{ route('repositories.create') }}" class="btn btn-primary me-2">
                <i class="fas fa-plus"></i> New Repository
            </a>
            <a href="{{ route('tasks.create') }}" class="btn btn-outline-primary">
                <i class="fas fa-plus"></i> New Task
            </a>
