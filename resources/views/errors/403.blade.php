@extends('errors.layout')

@section('title', '403 - Akses Dilarang')

@section('gradient', 'linear-gradient(135deg, #ab47bc 0%, #8e24aa 100%)')

@section('content')
<div class="error-illustration floating">
    <svg viewBox="0 0 200 200" class="error-illustration">
        <rect x="70" y="90" width="60" height="50" fill="none" stroke="#ab47bc" stroke-width="4" rx="4"/>
        <path d="M85 90 V75 C85 65 95 60 100 60 C105 60 115 65 115 75 V90" fill="none" stroke="#ab47bc" stroke-width="4"/>
        <circle cx="100" cy="115" r="4" fill="#ab47bc"/>
    </svg>
</div>
<div class="error-code">403</div>
<h1 class="error-title">Akses Dilarang</h1>
<p class="error-description">
    Anda tidak memiliki izin untuk mengakses halaman ini. Silakan hubungi administrator jika Anda yakin seharusnya memiliki akses ke halaman ini.
</p>
<div class="error-actions">
    <a href="{{ url('/') }}" class="btn btn-primary">
        <svg class="icon" viewBox="0 0 24 24">
            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
        </svg>
        Kembali ke Beranda
    </a>
    @auth
        <a href="{{ route('home') }}" class="btn btn-secondary">
            <svg class="icon" viewBox="0 0 24 24">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            Dashboard
        </a>
    @else
        <a href="{{ route('login') }}" class="btn btn-secondary">
            <svg class="icon" viewBox="0 0 24 24">
                <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2Z"/>
            </svg>
            Login
        </a>
    @endauth
</div>
@endsection