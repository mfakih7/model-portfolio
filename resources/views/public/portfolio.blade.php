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
        <div class="portfolio-grid fade-up" id="portfolioGrid">
            @include('partials.portfolio-grid-items', ['images' => $images, 'offset' => 0])
        </div>

        @if($hasMore)
        <div class="text-center mt-5 fade-up">
            <button type="button"
                    class="btn btn-luxury"
                    id="portfolioLoadMore"
                    data-offset="{{ $images->count() }}"
                    data-category="{{ $category ?? '' }}"
                    data-total="{{ $total }}">
                Load More
            </button>
        </div>
        @endif
        @else
        <div class="text-center py-5">
            <p class="text-muted">No portfolio images yet. Check back soon.</p>
        </div>
        @endif
    </div>
</section>
@endsection
