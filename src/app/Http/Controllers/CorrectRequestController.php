<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectRequestFormRequest;
use App\Services\AttendanceCorrectRequestService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Attendance;

class CorrectRequestController extends Controller
{
    public function __construct(
        private AttendanceCorrectRequestService $attendanceCorrectRequestService
    ) {
    }

    /**
     * 勤怠の修正申請(user)
     *
     * @return RedirectResponse
     */
    public function update(AttendanceCorrectRequestFormRequest $request, Attendance $attendance): RedirectResponse {
        $this->authorize('view', $attendance);

        try {
            $this->attendanceCorrectRequestService->createUserRequest($attendance, $request->validated());

            return redirect()->route('attendance.edit', $attendance);
        } catch (\Throwable $e) {
            Log::error('勤怠修正申請の保存に失敗', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'system_error' => '修正申請の保存に失敗しました'
            ]);
        }
    }

    /**
     * 申請一覧の表示(user/admin共通)
     *
     * @return View
     */
    public function index(Request $request): View {
        $viewType = $request->attributes->get('view_type');

        $data = $this->attendanceCorrectRequestService->getRequestListData($viewType, Auth::id());

        return view('common.request_history', array_merge($data, compact('viewType')));
    }
}
