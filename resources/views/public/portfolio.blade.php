@extends('layouts.public')

@section('title', 'Portfolio | ' . ($settings->meta_title ?? $settings->model_name))

@section('content')
<section class="section-padding" style="padding-top: 8rem;">
    <div class="container">
        <div class="text-center mb-5 fade-up">
            <span class="section-label">Gallery</span>
            <h1 class="display-font display-3">Portfolio</h1>
            <p class="section-description">Explore editorial, commercial, and campaign work</p>
        </div>

        <nav class="category-filters fade-up" aria-label="Portfolio categories">
            <a href="{{ route('portfolio') }}" class="category-filter {{ !$category ? 'active' : '' }}">All</a>
            @foreach($categories as $cat)
                <a href="{{ route('portfolio', $cat->slug) }}" class="category-filter {{ $category === $cat->slug ? 'active' : '' }}">{{ $cat->name }}</a>
            @endforeach
        </nav>

        @if($images->count())
        <div class="portfolio-grid fade-up">
            @foreach($images as $image)
            <div class="portfolio-item" data-lightbox
                 data-src="{{ $image->large_url }}"
                 data-download="{{ $image->large_url }}"
                 data-title="{{ $image->title }}"
                 data-description="{{ $image->description }}"
                 data-category="{{ $image->category->name }}">
                <div class="skeleton"></div>
                <img src="{{ $image->thumb_url }}" alt="{{ $image->title }}" loading="lazy" decoding="async">
                <span class="zoom-hint" aria-hidden="true"><i class="bi bi-zoom-in"></i></span>
                <div class="overlay">
                    <span class="text-gold small text-uppercase">{{ $image->category->name }}</span>
                    <h5 class="display-font mb-0">{{ $image->title }}</h5>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-5">
            <p class="text-muted">No portfolio images yet. Check back soon.</p>
        </div>
        @endif
    </div>
</section>
@endsection
