<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceService
{
    /**
     * 管理者の日次勤怠一覧画面の表示に必要な情報を取得する。
     *
     * @return array{attendances: \Illuminate\Support\Collection, currentDate: Carbon, lastDate: string, nextDate: string}
     */
    public function getDailyAttendanceData(?string $date): array
    {
        $currentDate = $date ? Carbon::parse($date) : today();

        $lastDate = $currentDate->copy()->subDay()->format('Y-m-d');
        $nextDate = $currentDate->copy()->addDay()->format('Y-m-d');

        $attendances = Attendance::with(['user', 'breakRecords'])
            ->whereDate('check_in', $currentDate)
            ->get();

        return compact('attendances', 'currentDate', 'lastDate', 'nextDate');
    }

    /**
     * 月別勤怠一覧画面の表示に必要な情報を取得する。
     *
     * @return array{attendances: \Illuminate\Support\Collection, currentMonth: Carbon, dates: CarbonPeriod, lastMonth: string, nextMonth: string}
     */
    public function getMonthlyAttendanceData(int $userId, ?string $month): array
    {
        $currentMonth = Carbon::parse($month ?? today()->format('Y-m'));

        $lastMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        $dates = CarbonPeriod::create($startOfMonth, $endOfMonth);

        $attendances = Attendance::with('breakRecords')
            ->where('user_id', $userId)
            ->whereBetween('check_in', [$startOfMonth, $endOfMonth->endOfDay()])
            ->get()
            ->keyBy(fn (Attendance $attendance) => $attendance->check_in->format('Y-m-d'));

        return compact('attendances', 'currentMonth', 'dates', 'lastMonth', 'nextMonth');
    }

    /**
     * 勤怠詳細画面の表示に必要な情報を取得する。
     *
     * @return array{attendance: Attendance, breakCount: int, correctRequest: \App\Models\AttendanceCorrectRequest|null, displayBreaks: \Illuminate\Support\Collection}
     */
    public function getDetailData(Attendance $attendance): array
    {
        $attendance->load(['user', 'breakRecords']);

        $correctRequest = $attendance->pendingCorrectRequest();
        $displayBreaks = $correctRequest
            ? $correctRequest->breakCorrectRequests
            : $attendance->breakRecords;
        $breakCount = $displayBreaks->count();

        return compact('attendance', 'breakCount', 'correctRequest', 'displayBreaks');
    }
}
