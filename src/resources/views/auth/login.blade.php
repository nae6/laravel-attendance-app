@extends('layouts.user')

@section('title', 'ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<h1 class="content__title">ログイン</h1>

<div class="auth-form">
    <form class="form" action="{{ route('login') }}" method="POST" novalidate>
        @csrf
        <div class="form__group">
            <label class="form__label">メールアドレス</label>
            <div class="form__content">
                <input type="email" name="email" value="{{ old('email') }}">
            </div>
            @error('email')
            <p class="form__error">{{ $message }}</p>
            @enderror
        </div>
        <div class="form__group">
            <label class="form__label">パスワード</label>
            <div class="form__content">
                <input type="password" name="password">
            </div>
            @error('password')
            <p class="form__error">{{ $message }}</p>
            @enderror
        </div>
        <div class="form__btn">
            <button class="form__btn-submit" type="submit">ログインする</button>
        </div>
    </form>
    <div class="auth__link">
        <a class="link" href="{{ route('register') }}">会員登録はこちら</a>
    </div>
</div>
@endsection