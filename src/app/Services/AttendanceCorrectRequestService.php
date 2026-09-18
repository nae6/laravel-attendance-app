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

    /**
     * 管理者による勤怠修正を保存する
     *
     * @param Attendance $attendance
     * @param array $validated
     * @return void
     */
    public function updateByAdmin(Attendance $attendance, array $validated): void
    {
        DB::transaction(function () use ($attendance, $validated) {
            $correctRequest = $this->storeAdminCorrectRequest($attendance, $validated);

            $this->storeAdminBreakCorrectRequests($correctRequest, $validated);

            $attendance->update([
                'check_in' => $this->toDateTime($validated['date'], $validated['check_in']),
                'check_out' => $this->toDateTime($validated['date'], $validated['check_out']),
                'status' => '退勤済',
            ]);

            $this->replaceBreakRecords($attendance, $validated);
        });
    }

    /**
     * 修正履歴を保存
     *
     * @return AttendanceCorrectRequest
     */
    private function storeAdminCorrectRequest(Attendance $attendance, array $validated): AttendanceCorrectRequest
    {
        return AttendanceCorrectRequest::create([
            'attendance_id' => $attendance->id,
            'requested_check_in' => $this->toDateTime($validated['date'], $validated['check_in']),
            'requested_check_out' => $this->toDateTime($validated['date'], $validated['check_out']),
            'reason' => $validated['reason'],
            'approval_status' => AttendanceCorrectRequestStatus::Approved,
        ]);
    }

    /**
     * 修正後の休憩履歴を保存
     */
    private function storeAdminBreakCorrectRequests(AttendanceCorrectRequest $correctRequest, array $validated): void
    {
        foreach ($validated['breaks'] ?? [] as $break) {
            if ($this->isEmptyBreak($break)) {
                continue;
            }

            BreakCorrectRequest::create([
                'attendance_correct_request_id' => $correctRequest->id,
                'requested_break_start' => $this->toDateTime($validated['date'], $break['break_start']),
                'requested_break_end' => $this->toDateTime($validated['date'], $break['break_end']),
            ]);
        }
    }

    /**
     * 休憩を修正後の内容に置き換える
     */
    private function replaceBreakRecords(Attendance $attendance, array $validated): void
    {
        $attendance->breakRecords()->delete();

        foreach ($validated['breaks'] ?? [] as $break) {
            if ($this->isEmptyBreak($break)) {
                continue;
            }

            $attendance->breakRecords()->create([
                'break_start' => $this->toDateTime($validated['date'], $break['break_start']),
                'break_end' => $this->toDateTime($validated['date'], $break['break_end']),
            ]);
        }
    }

    /**
     * 勤務時間の入力に日付を付加
     */
    private function toDateTime(string $date, string $time): Carbon
    {
        return Carbon::parse("$date $time");
    }

    /**
     * 修正申請の承認画面表示に必要な情報を取得する
     *
     * @param AttendanceCorrectRequest $attendanceCorrectRequest
     * @return array{attendance: Attendance, breakCount: int, correctRequest: AttendanceCorrectRequest, displayBreaks: \Illuminate\Support\Collection}
     */
    public function getApprovalData(AttendanceCorrectRequest $attendanceCorrectRequest): array
    {
        $attendanceCorrectRequest->load(['attendance.user', 'breakCorrectRequests']);

        $attendance = $attendanceCorrectRequest->attendance;
        $correctRequest = $attendanceCorrectRequest;
        $displayBreaks = $correctRequest->breakCorrectRequests;
        $breakCount = $displayBreaks->count();

        return compact('attendance', 'breakCount', 'correctRequest', 'displayBreaks');
    }

    /**
     * 修正申請を承認する
     *
     * @param AttendanceCorrectRequest $attendanceCorrectRequest
     * @return void
     */
    public function approve(AttendanceCorrectRequest $attendanceCorrectRequest): void
    {
        $attendanceCorrectRequest->load(['attendance', 'breakCorrectRequests']);

        DB::transaction(function () use ($attendanceCorrectRequest) {
            $attendanceCorrectRequest->update([
                'approval_status' => AttendanceCorrectRequestStatus::Approved,
            ]);

            $attendance = $attendanceCorrectRequest->attendance;
            $attendance->update([
                'check_in' => $attendanceCorrectRequest['requested_check_in'],
                'check_out' => $attendanceCorrectRequest['requested_check_out'],
            ]);

            $attendance->breakRecords()->delete();

            foreach ($attendanceCorrectRequest->breakCorrectRequests as $break) {
                if (empty($break['requested_break_start']) && empty($break['requested_break_end'])) {
                    continue;
                }

                $attendance->breakRecords()->create([
                    'break_start' => $break['requested_break_start'],
                    'break_end' => $break['requested_break_end'],
                ]);
            }
        });
    }
}
