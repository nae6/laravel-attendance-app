<?php

namespace App\Http\Controllers;

use App\Exceptions\AlreadyClockedInException;
use App\Exceptions\AlreadyClockedOutException;
use App\Exceptions\NoActiveBreakException;
use App\Exceptions\NotClockedInException;
use App\Services\AttendanceActionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        try {
            $this->attendanceActionService->startWork(Auth::id());
        } catch (AlreadyClockedInException) {
            return $this->redirectWith('message', '本日の出勤は打刻済みです');
        }

        return $this->redirectWith('message', '出勤しました');
    }

    /**
     * 休憩入り打刻
     *
     * @return RedirectResponse
     */
    public function startBreak(): RedirectResponse {
        try {
            $this->attendanceActionService->startBreak(Auth::id());
        } catch (NotClockedInException) {
            return $this->redirectWith('message', '本日の出勤記録がありません');
        } catch (AlreadyClockedOutException) {
            return $this->redirectWith('message', '本日は退勤済みです');
        } catch (\Throwable $e) {
            Log::error('休憩開始の打刻に失敗', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return $this->redirectWith('error', '休憩開始に失敗しました');
        }

        return redirect()->route('attendance');
    }

    /**
     * 休憩戻り打刻
     *
     * @return RedirectResponse
     */
    public function endBreak(): RedirectResponse {
        try {
            $this->attendanceActionService->endBreak(Auth::id());
        } catch (NotClockedInException) {
            return $this->redirectWith('message', '本日の出勤記録がありません');
        } catch (AlreadyClockedOutException) {
            return $this->redirectWith('message', '本日は退勤済みです');
        } catch (NoActiveBreakException) {
            return $this->redirectWith('message', '終了できる休憩がありません');
        } catch (\Throwable $e) {
            Log::error('休憩終了の打刻に失敗', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return $this->redirectWith('error', '休憩終了に失敗しました');
        }

        return redirect()->route('attendance');
    }

    /**
     * 退勤打刻
     *
     * @return RedirectResponse
     */
    public function endWork(): RedirectResponse {
        try {
            $this->attendanceActionService->endWork(Auth::id());
        } catch (NotClockedInException) {
            return $this->redirectWith('message', '本日の出勤記録がありません');
        } catch (AlreadyClockedOutException) {
            return $this->redirectWith('message', '本日の退勤は打刻済みです');
        }

        return $this->redirectWith('message', '退勤しました');
    }

    /**
     * 打刻画面へフラッシュメッセージ付きでリダイレクト
     *
     * @return RedirectResponse
     */
    private function redirectWith(string $key, string $message): RedirectResponse {
        return redirect()->route('attendance')->with($key, $message);
    }
}
