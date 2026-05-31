@foreach($images as $image)
@php
    $index = ($offset ?? 0) + $loop->index;
    $eager = $index < 4;
@endphp
<div class="portfolio-item" data-lightbox
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
         loading="{{ $eager ? 'eager' : 'lazy' }}"
         decoding="async"
         @if($eager && $index === 0) fetchpriority="high" @endif>
    <span class="zoom-hint" aria-hidden="true"><i class="bi bi-zoom-in"></i></span>
    <div class="overlay">
        <span class="text-gold small text-uppercase">{{ $image->category->name }}</span>
        <h5 class="display-font mb-0">{{ $image->title }}</h5>
    </div>
</div>
@endforeach
