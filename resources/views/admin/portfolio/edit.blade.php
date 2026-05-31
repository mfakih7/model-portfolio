@extends('layouts.admin')

@section('page_title', 'Edit Portfolio Image')

@section('content')
<div class="admin-card">
    <form action="{{ route('admin.portfolio.update', $image) }}" method="POST" enctype="multipart/form-data" class="admin-form">
        @csrf @method('PUT')
        <div class="mb-3 admin-current-image">
            <img src="{{ $image->medium_url }}" class="img-thumbnail admin-preview-current" alt="{{ $image->title }}">
            <a href="{{ $image->original_url }}" class="btn btn-outline-secondary admin-btn-download" target="_blank" rel="noopener" download><i class="bi bi-download"></i> Full resolution</a>
        </div>
        @include('admin.portfolio._form', ['image' => $image])
        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary btn-lg admin-btn-submit">Update Image</button>
            <a href="{{ route('admin.portfolio.index') }}" class="btn btn-link">Cancel</a>
        </div>
    </form>
</div>
@endsection
