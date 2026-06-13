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
        <td class="table__date--space">{{ $attendance->user->name }}</td>
    </tr>

    <tr class="table__row">
        <th>日付</th>
        <td>
            <div class="datetime">
                <span class="table__date--space">
                    {{ $correctRequest->requested_check_in->format('Y年') }}
                </span>
                <span class="table__date--space">
                    {{ $correctRequest->requested_check_in->format('n月j日') }}
                </span>
            </div>
        </td>
    </tr>

    <tr class="table__row">
        <th>出勤・退勤</th>
        <td>
            <div class="time-input datetime">
                <span class="table__date--space">
                    {{ $correctRequest->requested_check_in->format('H:i') }}
                </span>
                <span>〜</span>
                <span class="table__date--space">
                    {{ $correctRequest->requested_check_out?->format('H:i') }}
                </span>
            </div>
        </td>
    </tr>

    @forelse ($displayBreaks as $index => $break)
    <tr class="table__row">
        <th>休憩{{ $index === 0 ? '' : $index + 1 }}</th>
        <td>
            <div class="time-input datetime">
                <span class="table__date--space">
                    {{ $break->requested_break_start?->format('H:i') }}
                </span>
                <span>〜</span>
                <span class="table__date--space">
                    {{ $break->requested_break_end?->format('H:i') }}
                </span>
            </div>
        </td>
    </tr>
    @empty
    <tr class="table__row">
        <th>休憩</th>
        <td>
            <div class="time-input datetime">
                <span class="table__date--space"></span>
                <span>〜</span>
                <span class="table__date--space"></span>
            </div>
        </td>
    </tr>
    @endforelse

    <tr class="table__row">
        <th>備考</th>
        <td>
            <p name="reason" class="reason">
                {{ $correctRequest->reason }}
            </p>
        </td>
    </tr>
</table>

@if($correctRequest->isApproved())
<div class="btn-wrapper">
    <p class="form__btn--gray">承認済み</p>
</div>
@else
<form method="POST" action="{{ route('admin.request.approve', $correctRequest) }}" class="btn-wrapper">
    @csrf
    @method('PUT')
    <button type="submit" class="form__btn">承認</button>
</form>
@endif
@endsection