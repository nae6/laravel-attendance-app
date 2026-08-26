<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\BreakRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceActionService
{
    /**
     * 勤怠打刻画面の表示に必要な情報を取得
     *
     * @param int $userId
     * @return array{status: string, now_date: string, now_time: string}
     */
    public function getAttendanceActionData(int $userId): array
    {
        $attendance = $this->getTodayAttendance($userId);

        $status = $attendance ? $attendance->status : '勤務外';

        $now = Carbon::now();
        $now_date = $now->isoFormat('YYYY年MM月DD日(ddd)');

        if ($attendance && $attendance->status === '退勤済') {
            $now_time = $attendance->check_out->format('H:i');
        } else {
            $now_time = now()->format('H:i');
        }

        return compact('status', 'now_date', 'now_time');
    }

    /**
     * 出勤打刻
     *
     * @param int $userId
     * @return array{message: string}
     */
    public function startWork(int $userId): array
    {
        $exists = Attendance::where('user_id', $userId)
            ->whereDate('check_in', today())
            ->exists();

        if ($exists) {
            return ['message' => '本日の出勤は打刻済みです'];
        }

        Attendance::create([
            'user_id' => $userId,
            'check_in' => now(),
            'check_out' => null,
        ]);

        return ['message' => '出勤しました'];
    }

    /**
     * 休憩入り打刻
     *
     * @param int $userId
     * @return array{message?: string, error?: string}
     */
    public function startBreak(int $userId): array
    {
        $attendance = $this->getTodayAttendance($userId);

        if (!$attendance) {
            return ['message' => '本日の出勤記録がありません'];
        }

        if ($attendance->check_out) {
            return ['message' => '本日は退勤済みです'];
        }

        try {
            DB::transaction(function () use ($attendance) {
                BreakRecord::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => now(),
                    'break_end' => null,
                ]);

                $attendance->update([
                    'status' => '休憩中',
                ]);
            });

            return [];
        } catch (\Throwable $e) {
            Log::error($e);

            return ['error' => '休憩開始に失敗しました'];
        }
    }

    /**
     * 休憩戻り打刻
     *
     * @param int $userId
     * @return array{message?: string, error?: string}
     */
    public function endBreak(int $userId): array
    {
        $attendance = $this->getTodayAttendance($userId);

        if (!$attendance) {
            return ['message' => '本日の出勤記録がありません'];
        }

        if ($attendance->check_out) {
            return ['message' => '本日は退勤済みです'];
        }

        $break = BreakRecord::where('attendance_id', $attendance->id)
            ->whereNull('break_end')
            ->latest('break_start')
            ->first();

        if (!$break) {
            return ['message' => '終了できる休憩がありません'];
        }

        try {
            DB::transaction(function () use ($attendance, $break) {
                $break->update([
                    'break_end' => now(),
                ]);

                $attendance->update([
                    'status' => '出勤中',
                ]);
            });

            return [];
        } catch (\Throwable $e) {
            Log::error($e);

            return ['error' => '休憩終了に失敗しました'];
        }
    }

    /**
     * 退勤打刻
     *
     * @param int $userId
     * @return array{message: string}
     */
    public function endWork(int $userId): array
    {
        $attendance = $this->getTodayAttendance($userId);

        if (!$attendance) {
            return ['message' => '本日の出勤記録がありません'];
        }

        if ($attendance->check_out) {
            return ['message' => '本日の退勤は打刻済みです'];
        }

        $attendance->update([
            'check_out' => now(),
            'status' => '退勤済',
        ]);

        return ['message' => '退勤しました'];
    }

    /**
     * 当日の勤怠を取得
     *
     * @param int $userId
     * @return Attendance|null
     */
    private function getTodayAttendance(int $userId): ?Attendance
    {
        return Attendance::where('user_id', $userId)
            ->whereDate('check_in', today())
            ->first();
    }
}
