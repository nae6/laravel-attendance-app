<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Attendance;
use App\Services\AttendanceService;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService
    ) {
    }

    /**
     * ログインユーザーの勤怠一覧画面表示
     *
     * @return View
     */
    public function index(Request $request): View {
        $data = $this->attendanceService->getMonthlyAttendanceData(
            Auth::id(),
            $request->input('month')
        );

        return view('user.history', $data);
    }

    /**
     * 勤怠詳細画面の表示
     *
     * @param Attendance $attendance
     * @return View
     */
    public function edit(Attendance $attendance): View {
        abort_if($attendance->user_id !== Auth::id(), 403);

        return view('user.detail', $this->attendanceService->getDetailData($attendance));
    }
}
