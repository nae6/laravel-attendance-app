<?php

namespace App\Http\Controllers;

use App\Exceptions\AlreadyClockedInException;
use App\Exceptions\AlreadyClockedOutException;
use App\Exceptions\NoActiveBreakException;
use App\Exceptions\NotClockedInException;
use App\Services\AttendanceActionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function index(Request $request): View {
        $data = $this->attendanceActionService->getAttendanceActionData($request->user()->id);

        return view('user.index', $data);
    }

    /**
     * 出勤打刻
     *
     * @return RedirectResponse
     */
    public function startWork(Request $request): RedirectResponse {
        try {
            $this->attendanceActionService->startWork($request->user()->id);
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
    public function startBreak(Request $request): RedirectResponse {
        try {
            $this->attendanceActionService->startBreak($request->user()->id);
        } catch (NotClockedInException) {
            return $this->redirectWith('message', '本日の出勤記録がありません');
        } catch (AlreadyClockedOutException) {
            return $this->redirectWith('message', '本日は退勤済みです');
        } catch (\Throwable $e) {
            Log::error('休憩開始の打刻に失敗', [
                'user_id' => $request->user()->id,
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
    public function endBreak(Request $request): RedirectResponse {
        try {
            $this->attendanceActionService->endBreak($request->user()->id);
        } catch (NotClockedInException) {
            return $this->redirectWith('message', '本日の出勤記録がありません');
        } catch (AlreadyClockedOutException) {
            return $this->redirectWith('message', '本日は退勤済みです');
        } catch (NoActiveBreakException) {
            return $this->redirectWith('message', '終了できる休憩がありません');
        } catch (\Throwable $e) {
            Log::error('休憩終了の打刻に失敗', [
                'user_id' => $request->user()->id,
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
    public function endWork(Request $request): RedirectResponse {
        try {
            $this->attendanceActionService->endWork($request->user()->id);
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
