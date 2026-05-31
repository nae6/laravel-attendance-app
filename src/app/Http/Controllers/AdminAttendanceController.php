<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    /**
     * 日時勤怠一覧画面の表示
     *
     * @return View
     */
    public function index(Request $request): View {
        $currentDate = $request->filled('date') ? Carbon::parse($request->date) : today();

        $lastDate = $currentDate->copy()->subDay()->format('Y-m-d');
        $nextDate = $currentDate->copy()->addDay()->format('Y-m-d');

        // その日出勤したスタッフ全員の勤怠データ取得
        $attendances = Attendance::with(['user', 'breakRecords'])
            ->whereDate('check_in', $currentDate)
            ->get();

        return view('admin.attendance_history', compact('attendances', 'currentDate', 'lastDate', 'nextDate'));
    }

    /**
     * 勤怠詳細画面の表示
     *
     * @param Attendance $attendance
     * @return View
     */
    public function edit(Attendance $attendance): View {
        $attendance->load(['user', 'breakRecords']);

        // 未承認の修正申請を取得
        $correctRequest = $attendance->pendingCorrectRequest();

        // 表示用の勤怠データ
        $displayAttendance = $correctRequest? $correctRequest: $attendance->format('H:i');

        // 表示用の休憩データ
        $displayBreaks = $correctRequest
            ? $correctRequest->breakCorrectRequests
            : $attendance->breakRecords;

        $breakCount = $displayBreaks->count();

        return view('admin.attendance_detail', compact(
            'attendance',
            'breakCount',
            'displayAttendance',
            'displayBreaks'
        ));
    }
}
