@extends('layouts.admin')

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/history.css') }}">
@endsection

@section('content')
<h1 class="content-title">{{ $currentDate->format('Y年m月d日') }}の勤怠</h1>

@if (session('message'))
<p class="stamp">{{ session('message')}}</p>
@endif

<div class="date-nav font-setting">
    <a href="{{ route('admin.attendance.index', ['date' => $lastDate]) }}">
        <img src="{{ asset('images/arrow.png') }}" alt="left-arrow" class="arrow">
        前日
    </a>
    <span class="display-date">
        <img src="{{ asset('images/calender.png') }}" alt="calender-icon">
        {{ $currentDate->format('Y/m/d') }}
    </span>
    <a href="{{ route('admin.attendance.index', ['date' => $nextDate]) }}">
        翌日
        <img src="{{ asset('images/arrow.png') }}" alt="right-arrow" class="arrow arrow__right">
    </a>
</div>
<div class="table__wrapper font-setting">
    <table>
        <thead>
            <tr class="table__header">
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $attendance)
            <tr class="table__inner">
                <td>{{ $attendance->user->name }}</td>
                <td>{{ $attendance->check_in->format('H:i') }}</td>
                <td>{{ $attendance->check_out ? $attendance->check_out->format('H:i'): '' }}</td>
                <td>{{ $attendance->break_time }}</td>
                <td>{{ $attendance->work_time }}</td>
                <td class="detail__link">
                    <a href="{{ route('admin.attendance.edit', $attendance->id) }}">詳細</a>
                </td>
            </tr>
            @empty
            <tr class="table__inner">
                <td colspan="6">本日の勤怠はありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection