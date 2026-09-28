@props(['attendance', 'correctRequest'])

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
                <span class="table__date--space">
                    {{ $correctRequest->requested_check_in?->format('H:i') }}
                </span>
                <span>〜</span>
                <span class="table__date--space">
                    {{ $correctRequest->requested_check_out?->format('H:i') }}
                </span>
            </div>
        </td>
    </tr>

    @forelse ($correctRequest->breakCorrectRequests as $index => $break)
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
            {{ $correctRequest->reason }}
        </td>
    </tr>
</table>
<div class="btn-wrapper">
    <p class="form__message">＊承認待ちのため修正はできません＊</p>
</div>
