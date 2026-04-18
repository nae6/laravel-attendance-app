@extends('layouts.user')

@section('title', '新規会員登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<h1 class="content__title">会員登録</h1>
<div class="auth-form">
    <form class="form" action="{{ route('register') }}" method="POST" novalidate>
        @csrf
        <div class="form__group">
            <label class="form__label" for="name">名前</label>
            <input type="text" name="name" value="{{ old('name') }}">
            @error('name')
            <p class="form__error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form__group">
            <label class="form__label" for="email">メールアドレス</label>
            <input type="email" name="email" value="{{ old('email') }}">
            @error('email')
            <p class="form__error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form__group">
            <label class="form__label" for="password">パスワード</label>
            <input type="password" name="password">
            @error('password')
            <p class="form__error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form__group">
            <label class="form__label" for="password_confirmation">パスワード確認</label>
            <input type="password" name="password_confirmation">
            @error('password_confirmation')
            <p class="form__error">{{ $message }}</p>
            @enderror
        </div>

        <button class="form__btn-submit" type="submit">登録する</button>
    </form>
    <div class="auth__link">
        <a class="link" href="{{ route('login') }}">ログインはこちら</a>
    </div>
</div>
@endsection