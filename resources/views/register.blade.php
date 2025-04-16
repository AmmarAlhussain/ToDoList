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
    </form>
@endsection
