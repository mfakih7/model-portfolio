@extends('layouts.admin')

@section('page_title', 'Site Settings')

@section('content')
<div class="admin-card">
    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="admin-form">
        @csrf @method('PUT')

        <h5 class="mb-3">Model & Hero</h5>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Model Name *</label>
                <input type="text" name="model_name" class="form-control" value="{{ old('model_name', $settings->model_name) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Model Title *</label>
                <input type="text" name="model_title" class="form-control" value="{{ old('model_title', $settings->model_title) }}" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Intro Text</label>
            <textarea name="intro_text" class="form-control" rows="3">{{ old('intro_text', $settings->intro_text) }}</textarea>
        </div>
        <div class="mb-4">
            <label class="form-label">Hero Background Image</label>
            @if($settings->hero_image_url)<img src="{{ $settings->hero_image_url }}" class="d-block mb-2" style="max-height:100px" alt="">@endif
            <input type="file" name="hero_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        </div>

        <h5 class="mb-3">SEO</h5>
        <div class="mb-3">
            <label class="form-label">Meta Title</label>
            <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title', $settings->meta_title) }}">
        </div>
        <div class="mb-4">
            <label class="form-label">Meta Description</label>
            <textarea name="meta_description" class="form-control" rows="2">{{ old('meta_description', $settings->meta_description) }}</textarea>
        </div>

        <h5 class="mb-3">Social Media & Contact</h5>
        <div class="row">
            @foreach(['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'facebook' => 'Facebook', 'youtube' => 'YouTube', 'twitter' => 'X / Twitter', 'linkedin' => 'LinkedIn'] as $field => $label)
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ $label }} URL</label>
                <input type="url" name="{{ $field }}_url" class="form-control" value="{{ old($field.'_url', $settings->{$field.'_url'}) }}" placeholder="https://">
            </div>
            @endforeach
            <div class="col-md-6 mb-3">
                <label class="form-label">WhatsApp Number</label>
                <input type="text" name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $settings->whatsapp_number) }}" placeholder="Country code + number">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $settings->email) }}">
            </div>
        </div>

        <div class="admin-form-actions">
        <button type="submit" class="btn btn-primary btn-lg admin-btn-submit">Save Settings</button>
        </div>
    </form>
</div>
@endsection
