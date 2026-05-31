<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $settings->meta_title ?? $settings->model_name)</title>
    <meta name="description" content="@yield('meta_description', $settings->meta_description ?? '')">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/public.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-public fixed-top">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">{{ $settings->model_name }}</a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="mainNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('portfolio') ? 'active' : '' }}" href="{{ route('portfolio') }}">Portfolio</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}" href="{{ route('about') }}">About</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('contact*') ? 'active' : '' }}" href="{{ route('contact') }}">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    <div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Image preview" aria-hidden="true">
        <button type="button" class="lightbox-close" aria-label="Close preview">&times;</button>
        <div class="lightbox-content">
            <div class="lightbox-figure">
                <img src="" alt="" class="lightbox-image">
            </div>
            <div class="lightbox-caption">
                <span class="lightbox-category"></span>
                <h4 class="lightbox-title display-font"></h4>
                <p class="lightbox-desc"></p>
                <a class="lightbox-download btn btn-sm btn-luxury" href="#" target="_blank" rel="noopener" download>
                    <i class="bi bi-download me-1"></i> Download image
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/public.js') }}"></script>
    @stack('scripts')
</body>
</html>
