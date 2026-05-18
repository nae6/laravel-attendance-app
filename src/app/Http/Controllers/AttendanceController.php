<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Attendance;
use Carbon\CarbonPeriod;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * ログインユーザーの勤怠一覧画面表示
     *
     * @return View
     */
    public function index(Request $request): View {
        $currentMonth = Carbon::parse($request->input('month', today()->format('Y-m')));

        $lastMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        // １ヶ月分の日付取得
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        $dates = CarbonPeriod::create($startOfMonth, $endOfMonth);

        // １ヶ月分の勤怠データ取得
        $attendances = Attendance::with('breakRecords')
            ->where('user_id', Auth::id())
            ->whereBetween('check_in', [$startOfMonth, $endOfMonth->endOfDay()])
            ->get()
            ->keyBy(fn($attendance) => $attendance->check_in->format('Y-m-d'));

        return view('user.history', compact('attendances', 'currentMonth','dates', 'lastMonth', 'nextMonth'));
    }

    /**
     * 勤怠詳細画面の表示
     *
     * @param Attendance $attendance
     * @return View
     */
    public function edit(Attendance $attendance): View {
        abort_if($attendance->user_id !== Auth::id(), 403);

        $attendance->load(['user', 'breakRecords']);

        // 未承認の修正申請を取得
        $correctRequest = $attendance->pendingCorrectRequest();

        // 表示用の休憩データ
        $displayBreaks = $correctRequest
            ? $correctRequest->breakCorrectRequests
            : $attendance->breakRecords;

        $breakCount = $displayBreaks->count();

        return view('user.detail', compact(
            'attendance',
            'breakCount',
            'correctRequest',
            'displayBreaks'
        ));
    }
}
