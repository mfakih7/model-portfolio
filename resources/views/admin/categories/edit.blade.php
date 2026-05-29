@extends('layouts.admin')

@section('page_title', 'Edit Category')

@section('content')
<div class="admin-card">
    <form action="{{ route('admin.categories.update', $category) }}" method="POST">
        @csrf @method('PUT')
        @include('admin.categories._form')
        <button type="submit" class="btn btn-primary">Update Category</button>
    </form>
</div>
@endsection
