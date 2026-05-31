@extends('layouts.admin')

@section('page_title', 'Categories')

@section('page_actions')
<a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm admin-btn-action"><i class="bi bi-plus-lg"></i> Add Category</a>
@endsection

@section('content')
<div class="admin-card">
    {{-- Desktop table --}}
    <div class="table-responsive admin-table-desktop d-none d-lg-block">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Name</th><th>Slug</th><th>Images</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td><code>{{ $category->slug }}</code></td>
                    <td>{{ $category->images_count }}</td>
                    <td><span class="badge {{ $category->status ? 'bg-success' : 'bg-secondary' }}">{{ $category->status ? 'Active' : 'Inactive' }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete category?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="admin-mobile-list d-lg-none">
        @foreach($categories as $category)
        <article class="admin-mobile-card">
            <div class="admin-mobile-card-info mb-2">
                <h6 class="admin-mobile-card-title mb-1">{{ $category->name }}</h6>
                <code class="small">{{ $category->slug }}</code>
            </div>
            <div class="admin-mobile-card-meta">
                <span class="admin-mobile-meta-item">
                    <span class="text-muted">Images</span>
                    <strong>{{ $category->images_count }}</strong>
                </span>
                <span class="admin-mobile-meta-item">
                    <span class="text-muted">Status</span>
                    <span class="badge {{ $category->status ? 'bg-success' : 'bg-secondary' }}">{{ $category->status ? 'Active' : 'Inactive' }}</span>
                </span>
            </div>
            <div class="admin-mobile-card-actions">
                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-outline-primary">Edit</a>
                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Delete category?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">Delete</button>
                </form>
            </div>
        </article>
        @endforeach
    </div>
</div>
@endsection
