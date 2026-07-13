@extends('errors.layout')

@section('title', '503 - Layanan Tidak Tersedia')

@section('gradient', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)')

@section('content')
<div class="error-illustration floating">
    <svg viewBox="0 0 200 200" class="error-illustration">
        <circle cx="100" cy="100" r="60" fill="none" stroke="#667eea" stroke-width="4" opacity="0.3"/>
        <circle cx="100" cy="100" r="40" fill="none" stroke="#667eea" stroke-width="4" opacity="0.5"/>
        <circle cx="100" cy="100" r="20" fill="none" stroke="#667eea" stroke-width="4" opacity="0.7"/>
        <circle cx="100" cy="100" r="8" fill="#667eea"/>
        <path d="M100 60 L100 140 M60 100 L140 100" stroke="#667eea" stroke-width="3" opacity="0.4"/>
    </svg>
</div>
<div class="error-code">503</div>
<h1 class="error-title">Layanan Tidak Tersedia</h1>
<p class="error-description">
    Aplikasi sedang dalam pemeliharaan atau mengalami gangguan sementara. Silakan coba lagi dalam beberapa menit.
</p>
<div class="error-actions">
    <button class="btn btn-primary" onclick="location.reload()">
        <svg class="icon" viewBox="0 0 24 24">
            <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 8 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
        </svg>
        Coba Lagi
    </button>
    <a href="{{ url('/') }}" class="btn btn-secondary">
        <svg class="icon" viewBox="0 0 24 24">
            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
        </svg>
        Kembali ke Beranda
    </a>
</div>
@endsection