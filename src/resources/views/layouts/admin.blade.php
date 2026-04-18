<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '勤怠アプリ')</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <a href="/" class="header__logo">
                <img src="{{ asset('images/header_logo.png') }}" alt="coachtech logo">
            </a>

            @auth
            <nav class="header__nav">
                <a href="{{ route('') }}">勤怠一覧</a>
                <a href="{{ route('') }}">スタップ一覧</a>
                <a href="{{ route('') }}">申請一覧</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">ログアウト</button>
                </form>
            </nav>
            @endauth
        </div>
    </header>

    <main>
        @yield('content')
    </main>
</body>

</html>