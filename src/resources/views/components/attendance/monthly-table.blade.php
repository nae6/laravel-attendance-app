@props(['dates', 'attendances', 'detailRoute'])

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
                    <a href="{{ route($detailRoute, $attendance->id) }}">詳細</a>
                    @else
                    <span>詳細</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
