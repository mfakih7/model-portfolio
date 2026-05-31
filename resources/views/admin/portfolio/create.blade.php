@extends('layouts.admin')

@section('page_title', 'Add Portfolio Image')

@section('content')
<div class="admin-card">
    <form action="{{ route('admin.portfolio.store') }}" method="POST" enctype="multipart/form-data" class="admin-form">
        @csrf
        @include('admin.portfolio._form')
        <div class="admin-form-actions">
            <button type="submit" class="btn btn-primary btn-lg admin-btn-submit">Upload Image</button>
            <a href="{{ route('admin.portfolio.index') }}" class="btn btn-link">Cancel</a>
        </div>
    </form>
</div>
@endsection
