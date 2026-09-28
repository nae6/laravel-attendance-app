@extends('common.app')

@section('title', 'スタッフ別勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/history.css') }}">
@endsection

@section('content')
<h1 class="content-title">{{ $staff->name }}さんの勤怠</h1>

<x-attendance.month-nav
    :current-month="$currentMonth"
    :prev-url="route('staff.attendance.list', ['staff' => $staff, 'month' => $lastMonth])"
    :next-url="route('staff.attendance.list', ['staff' => $staff, 'month' => $nextMonth])" />

<x-attendance.monthly-table
    :dates="$dates"
    :attendances="$attendances"
    detail-route="admin.attendance.edit" />

<div class="btn-wrapper">
    <a href="{{ route('staff.attendance.export', ['staff' => $staff->id, 'month' => $currentMonth->format('Y-m')]) }}" class="btn">CSV出力</a>
</div>
@endsection
