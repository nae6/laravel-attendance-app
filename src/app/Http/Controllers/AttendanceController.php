<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * 勤怠打刻画面
     *
     * @return View
     */
    public function index(): View {
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
     * 出勤打刻機能
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
     * 退勤打刻機能
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

        Attendance::find($attendance->id)
            ->update([
                'check_out' => now(),
                'status' => '退勤済'
            ]);

        return redirect()->route('attendance');
    }
}
