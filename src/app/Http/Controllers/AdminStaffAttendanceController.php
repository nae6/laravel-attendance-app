<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\User;
use App\Services\AdminStaffService;

class AdminStaffAttendanceController extends Controller
{
    public function __construct(
        private AdminStaffService $adminStaffService
    ) {
    }

    /**
     * スタッフ別月次勤怠一覧画面の表示
     *
     * @return View
     */
    public function index(Request $request, User $staff): View {
        $this->authorize('viewAttendance', $staff);

        $monthData = $this->adminStaffService->getMonthlyAttendanceData(
            $staff,
            $request->input('month')
        );

        return view('admin.staff_attendance_history', array_merge($monthData, compact('staff')));
    }

    /**
     * スタッフ別月次勤怠一覧のCSVエクスポート
     *
     * @return StreamedResponse
     */
    public function export(Request $request, User $staff): StreamedResponse {
        $this->authorize('viewAttendance', $staff);

        $monthData = $this->adminStaffService->getMonthlyAttendanceData(
            $staff,
            $request->input('month')
        );
        $csv = $this->adminStaffService->buildCsv($monthData);

        $fileName = $staff->name . '_' . $monthData['currentMonth']->format('Y-m') . '_attendance.csv';

        return response()->streamDownload(
            function () use ($csv) {
                echo $csv;
            },
            $fileName,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }
}
