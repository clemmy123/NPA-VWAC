@extends('components.authentication')

@section('title', __('Login'))

@section('form')
    <h1 class="auth-title">{{ __('Login') }}</h1>
    <p class="auth-subtitle">{{ __('Sign in with your module account.') }}</p>

    <form method="POST" action="{{ route('login.store') }}">
        @csrf

        <div class="auth-group">
            <label for="email" class="auth-label">{{ __('Email') }}</label>
            <input type="email" name="email" id="email"
                   class="auth-input @error('email') is-invalid @enderror"
                   value="{{ old('email') }}"
                   required autocomplete="email" autofocus>
            @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>

        <div class="auth-group">
            <label for="password" class="auth-label">{{ __('Password') }}</label>
            <div class="auth-input-wrap">
                <input type="password" name="password" id="password"
                       class="auth-input @error('password') is-invalid @enderror"
                       placeholder="••••••••"
                       required autocomplete="current-password">
                <span class="auth-eye mdi mdi-eye-outline" id="togglePwd"></span>
            </div>
            @error('password') <span class="invalid-feedback">{{ $message }}</span> @enderror
        </div>

        <div class="auth-group">
            <label class="auth-check">
                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                {{ __('Remember me') }}
            </label>
        </div>

        <button type="submit" class="auth-btn">{{ __('Sign In') }}</button>
    </form>

    <script>
        document.getElementById('togglePwd').addEventListener('click', function () {
            var input = document.getElementById('password');
            var isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            this.classList.toggle('mdi-eye-outline', !isPassword);
            this.classList.toggle('mdi-eye-off-outline', isPassword);
        });
    </script>
@endsection
