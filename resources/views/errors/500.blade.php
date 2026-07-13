@extends('errors.layout')

@section('title', '500 - Server Error')

@section('gradient', 'linear-gradient(135deg, #ffa726 0%, #ff7043 100%)')

@section('content')
<div class="error-illustration floating">
    <svg viewBox="0 0 200 200" class="error-illustration">
        <rect x="60" y="60" width="80" height="80" fill="none" stroke="#ffa726" stroke-width="4" rx="8"/>
        <path d="M90 80 L90 120 M110 80 L110 120" stroke="#ffa726" stroke-width="4" stroke-linecap="round"/>
        <circle cx="100" cy="140" r="3" fill="#ffa726"/>
    </svg>
</div>
<div class="error-code">500</div>
<h1 class="error-title">Server Mengalami Masalah</h1>
<p class="error-description">
    Terjadi kesalahan pada server kami. Tim teknis sudah diberitahu dan sedang bekerja untuk memperbaiki masalah ini. Silakan coba lagi dalam beberapa saat.
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