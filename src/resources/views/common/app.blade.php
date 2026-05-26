<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '勤怠アプリ')</title>
    <link rel="stylesheet" href="{{ asset('css/common/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common/common.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <a href="/" class="header__logo">
                <img src="{{ asset('images/header_logo.png') }}" alt="coachtech logo">
            </a>
            @auth
                @if(Auth::user()->role === 'admin')
                    @include('common.admin_header')
                @else
                    @include('common.user_header')
                @endif
            @endauth
        </div>
    </header>

    <main>
        <div class="content">
            @yield('content')
        </div>
    </main>
</body>

</html>