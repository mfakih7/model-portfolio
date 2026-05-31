@extends('layouts.public')

@section('title', $settings->meta_title ?? $settings->model_name . ' | Fashion Model')

@if($settings->hero_image_url)
@push('head')
<link rel="preload" as="image" href="{{ $settings->hero_image_url }}" fetchpriority="high">
@endpush
@endif

@section('content')
<section class="hero-section" @if($settings->hero_image_url) style="background-image: url('{{ $settings->hero_image_url }}')" @endif>
    <div class="container hero-content">
        <div class="row">
            <div class="col-lg-8 fade-up">
                <p class="hero-subtitle mb-3">{{ $settings->model_title }}</p>
                <h1 class="hero-title display-font">{{ $settings->model_name }}</h1>
                <p class="hero-description mt-4 mb-5" style="max-width: 540px;">{{ $settings->intro_text }}</p>
                <a href="{{ route('portfolio') }}" class="btn btn-luxury">View Portfolio</a>
            </div>
        </div>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="row align-items-center mb-5 fade-up">
            <div class="col-lg-6">
                <span class="section-label">Introduction</span>
                <h2 class="display-font display-4 mb-4">Crafting Visual <span class="text-gold">Excellence</span></h2>
                <p class="section-description">{{ $about->short_bio ?? $settings->intro_text }}</p>
                <a href="{{ route('about') }}" class="btn btn-luxury mt-4">Read My Story</a>
            </div>
            <div class="col-lg-6 mt-5 mt-lg-0">
                @if($about->main_image_url)
                    <div class="about-image-wrap">
                        <img src="{{ $about->main_image_url }}" alt="{{ $settings->model_name }}" class="img-fluid w-100">
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>

@if($featuredImages->count())
<section class="section-padding pt-0">
    <div class="container fade-up">
        <div class="text-center mb-5">
            <span class="section-label">Selected Work</span>
            <h2 class="display-font display-4">Featured Portfolio</h2>
        </div>
        <div class="row g-4">
            @foreach($featuredImages as $image)
            <div class="col-md-6 col-lg-4">
                <div class="featured-card" data-lightbox
                     data-src="{{ $image->large_url }}"
                     data-download="{{ $image->large_url }}"
                     data-title="{{ $image->title }}"
                     data-description="{{ $image->description }}"
                     data-category="{{ $image->category->name }}">
                    <div class="skeleton"></div>
                    <img src="{{ $image->thumb_url }}"
                         alt="{{ $image->title }}"
                         width="600"
                         height="800"
                         loading="{{ $loop->index < 4 ? 'eager' : 'lazy' }}"
                         decoding="async"
                         @if($loop->index === 0) fetchpriority="high" @endif>
                    <span class="zoom-hint" aria-hidden="true"><i class="bi bi-zoom-in"></i></span>
                </div>
            </div>
            @endforeach
        </div>
        <div class="text-center mt-5">
            <a href="{{ route('portfolio') }}" class="btn btn-luxury-filled">View Full Portfolio</a>
        </div>
    </div>
</section>
@endif

@if($settings->whatsapp_link)
<section class="section-padding text-center fade-up">
    <div class="container">
        <span class="section-label">Bookings & Collaborations</span>
        <h2 class="display-font display-5 mb-4">Let's Create Something <span class="text-gold">Extraordinary</span></h2>
        <a href="{{ $settings->whatsapp_link }}" class="btn btn-whatsapp" target="_blank" rel="noopener">
            <i class="bi bi-whatsapp me-2"></i> Contact Me on WhatsApp
        </a>
    </div>
</section>
@endif
@endsection
