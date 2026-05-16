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
            <nav class="header__nav">
                <a href="{{ route('admin.attendance.index') }}">勤怠一覧</a>
                <a href="#">スタップ一覧</a>
                <a href="#">申請一覧</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <input type="hidden" name="login_type" value="admin">
                    <button type="submit">ログアウト</button>
                </form>
            </nav>
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