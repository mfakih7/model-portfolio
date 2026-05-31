@extends('layouts.admin')

@section('page_title', 'Categories')

@section('page_actions')
<a href="{{ route('admin.categories.create') }}" class="btn btn-admin-primary admin-btn-action">
    <i class="bi bi-plus-lg"></i> Add Category
</a>
@endsection

@section('content')
<div class="admin-card admin-card-flush d-md-none">
    <div class="admin-mobile-list">
        @foreach($categories as $category)
        <article class="admin-list-card">
            <div class="admin-list-card-body">
                <h6 class="admin-list-card-title">{{ $category->name }}</h6>
                <code class="admin-list-card-sub">{{ $category->slug }}</code>
                <div class="admin-list-card-meta">
                    <span class="admin-pill admin-pill-muted">{{ $category->images_count }} images</span>
                    <span class="admin-pill {{ $category->status ? 'admin-pill-success' : 'admin-pill-muted' }}">
                        {{ $category->status ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
            <div class="admin-list-card-actions">
                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-admin-sm btn-admin-edit">Edit</a>
                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Delete category?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-admin-sm btn-admin-delete">Delete</button>
                </form>
            </div>
        </article>
        @endforeach
    </div>
</div>

<div class="admin-card d-none d-md-block">
    <div class="table-responsive admin-table-desktop">
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
</div>
@endsection
