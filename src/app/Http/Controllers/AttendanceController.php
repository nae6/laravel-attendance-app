<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\BreakRecord;
use App\Models\Attendance;
use Carbon\CarbonPeriod;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * 勤怠打刻画面表示
     *
     * @return View
     */
    public function edit(): View {
        $userId = Auth::id();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('check_in', today())
            ->first();

        $status = $attendance ? $attendance->status : '勤務外';

        $now = Carbon::now();
        $now_date = $now->isoFormat('YYYY年MM月DD日(ddd)');
        $now_time = $now->format('H:i');

        return view('user.index', compact('status', 'now_date', 'now_time'));
    }

    /**
     * 出勤打刻
     *
     * @return RedirectResponse
     */
    public function startWork(): RedirectResponse {
        $userId = Auth::id();

        $exists = Attendance::where('user_id', $userId)
            ->whereDate('check_in', today())
            ->exists();

        if ($exists) {
            return redirect()->route('attendance')
                ->with('message', '本日の出勤は打刻済みです');
        }

        Attendance::create([
            'user_id' => $userId,
            'check_in' => now(),
            'check_out' => null,
        ]);

        return redirect()->route('attendance')->with('message', '出勤しました');
    }

    /**
     * 休憩入り打刻
     *
     * @return RedirectResponse
     */
    public function startBreak(): RedirectResponse {
        $userId = Auth::id();

        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('check_in', today())
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance')
                ->with('message', '本日の出勤記録がありません');
        }

        if ($attendance->check_out) {
            return redirect()->route('attendance')
                ->with('message', '本日は退勤済みです');
        }

        try {
            DB::transaction(function () use ($attendance) {
                BreakRecord::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => now(),
                    'break_end' => null,
                ]);

                $attendance->update([
                        'status' => '休憩中'
                    ]);
            });

            return redirect()->route('attendance');
        } catch (\Throwable $e) {
            Log::error($e);

            return redirect()->route('attendance')
                ->with('error', '休憩開始に失敗しました');
        }
    }

    /**
     * 休憩戻り打刻
     *
     * @return RedirectResponse
     */
    public function endBreak(): RedirectResponse {
        $userId = Auth::id();

        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('check_in', today())
            ->first();

        if (!$attendance) {
            return redirect()->route('attendance')
                ->with('message', '本日の出勤記録がありません');
        }

        if ($attendance->check_out) {
            return redirect()->route('attendance')
                ->with('message', '本日は退勤済みです');
        }

        $break = BreakRecord::where('attendance_id', $attendance->id)
            ->whereNull('break_end')
            ->latest('break_start')
            ->first();

        if (!$break) {
            return redirect()->route('attendance')
                ->with('message', '終了できる休憩がありません');
        }

        try {
            DB::transaction(function () use ($attendance, $break) {
                $break->update([
                    'break_end' => now(),
                ]);

                $attendance->update([
                    'status' => '出勤中'
                ]);
            });

            return redirect()->route('attendance');
        } catch (\Throwable $e) {
            Log::error($e);

            return redirect()->route('attendance')
                ->with('error', '休憩終了に失敗しました');
        }
    }

    /**
     * 退勤打刻
     *
     * @return RedirectResponse
     */
    public function endWork(Attendance $attendance): RedirectResponse {
        $userId = Auth::id();

        $attendance = Attendance::where('user_id', $userId)
            ->whereDate('check_in', today())
            ->first();

        if ($attendance->check_out) {
            return redirect()->route('attendance')
                ->with('message', '本日の退勤は打刻済みです');
        }

        $attendance->update([
                'check_out' => now(),
                'status' => '退勤済'
            ]);

        return redirect()->route('attendance')->with('message', '退勤しました');
    }

    /**
     * ログインユーザーの勤怠一覧画面表示
     *
     * @return View
     */
    public function index(Request $request): View {
        // １ヶ月分の日付取得
        $currentMonth = Carbon::parse($request->input('month', today()->format('Y-m')));

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $dates = CarbonPeriod::create($startOfMonth, $endOfMonth);

        // １ヶ月分の勤怠データ取得
        $attendances = Attendance::with('breakRecords')
            ->where('user_id', Auth::id())
            ->whereBetween('check_in', [$startOfMonth, $endOfMonth->endOfDay()])
            ->get()
            ->keyBy(fn($attendance) => $attendance->check_in->format('Y-m-d'));

        return view('user.history', compact('attendances', 'currentMonth','dates'));
    }
}
