@extends('layouts.admin')

@section('page_title', 'Edit About Story')

@section('content')
<div class="admin-card">
    <form action="{{ route('admin.about.update') }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $about->title) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Short Bio</label>
            <textarea name="short_bio" class="form-control" rows="3">{{ old('short_bio', $about->short_bio) }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Full Story</label>
            <textarea name="full_story" class="form-control" rows="10">{{ old('full_story', $about->full_story) }}</textarea>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Main Image</label>
                @if($about->main_image_url)<img src="{{ $about->main_image_url }}" class="d-block mb-2 thumb-preview" style="width:120px;height:auto" alt="">@endif
                <input type="file" name="main_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Additional Image</label>
                @if($about->additional_image_url)<img src="{{ $about->additional_image_url }}" class="d-block mb-2 thumb-preview" style="width:120px;height:auto" alt="">@endif
                <input type="file" name="additional_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Save About Content</button>
    </form>
</div>
@endsection
