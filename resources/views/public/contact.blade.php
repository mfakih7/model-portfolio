@extends('layouts.public')

@section('title', 'Contact | ' . ($settings->meta_title ?? $settings->model_name))

@section('content')
<section class="section-padding" style="padding-top: 8rem;">
    <div class="container">
        <div class="text-center mb-5 fade-up">
            <span class="section-label">Get in Touch</span>
            <h1 class="display-font display-3">Contact</h1>
            <p class="section-description">Brands, agencies, and creative directors — let's connect</p>
        </div>

        <div class="row justify-content-center g-5">
            <div class="col-lg-5 fade-up text-center">
                @if($settings->whatsapp_link)
                <a href="{{ $settings->whatsapp_link }}" class="btn btn-whatsapp btn-lg w-100 mb-4" target="_blank" rel="noopener">
                    <i class="bi bi-whatsapp me-2 fs-4"></i><br>
                    Contact Me on WhatsApp
                </a>
                @endif
                @if($settings->email)
                <p class="text-muted"><i class="bi bi-envelope me-2"></i> <a href="mailto:{{ $settings->email }}" class="text-gold">{{ $settings->email }}</a></p>
                @endif
                <div class="mt-4">
                    @foreach($settings->social_links as $social)
                        <a href="{{ $social['url'] }}" class="social-icon" target="_blank" rel="noopener"><i class="bi {{ $social['icon'] }}"></i></a>
                    @endforeach
                </div>
            </div>

            <div class="col-lg-6 fade-up">
                <div class="contact-card">
                    <h3 class="display-font h4 mb-4">Send a Message</h3>
                    @if(session('success'))
                        <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success">{{ session('success') }}</div>
                    @endif
                    <form action="{{ route('contact.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <input type="text" name="name" class="form-control form-control-luxury @error('name') is-invalid @enderror" placeholder="Your Name" value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-4">
                            <input type="email" name="email" class="form-control form-control-luxury @error('email') is-invalid @enderror" placeholder="Your Email" value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-4">
                            <textarea name="message" rows="5" class="form-control form-control-luxury @error('message') is-invalid @enderror" placeholder="Your Message" required>{{ old('message') }}</textarea>
                            @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-luxury w-100">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
