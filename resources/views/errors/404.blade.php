@extends('errors.layout')

@section('title', '404 - Halaman Tidak Ditemukan')

@section('gradient', 'linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%)')

@section('content')
<div class="error-illustration floating">
    <svg viewBox="0 0 200 200" class="error-illustration">
        <circle cx="100" cy="100" r="80" fill="none" stroke="#ff6b6b" stroke-width="4" opacity="0.3"/>
        <path d="M70 70 L130 130 M130 70 L70 130" stroke="#ff6b6b" stroke-width="6" stroke-linecap="round"/>
        <circle cx="100" cy="100" r="15" fill="#ff6b6b" opacity="0.5"/>
    </svg>
</div>
<div class="error-code">404</div>
<h1 class="error-title">Halaman Tidak Ditemukan</h1>
<p class="error-description">
    Maaf, halaman yang Anda cari tidak dapat ditemukan. Mungkin halaman telah dipindahkan, dihapus, atau URL yang Anda masukkan salah.
</p>
<div class="error-actions">
    <a href="{{ url('/') }}" class="btn btn-primary">
        <svg class="icon" viewBox="0 0 24 24">
            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
        </svg>
        Kembali ke Beranda
    </a>
    <button class="btn btn-secondary" onclick="history.back()">
        <svg class="icon" viewBox="0 0 24 24">
            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
        </svg>
        Halaman Sebelumnya
    </button>
</div>
@endsection