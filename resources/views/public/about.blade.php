@extends('layouts.public')

@section('title', 'About | ' . ($settings->meta_title ?? $settings->model_name))

@section('content')
<section class="section-padding" style="padding-top: 8rem;">
    <div class="container">
        <div class="row align-items-center g-5 mb-5 fade-up">
            <div class="col-lg-6 order-lg-2">
                <span class="section-label">About</span>
                <h1 class="display-font display-3 mb-4">{{ $about->title }}</h1>
                <p class="lead text-gold">{{ $about->short_bio }}</p>
            </div>
            <div class="col-lg-6 order-lg-1">
                @if($about->main_image_url)
                <div class="about-image-wrap">
                    <img src="{{ $about->main_image_url }}" alt="{{ $settings->model_name }}" class="img-fluid w-100">
                </div>
                @else
                <div class="bg-dark ratio ratio-4x3 d-flex align-items-center justify-content-center">
                    <span class="text-muted">Upload main image in admin</span>
                </div>
                @endif
            </div>
        </div>

        <div class="row g-5 fade-up">
            @if($about->additional_image_url)
            <div class="col-lg-5">
                <img src="{{ $about->additional_image_url }}" alt="Additional" class="img-fluid w-100">
            </div>
            <div class="col-lg-7">
            @else
            <div class="col-12">
            @endif
                <div class="story-content" style="white-space: pre-line;">{{ $about->full_story }}</div>
            </div>
        </div>
    </div>
</section>
@endsection
