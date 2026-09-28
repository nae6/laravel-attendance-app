<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Exceptions\AlreadyClockedInException;
use App\Exceptions\AlreadyClockedOutException;
use App\Exceptions\NoActiveBreakException;
use App\Exceptions\NotClockedInException;
use App\Models\Attendance;
use App\Models\BreakRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceActionService
{
    /**
     * 勤怠打刻画面の表示に必要な情報を取得
     *
     * @param int $userId
     * @return array{status: AttendanceStatus, now_date: string, now_time: string}
     */
    public function getAttendanceActionData(int $userId): array
    {
        $attendance = $this->getTodayAttendance($userId);

        $status = $attendance ? $attendance->status : AttendanceStatus::OffDuty;

        $now = Carbon::now();
        $now_date = $now->isoFormat('YYYY年MM月DD日(ddd)');

        if ($attendance && $attendance->status === AttendanceStatus::Finished) {
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
     * @return void
     * @throws AlreadyClockedInException 当日すでに出勤済みの場合
     */
    public function startWork(int $userId): void
    {
        $exists = Attendance::where('user_id', $userId)
            ->whereDate('check_in', today())
            ->exists();

        if ($exists) {
            throw new AlreadyClockedInException();
        }

        Attendance::create([
            'user_id' => $userId,
            'check_in' => now(),
            'check_out' => null,
        ]);
    }

    /**
     * 休憩入り打刻
     *
     * @param int $userId
     * @return void
     * @throws NotClockedInException 当日の出勤記録がない場合
     * @throws AlreadyClockedOutException 当日すでに退勤済みの場合
     */
    public function startBreak(int $userId): void
    {
        $attendance = $this->getWorkingAttendance($userId);

        DB::transaction(function () use ($attendance) {
            BreakRecord::create([
                'attendance_id' => $attendance->id,
                'break_start' => now(),
                'break_end' => null,
            ]);

            $attendance->update([
                'status' => AttendanceStatus::OnBreak,
            ]);
        });
    }

    /**
     * 休憩戻り打刻
     *
     * @param int $userId
     * @return void
     * @throws NotClockedInException 当日の出勤記録がない場合
     * @throws AlreadyClockedOutException 当日すでに退勤済みの場合
     * @throws NoActiveBreakException 終了できる休憩がない場合
     */
    public function endBreak(int $userId): void
    {
        $attendance = $this->getWorkingAttendance($userId);

        $break = BreakRecord::where('attendance_id', $attendance->id)
            ->whereNull('break_end')
            ->latest('break_start')
            ->first();

        if (!$break) {
            throw new NoActiveBreakException();
        }

        DB::transaction(function () use ($attendance, $break) {
            $break->update([
                'break_end' => now(),
            ]);

            $attendance->update([
                'status' => AttendanceStatus::Working,
            ]);
        });
    }

    /**
     * 退勤打刻
     *
     * @param int $userId
     * @return void
     * @throws NotClockedInException 当日の出勤記録がない場合
     * @throws AlreadyClockedOutException 当日すでに退勤済みの場合
     */
    public function endWork(int $userId): void
    {
        $attendance = $this->getWorkingAttendance($userId);

        $attendance->update([
            'check_out' => now(),
            'status' => AttendanceStatus::Finished,
        ]);
    }

    /**
     * 当日の勤務中(出勤済みかつ未退勤)の勤怠を取得
     *
     * @param int $userId
     * @return Attendance
     * @throws NotClockedInException 当日の出勤記録がない場合
     * @throws AlreadyClockedOutException 当日すでに退勤済みの場合
     */
    private function getWorkingAttendance(int $userId): Attendance
    {
        $attendance = $this->getTodayAttendance($userId);

        if (!$attendance) {
            throw new NotClockedInException();
        }

        if ($attendance->check_out) {
            throw new AlreadyClockedOutException();
        }

        return $attendance;
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
