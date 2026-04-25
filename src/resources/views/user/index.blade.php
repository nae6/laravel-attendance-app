@extends('layouts.user')

@section('title', '勤怠登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('content')
<div class="registration">
    <p class="status">{{ $status }}</p>
    <p class="date">{{ $now_date }}</p>
    <p class="time">{{ $now_time }}</p>
    @if (session('message'))
    <p class="stamp">{{ session('message')}}</p>
    @endif
    @switch($status)
    @case('勤務外')
    <form action="{{ route('attendance.start') }}" method="POST" class="form__btn">
        @csrf
        <button class="form__btn--black">出勤</button>
    </form>
    @break
    @case('出勤中')
    <div class="btn-wrapper">
        <form action="{{ route('attendance.end') }}" method="POST" class="form__btn">
            @csrf
            <button class="form__btn--black">退勤</button>
        </form>
        <form action="#" method="POST" class="form__btn">
            @csrf
            <button class="form__btn--white">休憩入</button>
        </form>
    </div>
    @break
    @case('休憩中')
    <form action="#" method="POST" class="form__btn">
        @csrf
        <button class="form__btn--black">休憩戻</button>
    </form>
    @break
    @case('退勤済')
    <p class="see-you">お疲れ様でした。</p>
    @break
    @endswitch
</div>
@endsection