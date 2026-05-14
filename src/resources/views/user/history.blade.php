@extends('layouts.user')

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/history.css') }}">
@endsection

@section('content')
<h1 class="content-title">勤怠一覧</h1>
<div class="month-nav font-setting">
    <a href="{{ route('attendance.index', ['month' => $lastMonth]) }}">
        <img src="{{ asset('images/arrow.png') }}" alt="left-arrow" class="arrow">
        前月
    </a>
    <span class="display-month">
        <img src="{{ asset('images/calender.png') }}" alt="calender-icon">
        {{ $currentMonth->format('Y/m') }}
    </span>
    <a href="{{ route('attendance.index', ['month' => $nextMonth]) }}">
        翌月
        <img src="{{ asset('images/arrow.png') }}" alt="right-arrow" class="arrow arrow__right">
    </a>
</div>
<div class="table__wrapper font-setting">
    <table>
        <thead>
            <tr class="table__header">
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dates as $date)
            @php
            $attendance = $attendances->get($date->format('Y-m-d'));
            @endphp
            <tr class="table__inner">
                <td>{{ $date->format('m/d') }}({{ $date->isoFormat('ddd') }})</td>
                <td>{{ $attendance ? $attendance->check_in->format('H:i') : '' }}</td>
                <td>{{ $attendance && $attendance->check_out ? $attendance->check_out->format('H:i'): '' }}</td>
                <td>{{ $attendance?->break_time }}</td>
                <td>{{ $attendance?->work_time }}</td>
                <td class="detail__link">
                    @if ($attendance)
                    <a href="{{ route('attendance.edit', $attendance->id) }}">詳細</a>
                    @else
                    <span>詳細</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection