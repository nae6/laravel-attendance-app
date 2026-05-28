<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectRequestFormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Models\AttendanceCorrectRequest;
use App\Models\BreakCorrectRequest;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceCorrectController extends Controller
{
    /**
     * 管理者による勤怠修正
     *
     * @return RedirectResponse
     */
    public function update(AttendanceCorrectRequestFormRequest $request, Attendance $attendance): RedirectResponse {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($attendance, $validated) {
                $correctRequest = $this->storeCorrectRequest($attendance, $validated);

                $this->storeBreakCorrectRequests($correctRequest, $validated);

                $this->updateAttendance($attendance, $validated);

                $this->replaceBreakRecords($attendance, $validated);
            });

            return redirect()
                ->route('admin.attendance.index', $attendance)
                ->with('message', '勤怠を修正しました');

        } catch (\Throwable $e) {
            Log::error('管理者による勤怠修正に失敗', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'system_error' => '勤怠の修正に失敗しました',
            ]);
        }
    }

    /**
     * 修正履歴を保存
     *
     * @return AttendanceCorrectRequest
     */
    private function storeCorrectRequest(Attendance $attendance, array $validated): AttendanceCorrectRequest {
        return AttendanceCorrectRequest::create([
            'attendance_id' => $attendance->id,
            'requested_check_in' => $this->toDateTime($validated['date'], $validated['check_in']),
            'requested_check_out' => $this->toDateTime($validated['date'], $validated['check_out']),
            'reason' => $validated['reason'],
            'approval_status' => AttendanceCorrectRequest::STATUS_APPROVED,
        ]);
    }

    /**
     * 修正後の休憩履歴を保存
     */
    private function storeBreakCorrectRequests(AttendanceCorrectRequest $correctRequest, array $validated): void {
        foreach ($validated['breaks'] ?? [] as $break) {
            if ($this->isEmptyBreak($break)) {
                continue;
            }

            BreakCorrectRequest::create([
                'attendance_correct_request_id' => $correctRequest->id,
                'requested_break_start' => $break['break_start'],
                'requested_break_end' => $break['break_end'],
            ]);
        }
    }

    /**
     * 勤怠本体を更新
     */
    private function updateAttendance(Attendance $attendance, array $validated): void {
        $attendance->update([
            'check_in' => $this->toDateTime($validated['date'], $validated['check_in']),
            'check_out' => $this->toDateTime($validated['date'], $validated['check_out']),
            'status' => '退勤済',
        ]);
    }

    /**
     * 休憩を修正後の内容に置き換える
     */
    private function replaceBreakRecords(Attendance $attendance, array $validated): void {
        $attendance->breakRecords()->delete();

        foreach ($validated['breaks'] ?? [] as $break) {
            if ($this->isEmptyBreak($break)) {
                continue;
            }

            $attendance->breakRecords()->create([
                'break_start' => $break['break_start'],
                'break_end' => $break['break_end'],
            ]);
        }
    }

    /**
     * 空の休憩行か判定
     */
    private function isEmptyBreak(array $break): bool {
        return empty($break['break_start']) && empty($break['break_end']);
    }

    /**
     * 勤務時間の入力に日付を付加
     */
    private function toDateTime(string $date, string $time): Carbon {
        return Carbon::parse("$date $time");
    }

    /**
     * 修正申請の承認画面表示
     */
    public function show(AttendanceCorrectRequest $attendanceCorrectRequest): View {
        $attendanceCorrectRequest->load(['attendance.user', 'breakCorrectRequests']);

        $attendance = $attendanceCorrectRequest->attendance;
        $correctRequest = $attendanceCorrectRequest;

        $displayBreaks = $correctRequest->breakCorrectRequests;

        $breakCount = $displayBreaks->count();

        return view('admin.request_approve', compact(
            'attendance',
            'breakCount',
            'correctRequest',
            'displayBreaks'
        ));
    }

    /**
     * 修正申請の承認
     *
     * @return RedirectResponse
     */
    public function approve(AttendanceCorrectRequest $attendanceCorrectRequest): RedirectResponse {
        if ($attendanceCorrectRequest->approval_status === AttendanceCorrectRequest::STATUS_APPROVED) {
            return back()->withErrors([
                'system_error' => 'この申請はすでに承認済みです',
            ]);
        }

        try {
            $attendanceCorrectRequest->load(['attendance', 'breakCorrectRequests']);

            DB::transaction(function () use ($attendanceCorrectRequest) {

                $attendanceCorrectRequest->update([
                    'approval_status' => AttendanceCorrectRequest::STATUS_APPROVED,
                ]);

                // attendancesテーブルの更新
                $attendance = $attendanceCorrectRequest->attendance;

                $attendance->update([
                    'check_in' => $attendanceCorrectRequest->requested_check_in,
                    'check_out' => $attendanceCorrectRequest->requested_check_out,
                ]);

                // break_recordsテーブルの更新
                $attendance->breakRecords()->delete();

                foreach ($attendanceCorrectRequest->breakCorrectRequests as $break) {
                    if (empty($break->requested_break_start) && empty($break->requested_break_end)) {
                        continue;
                    }

                    $attendance->breakRecords()->create([
                        'break_start' => $break->requested_break_start,
                        'break_end' => $break->requested_break_end,
                    ]);
                }
            });

            return redirect()->route('request.list')->with('success', '申請を承認しました');

        } catch (\Exception $e) {
            Log::error('管理者による修正承認に失敗', [
                'attendance_correct_request_id' => $attendanceCorrectRequest->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'system_error' => '承認処理に失敗しました',
            ]);
        }
    }
}

