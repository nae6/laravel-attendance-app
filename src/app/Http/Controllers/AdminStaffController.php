<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\User;
use App\Services\AdminStaffService;

class AdminStaffController extends Controller
{
    public function __construct(
        private AdminStaffService $adminStaffService
    ) {
    }

    /**
     * スタッフ一覧画面の表示
     *
     * @return View
     */
    public function staffList(): View {
        $users = $this->adminStaffService->getStaffList();

        return view('admin.staff_list', compact('users'));
    }

    /**
     * スタッフ別月次勤怠一覧画面の表示
     *
     * @return View
     */
    public function index(Request $request, User $staff): View {
        abort_if($staff->role !== 'user', 404);

        $monthData = $this->adminStaffService->getMonthlyAttendanceData(
            $staff,
            $request->input('month')
        );

        return view('admin.staff_attendance_history', [
            'staff' => $staff,
            'attendances' => $monthData['attendances'],
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

        $monthData = $this->adminStaffService->getMonthlyAttendanceData(
            $staff,
            $request->input('month')
        );
        $rows = $this->adminStaffService->getCsvRows($monthData);

        $fileName = $staff->name . '_' . $monthData['currentMonth']->format('Y-m') . '_attendance.csv';

        return response()->streamDownload(
            function () use ($rows) {
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

                foreach ($rows as $row) {
                    fputcsv($stream, $row);
                }

                fclose($stream);
            },
            $fileName,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

}
