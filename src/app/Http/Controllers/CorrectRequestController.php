<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectRequestFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\AttendanceCorrectRequest;
use App\Models\BreakCorrectRequest;
use App\Models\Attendance;

class CorrectRequestController extends Controller
{
    /**
     * 勤怠の修正申請(user)
     *
     * @return RedirectResponse
     */
    public function update(AttendanceCorrectRequestFormRequest $request, Attendance $attendance): RedirectResponse {
        abort_if($attendance->user_id !== Auth::id(), 403);

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($attendance, $validated) {
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
        } catch (\Throwable $e) {
            Log::error('勤怠修正申請の保存に失敗', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'system_error' => '修正申請の保存に失敗しました'
            ]);
        }
    }

    /**
     * 申請一覧の表示(user)
     *
     * @return View
     */
    public function index(): View {
        // 承認待ち
        $pendingRequests = AttendanceCorrectRequest::with('attendance.user')
            ->forUser(Auth::id())
            ->where('approval_status', AttendanceCorrectRequest::STATUS_PENDING)
            ->latest()
            ->get();

        // 承認済
        $approvedRequests = AttendanceCorrectRequest::with(['attendance.user'])
            ->forUser(Auth::id())
            ->where('approval_status', AttendanceCorrectRequest::STATUS_APPROVED)
            ->latest()
            ->get();

        return view('user.stamp_correct_request', compact('pendingRequests', 'approvedRequests'));
    }
}
