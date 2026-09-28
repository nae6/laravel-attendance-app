@extends('common.app')

@section('title', '勤怠登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('content')
@php use App\Enums\AttendanceStatus; @endphp
<div class="registration">
    <p class="status">{{ $status->value }}</p>
    <p class="date">{{ $now_date }}</p>
    <p class="time" id="attendance-clock" data-is-fixed="{{ $status === AttendanceStatus::Finished ? 'true' : 'false' }}">
        {{ $now_time }}
    </p>
    @if (session('message'))
    <p class="stamp">{{ session('message')}}</p>
    @endif
    @switch($status)
    @case(AttendanceStatus::OffDuty)
    <form action="{{ route('attendance.start') }}" method="POST" class="form__btn">
        @csrf
        <button type="submit" class="form__btn--black">出勤</button>
    </form>
    @break
    @case(AttendanceStatus::Working)
    <div class="btn-wrapper">
        <form action="{{ route('attendance.end') }}" method="POST" class="form__btn">
            @csrf
            <button type="submit" class="form__btn--black">退勤</button>
        </form>

        <form action="{{ route('break.start') }}" method="POST" class="form__btn">
            @csrf
            <button type="submit" class="form__btn--white">休憩入</button>
        </form>
    </div>
    @break
    @case(AttendanceStatus::OnBreak)
    <form action="{{ route('break.end') }}" method="POST" class="form__btn">
        @csrf
        <button type="submit" class="form__btn--black">休憩戻</button>
    </form>
    @break
    @case(AttendanceStatus::Finished)
    <p class="see-you">お疲れ様でした。</p>
    @break
    @endswitch
</div>
@endsection
