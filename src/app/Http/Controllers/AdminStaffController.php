<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;
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

        $monthData = $this->getMonthData($request);

        // １ヶ月分の勤怠データ取得
        $attendances = Attendance::with('breakRecords', 'user')
            ->where('user_id', $staff->id)
            ->whereBetween('check_in', [
                $monthData['startOfMonth'],
                $monthData['endOfMonth']->copy()->endOfDay(),
            ])
            ->get()
            ->keyBy(fn($attendance) => $attendance->check_in->format('Y-m-d'));

        return view('admin.staff_attendance_history', [
            'staff' => $staff,
            'attendances' => $attendances,
            'currentMonth' => $monthData['currentMonth'],
            'dates' => $monthData['dates'],
            'lastMonth' => $monthData['lastMonth'],
            'nextMonth' => $monthData['nextMonth'],
        ]);
    }

    /**
     * スタッフ別月次勤怠一覧のCSVエクスポート
     *
     * @return StreamedResponse
     */
    public function export(Request $request, User $staff): StreamedResponse {
        abort_if($staff->role !== 'user', 404);

        $monthData = $this->getMonthData($request);
        $dates = $monthData['dates'];

        $attendances = Attendance::with('breakRecords', 'user')
            ->where('user_id', $staff->id)
            ->whereBetween('check_in', [
                $monthData['startOfMonth'],
                $monthData['endOfMonth']->copy()->endOfDay(),
            ])
            ->get()
            ->keyBy(fn($attendance) => $attendance->check_in->format('Y-m-d'));

        $fileName = $staff->name . '_' . $monthData['currentMonth']->format('Y-m') . '_attendance.csv';

        return response()->streamDownload(
            function () use ($dates, $attendances) {
                $stream = fopen('php://output', 'w');

                // Excelの文字化け対策
                fwrite($stream, "\xEF\xBB\xBF");

                // ヘッダー行
                fputcsv($stream, [
                    '日付',
                    '出勤',
                    '退勤',
                    '休憩',
                    '合計',
                ]);

                // 日付・データの取り出し
                foreach ($dates as $date) {
                    $attendance = $attendances->get($date->format('Y-m-d'));

                    fputcsv($stream, [
                        $date->format('m/d') . '(' . $date->isoFormat('ddd') . ')',
                        $attendance?->check_in?->format('H:i') ?? '',
                        $attendance?->check_out?->format('H:i') ?? '',
                        $attendance?->break_time ?? '',
                        $attendance?->work_time ?? '',
                    ]);
                }

                fclose($stream);
            },
            $fileName,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    /**
     * 日付取得部分の共通化
     */
    private function getMonthData(Request $request): array
    {
        $currentMonth = Carbon::parse($request->input('month', today()->format('Y-m')));

        $lastMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $dates = CarbonPeriod::create($startOfMonth, $endOfMonth);

        return compact(
            'currentMonth',
            'lastMonth',
            'nextMonth',
            'startOfMonth',
            'endOfMonth',
            'dates'
        );
    }
}
