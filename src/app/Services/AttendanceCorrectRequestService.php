<?php

namespace App\Services;

use App\Enums\AttendanceCorrectRequestStatus;
use App\Enums\AttendanceStatus;
use App\Exceptions\AlreadyApprovedException;
use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;
use App\Models\BreakCorrectRequest;
use App\Models\User;
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
            $date = $attendance->check_in->toDateString();

            $correctRequest = AttendanceCorrectRequest::create([
                'attendance_id' => $attendance->id,
                'requested_check_in' => $this->toDateTime($date, $validated['check_in']),
                'requested_check_out' => $this->toDateTime($date, $validated['check_out']),
                'reason' => $validated['reason'],
            ]);

            foreach ($validated['breaks'] ?? [] as $break) {
                if ($this->isEmptyBreak($break)) {
                    continue;
                }

                BreakCorrectRequest::create([
                    'attendance_correct_request_id' => $correctRequest->id,
                    'requested_break_start' => $this->toDateTime($date, $break['break_start']),
                    'requested_break_end' => $this->toDateTime($date, $break['break_end']),
                ]);
            }
        });
    }

    /**
     * 申請一覧画面の表示に必要な情報を取得する(user/admin共通)
     *
     * 管理者は全ユーザー、一般ユーザーは自分の申請のみを対象とする
     *
     * @param User $user
     * @return array{pendingRequests: \Illuminate\Support\Collection, approvedRequests: \Illuminate\Support\Collection}
     */
    public function getRequestListData(User $user): array
    {
        $baseQuery = AttendanceCorrectRequest::with('attendance.user')
            ->latest('created_at');

        if (!$user->isAdmin()) {
            $baseQuery->forUser($user->id);
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
    private function isEmptyBreak(array $break): bool
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
        // 改ざん可能なリクエスト値ではなく、対象勤怠の日付を使う
        $date = $attendance->check_in->toDateString();

        DB::transaction(function () use ($attendance, $validated, $date) {
            $correctRequest = $this->storeAdminCorrectRequest($attendance, $validated, $date);

            $this->storeAdminBreakCorrectRequests($correctRequest, $validated, $date);

            $attendance->update([
                'check_in' => $this->toDateTime($date, $validated['check_in']),
                'check_out' => $this->toDateTime($date, $validated['check_out']),
                'status' => AttendanceStatus::Finished,
            ]);

            $this->replaceBreakRecords($attendance, $validated, $date);
        });
    }

    /**
     * 修正履歴を保存
     *
     * @return AttendanceCorrectRequest
     */
    private function storeAdminCorrectRequest(Attendance $attendance, array $validated, string $date): AttendanceCorrectRequest
    {
        return AttendanceCorrectRequest::create([
            'attendance_id' => $attendance->id,
            'requested_check_in' => $this->toDateTime($date, $validated['check_in']),
            'requested_check_out' => $this->toDateTime($date, $validated['check_out']),
            'reason' => $validated['reason'],
            'approval_status' => AttendanceCorrectRequestStatus::Approved,
        ]);
    }

    /**
     * 修正後の休憩履歴を保存
     */
    private function storeAdminBreakCorrectRequests(AttendanceCorrectRequest $correctRequest, array $validated, string $date): void
    {
        foreach ($validated['breaks'] ?? [] as $break) {
            if ($this->isEmptyBreak($break)) {
                continue;
            }

            BreakCorrectRequest::create([
                'attendance_correct_request_id' => $correctRequest->id,
                'requested_break_start' => $this->toDateTime($date, $break['break_start']),
                'requested_break_end' => $this->toDateTime($date, $break['break_end']),
            ]);
        }
    }

    /**
     * 休憩を修正後の内容に置き換える
     */
    private function replaceBreakRecords(Attendance $attendance, array $validated, string $date): void
    {
        $attendance->breakRecords()->delete();

        foreach ($validated['breaks'] ?? [] as $break) {
            if ($this->isEmptyBreak($break)) {
                continue;
            }

            $attendance->breakRecords()->create([
                'break_start' => $this->toDateTime($date, $break['break_start']),
                'break_end' => $this->toDateTime($date, $break['break_end']),
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
     * @throws AlreadyApprovedException 承認済みの申請の場合
     */
    public function approve(AttendanceCorrectRequest $attendanceCorrectRequest): void
    {
        if ($attendanceCorrectRequest->approval_status === AttendanceCorrectRequestStatus::Approved) {
            throw new AlreadyApprovedException();
        }

        $attendanceCorrectRequest->load(['attendance', 'breakCorrectRequests']);

        DB::transaction(function () use ($attendanceCorrectRequest) {
            $attendanceCorrectRequest->update([
                'approval_status' => AttendanceCorrectRequestStatus::Approved,
            ]);

            $attendance = $attendanceCorrectRequest->attendance;
            $attendance->update([
                'check_in' => $attendanceCorrectRequest->requested_check_in,
                'check_out' => $attendanceCorrectRequest->requested_check_out,
            ]);

            $attendance->breakRecords()->delete();

            // 休憩の修正申請は空行を除いて保存しているため、すべて反映する
            foreach ($attendanceCorrectRequest->breakCorrectRequests as $break) {
                $attendance->breakRecords()->create([
                    'break_start' => $break->requested_break_start,
                    'break_end' => $break->requested_break_end,
                ]);
            }
        });
    }
}
