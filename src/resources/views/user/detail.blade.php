@extends('common.app')

@section('title', '勤怠詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<h1 class="content-title">勤怠詳細</h1>

@error('system_error')
<p class="form__error">{{ $message }}</p>
@enderror

@if($correctRequest)
<x-attendance.pending-detail :attendance="$attendance" :correct-request="$correctRequest" />
@elseif($attendance)
<x-attendance.correction-form
    :attendance="$attendance"
    :break-count="$breakCount"
    :action="route('attendance.update', $attendance->id)" />
@endif
@endsection
