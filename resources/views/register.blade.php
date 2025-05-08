@php
    $title = 'register';
    $css = 'login';
@endphp

@extends('header')

@section('content')
    <h1>Register</h1>

    <form action="{{ route('handleRegister') }}" method="POST" novalidate>
        @csrf

        <div class="input-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required placeholder="Enter your name"
                value="{{ old('name') }}" class="@error('name') input-error @enderror">
            @error('name')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="input-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required placeholder="Enter your email"
                value="{{ old('email') }}" class="@error('email') input-error @enderror">
            @error('email')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Enter your password"
                class="@error('password') input-error @enderror">
            @error('password')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="input-group">
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                placeholder="Confirm your password" class="@error('password_confirmation') input-error @enderror">
            @error('password_confirmation')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-footer">
            <button type="submit">Register</button>
            <p>Already have an account? <a href="{{ route('login') }}">Login here</a></p>
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
