<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <h3 class="display-font text-gold mb-3">{{ $settings->model_name }}</h3>
                <p class="text-muted small">{{ $settings->model_title }}</p>
            </div>
            <div class="col-lg-4 text-center">
                <p class="section-label mb-3">Follow</p>
                <div class="social-links">
                    @foreach($settings->social_links as $key => $social)
                        <a href="{{ $social['url'] }}" class="social-icon" target="_blank" rel="noopener noreferrer" aria-label="{{ $social['label'] }}">
                            <i class="bi {{ $social['icon'] }}"></i>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <p class="section-label mb-3">Quick Links</p>
                <ul class="list-unstyled footer-quick-links">
                    <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a></li>
                    <li><a href="{{ route('portfolio') }}" class="{{ request()->routeIs('portfolio') ? 'active' : '' }}">Portfolio</a></li>
                    <li><a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">About</a></li>
                    <li><a href="{{ route('contact') }}" class="{{ request()->routeIs('contact*') ? 'active' : '' }}">Contact</a></li>
                </ul>
            </div>
        </div>
        <hr class="my-4 border-secondary opacity-25">
        <p class="text-center text-muted small mb-0">
            &copy; {{ date('Y') }} {{ $settings->model_name }}. All rights reserved.
        </p>
    </div>
</footer>
