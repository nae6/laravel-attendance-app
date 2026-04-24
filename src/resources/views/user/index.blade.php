@extends('layouts.user')

@section('title', '勤怠登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('content')
<!-- switchを使ってステータスごとに表示変更する -->
<!-- status = 勤務外 -->
<div class="registration">
    <p class="status">勤務外</p>
    <p class="date">{{ $now_date }}</p>
    <p class="time">{{ $now_time }}</p>
    <form action="" method="POST" class="form__btn">
        <button class="form__btn--black">出勤</button>
    </form>
</div>

<!-- status = 出勤中 -->
<div class="registration">
    <p class="status">出勤中</p>
    <p class="date">{{ $now_date }}</p>
    <p class="time">{{ $now_time }}</p>
    <div class="btn-wrapper">
        <form action="#" method="POST" class="form__btn">
            <button class="form__btn--black">退勤</button>
        </form>
        <form action="#" method="POST" class="form__btn">
            <button class="form__btn--white">休憩入</button>
        </form>
    </div>
</div>

<!-- status = 休憩中 -->
<div class="registration">
    <p class="status">休憩中</p>
    <p class="date">{{ $now_date }}</p>
    <p class="time">{{ $now_time }}</p>
    <form action="#" method="POST" class="form__btn">
        <button class="form__btn--black">休憩戻</button>
    </form>
</div>

<!-- status = 退勤後 -->
<div class="registration">
    <p class="status">退勤済</p>
    <p class="date">{{ $now_date }}</p>
    <p class="time">{{ $now_time }}</p>
    <p class="see-you">お疲れ様でした。</p>
</div>
@endsection