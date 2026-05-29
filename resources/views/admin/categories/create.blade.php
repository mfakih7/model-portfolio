@extends('layouts.admin')

@section('page_title', 'Add Category')

@section('content')
<div class="admin-card">
    <form action="{{ route('admin.categories.store') }}" method="POST">
        @csrf
        @include('admin.categories._form')
        <button type="submit" class="btn btn-primary">Create Category</button>
    </form>
</div>
@endsection
