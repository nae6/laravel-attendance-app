<?php

namespace App\Http\Controllers;

use App\Services\AttendanceActionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttendanceActionController extends Controller
{
    public function __construct(
        private AttendanceActionService $attendanceActionService
    ) {
    }

    /**
     * 勤怠打刻画面の表示
     *
     * @return View
     */
    public function edit(): View {
        $data = $this->attendanceActionService->getAttendanceActionData(Auth::id());

        return view('user.index', $data);
    }

    /**
     * 出勤打刻
     *
     * @return RedirectResponse
     */
    public function startWork(): RedirectResponse {
        $result = $this->attendanceActionService->startWork(Auth::id());

        return redirect()->route('attendance')->with($result);
    }

    /**
     * 休憩入り打刻
     *
     * @return RedirectResponse
     */
    public function startBreak(): RedirectResponse {
        $result = $this->attendanceActionService->startBreak(Auth::id());

        return redirect()->route('attendance')->with($result);
    }

    /**
     * 休憩戻り打刻
     *
     * @return RedirectResponse
     */
    public function endBreak(): RedirectResponse {
        $result = $this->attendanceActionService->endBreak(Auth::id());

        return redirect()->route('attendance')->with($result);
    }

    /**
     * 退勤打刻
     *
     * @return RedirectResponse
     */
    public function endWork(): RedirectResponse {
        $result = $this->attendanceActionService->endWork(Auth::id());

        return redirect()->route('attendance')->with($result);
    }
}
