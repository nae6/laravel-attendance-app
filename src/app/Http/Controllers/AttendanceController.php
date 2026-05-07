<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectRequestFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;
use App\Models\BreakCorrectRequest;
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

        $attendance->load(['user', 'breakRecords', 'latestCorrectRequest.breakCorrectRequests',]);

        $correctRequest = $attendance->latestCorrectRequest;

        $displayBreaks = $correctRequest
            ? $correctRequest->breakCorrectRequests
            : $attendance->breakRecords;$attendance->breakRecords;

        $breakCount = $displayBreaks?->count() ?? 0;

        return view('user.detail', compact('attendance', 'breakCount', 'correctRequest', 'displayBreaks'));
    }

    /**
     * 勤怠詳細画面の表示(勤怠の入力が無い日)
     *
     * 未完成！！！
     *
     */
    public function create(string $date) {
        $attendance = null;
        $breakCount = $attendance?->breakRecords?->count() ?? 0;

        return view('user.detail', compact('date', 'attendance', 'breakCount'));
    }

    /**
     * 勤怠の修正
     *
     * @return RedirectResponse
     */
    public function update(AttendanceCorrectRequestFormRequest $request, Attendance $attendance): RedirectResponse {
        abort_if($attendance->user_id !== Auth::id(), 403);

        $validated = $request->validated();

        DB::transaction(function() use ($attendance, $validated) {
            $correctRequest = AttendanceCorrectRequest::create([
                'attendance_id' => $attendance->id,
                'requested_check_in' => $validated['check_in'],
                'requested_check_out' => $validated['check_out'],
                'reason' => $validated['reason'],
            ]);

            foreach ($validated['breaks'] ?? [] as $break) {
                if (empty($break['break_start']) && empty($break['break_end'])) {
                    continue;
                }

                BreakCorrectRequest::create([
                    'attendance_correct_request_id' => $correctRequest->id,
                    'requested_break_start' => $break['break_start'],
                    'requested_break_end' => $break['break_end'],
                ]);
            };
        });

        return redirect()->route('attendance.edit', $attendance);
    }
}
