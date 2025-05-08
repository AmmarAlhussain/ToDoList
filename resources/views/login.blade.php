@php
    $title = 'login';
    $css = 'login';
@endphp

@extends('header')

@section('content')
    <h1>Login</h1>

    <form action="{{ route('handleLogin') }}" method="POST" novalidate>
        @csrf

        @if ($errors->has('email') && $errors->first('email') === 'Your email or your password incorrect')
            <div class="error-message">{{ $errors->first('email') }}</div>
        @endif

        <div class="input-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required placeholder="Enter your email"
                value="{{ old('email') }}" class="@if ($errors->has('email')) input-error @endif">
        </div>

        <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Enter your password"
                class="@if ($errors->has('email')) input-error @endif">
        </div>

        <div class="form-footer">
            <button type="submit">Login</button>
            <p>Don't have an account? <a href="{{ route('register') }}">Register here</a></p>
        </div>    
        <hr style="margin-bottom: 10px; margin-top: 10px;">
        <a href="{{ route('google-auth') }}" 
        style="display: inline-flex; align-items: center; justify-content: center; width: 240px; height: 40px; border: 1px solid #dadce0; border-radius: 4px; background-color: white; color: #3c4043; font-size: 14px; font-family: Roboto, sans-serif; text-decoration: none; box-shadow: 0 1px 1px rgba(0,0,0,0.1); transition: background-color 0.2s;">
        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" 
             alt="Google logo" style="width: 18px; height: 18px; margin-right: 8px;">
        Sign in with Google
     </a>
    </form>
@endsection
