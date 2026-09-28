@extends('common.app')

@section('title', '勤怠一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/history.css') }}">
@endsection

@section('content')
<h1 class="content-title">勤怠一覧</h1>
<x-attendance.month-nav
    :current-month="$currentMonth"
    :prev-url="route('attendance.index', ['month' => $lastMonth])"
    :next-url="route('attendance.index', ['month' => $nextMonth])" />
<x-attendance.monthly-table
    :dates="$dates"
    :attendances="$attendances"
    detail-route="attendance.edit" />
@endsection
