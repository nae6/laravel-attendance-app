@props(['attendance', 'breakCount', 'action'])

<form action="{{ $action }}" method="POST" novalidate>
    @method('PUT')
    @csrf
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
                        {{ $attendance?->check_in->format('Y年') }}
                    </span>
                    <span class="table__date--space">
                        {{ $attendance?->check_in->format('n月j日') }}
                    </span>
                </div>
            </td>
        </tr>

        <tr class="table__row">
            <th>出勤・退勤</th>
            <td>
                <div class="time-input datetime">
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
                <div class="time-input datetime">
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
                <div class="time-input datetime">
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
                </div>
            </td>
        </tr>

        <tr class="table__row">
            <th>備考</th>
            <td>
                <textarea name="reason" class="note__content">{{ old('reason') }}</textarea>
                @error('reason')
                <p class="form__error">{{ $message }}</p>
                @enderror
            </td>
        </tr>
    </table>
    <div class="btn-wrapper">
        <button type="submit" class="form__btn">修正</button>
    </div>
</form>
