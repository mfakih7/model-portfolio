@extends('layouts.admin')

@section('page_title', 'Categories')

@section('page_actions')
<a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add Category</a>
@endsection

@section('content')
<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
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
