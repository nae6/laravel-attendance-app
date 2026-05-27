@extends('common.app')

@section('title', '修正申請承認画面')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<h1 class="content-title">勤怠詳細</h1>

@error('system_error')
<p class="form__error">{{ $message }}</p>
@enderror

<table class="table__wrapper font-setting">
    <tr class="table__row">
        <th>名前</th>
        <td>{{ $attendance->user->name }}</td>
    </tr>
    <tr class="table__row">
        <th>日付</th>
        <td>
            <span>
                {{ $attendance->check_in->format('Y年') }}
            </span>
            <span>
                {{ $attendance->check_in->format('n月j日') }}
            </span>
        </td>
    </tr>
    <tr class="table__row">
        <th>出勤・退勤</th>
        <td>
            <div class="time-input">
                <span>{{ $attendance->check_in->format('H:i') }}</span>
                <span>〜</span>
                <span>{{ $attendance->check_out?->format('H:i') }}</span>
            </div>
        </td>
    </tr>

    @forelse ($attendance->breakRecords as $index => $break)
    <tr class="table__row">
        <th>休憩{{ $index === 0 ? '' : $index + 1 }}</th>
        <td>
            <div class="time-input">
                <span>{{ $break->break_start?->format('H:i') }}</span>
                <span>〜</span>
                <span>{{ $break->break_end?->format('H:i') }}</span>
            </div>
        </td>
    </tr>
    @empty
    <tr class="table__row">
        <th>休憩</th>
        <td>
            <div class="time-input">
                <span></span>
                <span>〜</span>
                <span></span>
            </div>
        </td>
    </tr>
    @endforelse

    <tr class="table__row">
        <th>備考</th>
        <td>
            <textarea name="reason" class="note__content">
            {{ old('reason') }}
            </textarea>
        </td>
    </tr>
</table>

<form method="POST" action="{{ route('admin.request.update') }}" class="btn-wrapper">
    <button type="submit" class="form__btn">承認</button>
</form>

<div class="btn-wrapper">
    <p class="form__btn--gray">承認済み</p>
</div>

@endsection