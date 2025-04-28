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
        <a href="{{route('google-auth')}}" style="text-decoration: none;">Use Google to Login</a>
    </form>
@endsection
