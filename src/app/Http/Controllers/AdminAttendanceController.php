<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Attendance;
use App\Services\AttendanceService;

class AdminAttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService
    ) {
    }

    /**
     * 日時勤怠一覧画面の表示
     *
     * @return View
     */
    public function index(Request $request): View {
        $data = $this->attendanceService->getDailyAttendanceData(
            $request->input('date')
        );

        return view('admin.attendance_history', $data);
    }

    /**
     * 勤怠詳細画面の表示
     *
     * @param Attendance $attendance
     * @return View
     */
    public function edit(Attendance $attendance): View {
        return view('admin.attendance_detail', $this->attendanceService->getDetailData($attendance));
    }
}
