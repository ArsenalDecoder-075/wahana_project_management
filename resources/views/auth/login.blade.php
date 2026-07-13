@extends('layouts.guest')
<title>Login</title>

@section('content')
    <div class="container py-5 mb-6 h-100 object-image">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="card shadow-2-strong" style="border-radius: 1rem;">
                    <div class="card-body p-5">

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
@endsection
