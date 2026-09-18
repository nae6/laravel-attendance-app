<?php

namespace App\Services;

use App\Enums\AttendanceCorrectRequestStatus;
use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;
use App\Models\BreakCorrectRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceCorrectRequestService
{
    /**
     * 勤怠の修正申請を保存する(user)
     *
     * @param Attendance $attendance
     * @param array $validated
     * @return void
     */
    public function createUserRequest(Attendance $attendance, array $validated): void
    {
        DB::transaction(function () use ($attendance, $validated) {
            $attendanceDate = $attendance->check_in->toDateString();

            $correctRequest = AttendanceCorrectRequest::create([
                'attendance_id' => $attendance->id,
                'requested_check_in' => Carbon::parse($attendanceDate . ' ' . $validated['check_in']),
                'requested_check_out' => Carbon::parse($attendanceDate . ' ' . $validated['check_out']),
                'reason' => $validated['reason'],
            ]);

            foreach ($validated['breaks'] ?? [] as $break) {
                if ($this->isEmptyBreak($break)) {
                    continue;
                }

                BreakCorrectRequest::create([
                    'attendance_correct_request_id' => $correctRequest->id,
                    'requested_break_start' => Carbon::parse($attendanceDate . ' ' . $break['break_start']),
                    'requested_break_end' => Carbon::parse($attendanceDate . ' ' . $break['break_end']),
                ]);
            }
        });
    }

    /**
     * 申請一覧画面の表示に必要な情報を取得する(user/admin共通)
     *
     * @param string|null $viewType
     * @param int|null $userId
     * @return array{pendingRequests: \Illuminate\Support\Collection, approvedRequests: \Illuminate\Support\Collection}
     */
    public function getRequestListData(?string $viewType, ?int $userId): array
    {
        $baseQuery = AttendanceCorrectRequest::with('attendance.user')
            ->latest('created_at');

        if ($viewType === 'user') {
            $baseQuery->forUser($userId);
        }

        $pendingRequests = (clone $baseQuery)
            ->where('approval_status', AttendanceCorrectRequestStatus::Pending)
            ->get();

        $approvedRequests = (clone $baseQuery)
            ->where('approval_status', AttendanceCorrectRequestStatus::Approved)
            ->get();

        return compact('pendingRequests', 'approvedRequests');
    }

    /**
     * 空の休憩行か判定する
     *
     * @param array $break
     * @return bool
     */
    public function isEmptyBreak(array $break): bool
    {
        return empty($break['break_start']) && empty($break['break_end']);
    }
}
