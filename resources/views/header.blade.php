<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/master.css">
    <link rel="stylesheet" href="/css/{{ $css }}.css">
    <title>{{ $title ?? '' }}</title>

</head>

<body>
    <header>
        <h1 class="logo center"><a href="{{ route('home') }}">To Do List System</a></h1>
        <nav>
            <ul>
                @guest
                    @if (Route::currentRouteName() === 'login')
                        <li><a href="{{ route('register') }}">Register</a></li>
                    @elseif (Route::currentRouteName() === 'register')
                        <li><a href="{{ route('login') }}">Login</a></li>
                    @else
                        <li><a href="{{ route('register') }}">Register</a></li>
                        <li><a href="{{ route('login') }}">Login</a></li>
                    @endif
                @endguest

                @auth
                    @if (Route::currentRouteName() != "addTaskPage")
                        <li><a href="{{ route('addTaskPage') }}">Add Task</a></li>
                    @endif
                    <li>
                        <form action="{{ route('logout') }}" method="POST" class="logout-form">
                            @csrf
                            <button type="submit" class="logout-btn">Log Out</button>
                        </form>
                    </li>
                @endauth
            </ul>
        </nav>
    </header>

    <section class="center">
        <div class="box">
            @yield('content')
        </div>
    </section>
    <footer>
        <p>To do List System</p>
    </footer>
</body>

</html>
