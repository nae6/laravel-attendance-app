<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectRequestFormRequest;
use App\Services\AttendanceCorrectRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use App\Enums\AttendanceCorrectRequestStatus;
use App\Models\AttendanceCorrectRequest;
use App\Models\Attendance;

class AdminAttendanceCorrectController extends Controller
{
    public function __construct(
        private AttendanceCorrectRequestService $attendanceCorrectRequestService
    ) {
    }

    /**
     * 管理者による勤怠修正
     *
     * @return RedirectResponse
     */
    public function update(AttendanceCorrectRequestFormRequest $request, Attendance $attendance): RedirectResponse {
        try {
            $this->attendanceCorrectRequestService->updateByAdmin($attendance, $request->validated());

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
     * 修正申請の承認画面表示
     */
    public function show(AttendanceCorrectRequest $attendanceCorrectRequest): View {
        return view(
            'admin.request_approve',
            $this->attendanceCorrectRequestService->getApprovalData($attendanceCorrectRequest)
        );
    }

    /**
     * 修正申請の承認
     *
     * @return RedirectResponse
     */
    public function approve(AttendanceCorrectRequest $attendanceCorrectRequest): RedirectResponse {
        if ($attendanceCorrectRequest->approval_status === AttendanceCorrectRequestStatus::Approved) {
            return back()->withErrors([
                'system_error' => 'この申請はすでに承認済みです',
            ]);
        }

        try {
            $this->attendanceCorrectRequestService->approve($attendanceCorrectRequest);

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

