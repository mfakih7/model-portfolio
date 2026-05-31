@extends('layouts.admin')

@section('page_title', 'Add Category')

@section('content')
<div class="admin-card">
    <form action="{{ route('admin.categories.store') }}" method="POST" class="admin-form">
        @csrf
        @include('admin.categories._form')
        <div class="admin-form-actions">
            <button type="submit" class="btn btn-admin-primary admin-btn-submit">Create Category</button>
        </div>
    </form>
</div>
@endsection
