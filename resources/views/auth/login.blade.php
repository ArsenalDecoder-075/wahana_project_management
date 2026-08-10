@extends('layouts.guest')
<title>Login</title>

@section('content')
<style>
    /* Full page background */
    body {
        margin: 0;
        padding: 0;
        background-image: url('{{ asset('images/wpm_background.png') }}');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
        min-height: 100vh;
        height: 100%;
    }

    .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;

    }

    .card {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(10px);
        border: none !important;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2) !important;
    }

    .card-body {
        padding: 2.5rem !important;
    }
</style>

<div class="login-container">
    <div class="container">
        <div class="row d-flex justify-content-center align-items-center">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="card shadow-2-strong" style="border-radius: 1rem;">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <img src="{{ asset('images/logo/loginlogo.png') }}" alt="Logo" class="img-fluid" style="max-height: 80px;">
                        </div>

                        <h1 class="mb-5 fw-bold">Sign in</h1>

                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="form-outline mb-4">
                                <input id="email" type="text"
                                    class="form-control @error('email') is-invalid @enderror" name="email"
                                    value="{{ old('email') }}" required autocomplete="email" autofocus>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <label for="email" class="col-md-4 col-form-label">{{ __('Username') }}</label>
                            </div>

                            <div class="form-outline mb-4">
                                <input id="password" type="password"
                                    class="form-control @error('password') is-invalid @enderror" name="password" required
                                    autocomplete="current-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <label for="password" class="col-md-4 col-form-label">{{ __('Password') }}</label>
                            </div>

                            <!-- Checkbox -->
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                    {{ old('remember') ? 'checked' : '' }}>

                                <label class="form-check-label" for="remember">
                                    {{ __('Remember Me') }}
                                </label>
                                <a class="float-end text-black" href="{{ route('password.request') }}">
                                    {{ __('Lupa Password?') }}
                                </a>
                            </div>

                            <div class="d-grid gap-1">
                                <button class="btn btn-success btn-lg" type="submit">Masuk</button>
                            </div>

                            <hr class="my-4">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
