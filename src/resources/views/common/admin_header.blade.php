<nav class="header__nav">
    <a href="{{ route('admin.attendance.index') }}">勤怠一覧</a>
    <a href="{{ route('staff.list') }}">スタップ一覧</a>
    <a href="{{ route('admin.request.list') }}">申請一覧</a>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <input type="hidden" name="login_type" value="admin">
        <button type="submit">ログアウト</button>
    </form>
</nav>