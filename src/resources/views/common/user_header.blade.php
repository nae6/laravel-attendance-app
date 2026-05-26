<nav class="header__nav">
    <a href="{{ route('attendance') }}">勤怠</a>
    <a href="{{ route('attendance.index') }}">勤怠一覧</a>
    <a href="{{ route('request.list') }}">申請</a>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">ログアウト</button>
    </form>
</nav>