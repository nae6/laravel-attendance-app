<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Attendance;
use Carbon\CarbonPeriod;
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

        return view('admin.admin_history', compact('attendances', 'currentDate', 'lastDate', 'nextDate'));
    }

    /**
     * 勤怠詳細画面の表示
     *
     * @param Attendance $attendance
     * @return View
     */
    public function edit(Attendance $attendance): View
    {
        abort_if($attendance->user_id !== Auth::id(), 403);

        $attendance->load(['user', 'breakRecords', 'latestCorrectRequest.breakCorrectRequests',]);

        $correctRequest = $attendance->latestCorrectRequest;

        $displayBreaks = $correctRequest
            ? $correctRequest->breakCorrectRequests
            : $attendance->breakRecords;
        $attendance->breakRecords;

        $breakCount = $displayBreaks?->count() ?? 0;

        return view('user.detail', compact('attendance', 'breakCount', 'correctRequest', 'displayBreaks'));
    }
}
