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
<table class="table__wrapper font-setting">
    <tr class="table__row">
        <th>名前</th>
        <td>{{ $attendance->user->name }}</td>
    </tr>

    <tr class="table__row">
        <th>日付</th>
        <td>
            <span>{{ $attendance?->check_in->format('Y年') }}</span>
            <span>{{ $attendance?->check_in->format('n月j日') }}</span>
        </td>
    </tr>

    <tr class="table__row">
        <th>出勤・退勤</th>
        <td>
            <div class="time-input">
                <span>{{ $correctRequest->requested_check_in?->format('H:i') }}</span>
                <span>〜</span>
                <span>{{ $correctRequest->requested_check_out?->format('H:i') }}</span>
            </div>
        </td>
    </tr>

    @foreach ($correctRequest->breakCorrectRequests as $index => $break)
    <tr class="table__row">
        <th>休憩{{ $index === 0 ? '' : $index + 1 }}</th>
        <td>
            <div class="time-input">
                <span>{{ $break->requested_break_start?->format('H:i') }}</span>
                <span>〜</span>
                <span>{{ $break->requested_break_end?->format('H:i') }}</span>
            </div>
        </td>
    </tr>
    @endforeach

    <tr class="table__row">
        <th>備考</th>
        <td class="note">
            {{ $correctRequest->reason }}
        </td>
    </tr>
</table>
<div class="btn-wrapper">
    <p class="form__message">＊承認待ちのため修正はできません＊</p>
</div>

@elseif($attendance)
<form action="{{ route('attendance.update', $attendance->id) }}" method="POST">
    @method('PUT')
    @csrf
    <table class="table__wrapper font-setting">
        <tr class="table__row">
            <th>名前</th>
            <td>{{ $attendance->user->name }}</td>
        </tr>
        <tr class="table__row">
            <th>日付</th>
            <td>
                <span>
                    {{ $attendance?->check_in->format('Y年') }}
                </span>
                <span>
                    {{ $attendance?->check_in->format('n月j日') }}
                </span>
            </td>
        </tr>
        <tr class="table__row">
            <th>出勤・退勤</th>
            <td>
                <div class="time-input">
                    <input type="time" name="check_in" value="{{ old('check_in', $attendance->check_in?->format('H:i')) }}">
                    <span>〜</span>
                    <input type="time" name="check_out" value="{{ old('check_out', $attendance->check_out?->format('H:i')) }}">
                </div>
                <div class="error">
                    @if ($errors->has('check_in') || $errors->has('check_out'))
                    <p class="form__error">
                        {{ $errors->first('check_in') ?: $errors->first('check_out') }}
                    </p>
                    @endif
                </div>
            </td>
        </tr>
        @foreach ($attendance?->breakRecords as $index => $break)
        <tr class="table__row">
            <th>休憩{{ $index === 0 ? '' : $index + 1 }}</th>
            <td>
                <div class="time-input">
                    <input type="time"
                        name="breaks[{{ $index }}][break_start]"
                        value="{{ old("breaks.$index.break_start", $break->break_start?->format('H:i')) }}">
                    <span>〜</span>
                    <input type="time"
                        name="breaks[{{ $index }}][break_end]"
                        value="{{ old("breaks.$index.break_end", $break->break_end?->format('H:i')) }}">
                </div>
                <div class="error">
                    @if ($errors->has("breaks.$index.break_start") || $errors->has("breaks"))
                    <p class="form__error">
                        {{ $errors->first("breaks.$index.break_start") ?: $errors->first("breaks") }}
                    </p>
                    @endif
                    @error("breaks.$index.break_end")
                    <p class="form__error">{{ $message }}</p>
                    @enderror
                </div>
            </td>
        </tr>
        @endforeach
        <tr class="table__row">
            <th>休憩{{ $breakCount + 1 }}</th>
            <td>
                <div class="time-input">
                    <input type="time" name="breaks[{{ $breakCount }}][break_start]" value="{{ old("breaks.$breakCount.break_start") }}">
                    <span>〜</span>
                    <input type="time" name="breaks[{{ $breakCount }}][break_end]" value="{{ old("breaks.$breakCount.break_end") }}">
                </div>
                <div class="error">
                    @error("breaks.$breakCount.break_start")
                    <p class="form__error">{{ $message }}</p>
                    @enderror

                    @error("breaks.$breakCount.break_end")
                    <p class="form__error">{{ $message }}</p>
                    @enderror
            </td>
        </tr>
        <tr class="table__row">
            <th>備考</th>
            <td>
                <textarea name="reason" class="note__content">
                {{ old('reason', $correctRequest?->reason ?? '') }}
                </textarea>
                @error('reason')
                <p class="form__error">{{ $message }}</p>
                @enderror
            </td>
        </tr>
    </table>
    <div class="btn-wrapper">
        <button type="submit" class="btn">修正</button>
    </div>
</form>
@endif
@endsection