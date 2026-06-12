<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectRequestFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Enums\AttendanceCorrectRequestStatus;
use App\Models\AttendanceCorrectRequest;
use App\Models\BreakCorrectRequest;
use App\Models\Attendance;
use Carbon\Carbon;

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
                $attendanceDate = $attendance->check_in->toDateString();

                $correctRequest = AttendanceCorrectRequest::create([
                    'attendance_id' => $attendance->id,
                    'requested_check_in' => Carbon::parse($attendanceDate . ' ' . $validated['check_in']),
                    'requested_check_out' => Carbon::parse($attendanceDate . ' ' . $validated['check_out']),
                    'reason' => $validated['reason'],
                ]);

                foreach ($validated['breaks'] ?? [] as $break) {
                    if (empty($break['break_start']) && empty($break['break_end'])) {
                        continue;
                    }

                    BreakCorrectRequest::create([
                        'attendance_correct_request_id' => $correctRequest->id,
                        'requested_break_start' => Carbon::parse($attendanceDate . ' ' . $break['break_start']),
                        'requested_break_end' => Carbon::parse($attendanceDate . ' ' . $break['break_end']),
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
     * 申請一覧の表示(user/admin共通)
     *
     * @return View
     */
    public function index(Request $request): View {
        $viewType = $request->attributes->get('view_type');

        $baseQuery = AttendanceCorrectRequest::with('attendance.user')
            ->latest('created_at');

        if ($viewType === 'user') {
            $baseQuery->forUser(Auth::id());
        }

        $pendingRequests = (clone $baseQuery)
            ->where('approval_status', AttendanceCorrectRequestStatus::Pending)
            ->get();

        $approvedRequests = (clone $baseQuery)
            ->where('approval_status', AttendanceCorrectRequestStatus::Approved)
            ->get();

        return view('common.request_history', compact('pendingRequests', 'approvedRequests', 'viewType'));
    }
}
