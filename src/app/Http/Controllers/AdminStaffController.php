<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Attendance;
use App\Models\User;
use Carbon\CarbonPeriod;
use Carbon\Carbon;

class AdminStaffController extends Controller
{
    /**
     * スタッフ一覧画面の表示
     *
     * @return View
     */
    public function staffList(): View {
        $users = User::where('role', 'user')
            ->select('id', 'name', 'email')
            ->get();

        return view('admin.staff_list', compact('users'));
    }

    /**
     * スタッフ別月次勤怠一覧画面の表示
     *
     * @return View
     */
    public function attendanceHistory(Request $request, User $staff): View {
        abort_if($staff->role !== 'user', 404);

        $currentMonth = Carbon::parse($request->input('month', today()->format('Y-m')));

        $lastMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        // １ヶ月分の日付取得
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        $dates = CarbonPeriod::create($startOfMonth, $endOfMonth);

        // １ヶ月分の勤怠データ取得
        $attendances = Attendance::with('breakRecords', 'user')
            ->where('user_id', $staff->id)
            ->whereBetween('check_in', [$startOfMonth, $endOfMonth->endOfDay()])
            ->get()
            ->keyBy(fn($attendance) => $attendance->check_in->format('Y-m-d'));

        return view('admin.staff_attendance_history', compact('staff', 'attendances', 'currentMonth', 'dates', 'lastMonth', 'nextMonth'));
    }
}
