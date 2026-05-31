@extends('layouts.admin')

@section('page_title', 'Edit Category')

@section('content')
<div class="admin-card">
    <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="admin-form">
        @csrf @method('PUT')
        @include('admin.categories._form')
        <div class="admin-form-actions">
            <button type="submit" class="btn btn-admin-primary admin-btn-submit">Update Category</button>
        </div>
    </form>
</div>
@endsection
