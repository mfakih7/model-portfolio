@extends('layouts.admin')

@section('page_title', 'Dashboard')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <p class="text-muted small mb-1">Portfolio Images</p>
            <h3>{{ $stats['images'] }}</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <p class="text-muted small mb-1">Categories</p>
            <h3>{{ $stats['categories'] }}</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <p class="text-muted small mb-1">Contact Messages</p>
            <h3>{{ $stats['messages'] }} <small class="text-muted fs-6">({{ $stats['unread_messages'] }} unread)</small></h3>
        </div>
    </div>
</div>

<div class="admin-card">
    <h5 class="mb-3">Quick Actions</h5>
    <div class="admin-quick-actions d-flex flex-wrap gap-2">
        <a href="{{ route('admin.portfolio.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Portfolio Image</a>
        <a href="{{ route('admin.about.edit') }}" class="btn btn-outline-secondary"><i class="bi bi-pencil me-1"></i> Edit About Story</a>
        <a href="{{ route('admin.settings.edit') }}" class="btn btn-outline-secondary"><i class="bi bi-share me-1"></i> Update Social Links</a>
        <a href="{{ route('admin.messages.index') }}" class="btn btn-outline-secondary"><i class="bi bi-envelope me-1"></i> View Messages</a>
    </div>
</div>
@endsection
