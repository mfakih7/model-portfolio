@extends('layouts.admin')

@section('page_title', 'Add Portfolio Image')

@section('content')
<div class="admin-card">
    <form action="{{ route('admin.portfolio.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('admin.portfolio._form')
        <button type="submit" class="btn btn-primary">Upload Image</button>
        <a href="{{ route('admin.portfolio.index') }}" class="btn btn-link">Cancel</a>
    </form>
</div>
@endsection
