@extends('layouts.admin')

@section('page_title', 'Edit Portfolio Image')

@section('content')
<div class="admin-card">
    <form action="{{ route('admin.portfolio.update', $image) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="mb-3">
            <img src="{{ $image->medium_url }}" class="img-thumbnail" style="max-height: 200px" alt="{{ $image->title }}">
            <a href="{{ $image->original_url }}" class="btn btn-sm btn-outline-secondary ms-2" target="_blank" rel="noopener" download><i class="bi bi-download"></i> Full resolution</a>
        </div>
        @include('admin.portfolio._form', ['image' => $image])
        <button type="submit" class="btn btn-primary">Update Image</button>
        <a href="{{ route('admin.portfolio.index') }}" class="btn btn-link">Cancel</a>
    </form>
</div>
@endsection
