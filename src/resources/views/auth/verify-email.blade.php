@extends('layouts.user')

@section('title', 'メール認証')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<div class="auth-form verify-wrapper">
    <p class="verify-message">
        登録していただいたメールアドレスに認証メールを送付しました。<br>メール認証を完了してください。
    </p>
    <div class="verify-confirm">
        <a href="http://localhost:8025">認証はこちらから</a>
    </div>
    <form action="{{ route('verification.send') }}" method="POST" class="verify-resend">
        @csrf
        <button type="submit">認証メールを再送する</button>
    </form>
</div>
@endsection