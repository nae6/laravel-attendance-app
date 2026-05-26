@extends('common.app')

@section('title', 'ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<h1 class="content__title">ログイン</h1>

@error('role')
<p class="form__error--role">{{ $message }}</p>
@enderror

<div class="auth-form">
    <form class="form" action="{{ route('login') }}" method="POST" novalidate>
        @csrf
        <input type="hidden" name="login_type" value="user">
        <div class="form__group">
            <label class="form__label" for="email">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}">
            @error('email')
            <p class="form__error">{{ $message }}</p>
            @enderror
        </div>
        <div class="form__group">
            <label class="form__label" for="password">パスワード</label>
            <input id="password" type="password" name="password">
            @error('password')
            <p class="form__error">{{ $message }}</p>
            @enderror
        </div>
        <button class="form__btn-submit" type="submit">ログインする</button>
    </form>
    <div class="auth__link">
        <a class="link" href="{{ route('register') }}">会員登録はこちら</a>
    </div>
</div>
@endsection