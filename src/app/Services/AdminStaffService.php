<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AdminStaffService
{
    /**
     * スタッフ一覧に表示するユーザーを取得する。
     */
    public function getStaffList(): Collection
    {
        return User::where('role', 'user')
            ->select('id', 'name', 'email')
            ->get();
    }

    /**
     * スタッフ別月次勤怠一覧に必要な情報を取得する。
     *
     * @return array{attendances: Collection, currentMonth: Carbon, dates: CarbonPeriod, lastMonth: string, nextMonth: string, startOfMonth: Carbon, endOfMonth: Carbon}
     */
    public function getMonthlyAttendanceData(User $staff, ?string $month): array
    {
        $currentMonth = Carbon::parse($month ?? today()->format('Y-m'));

        $lastMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        $dates = CarbonPeriod::create($startOfMonth, $endOfMonth);

        $attendances = Attendance::with('breakRecords', 'user')
            ->where('user_id', $staff->id)
            ->whereBetween('check_in', [
                $startOfMonth,
                $endOfMonth->copy()->endOfDay(),
            ])
            ->get()
            ->keyBy(fn (Attendance $attendance) => $attendance->check_in->format('Y-m-d'));

        return compact(
            'attendances',
            'currentMonth',
            'dates',
            'lastMonth',
            'nextMonth',
            'startOfMonth',
            'endOfMonth'
        );
    }

    /**
     * スタッフ別勤怠CSVの内容を組み立てる。
     */
    public function buildCsv(array $monthData): string
    {
        $stream = fopen('php://temp', 'r+');

        // Excelの文字化け対策
        fwrite($stream, "\xEF\xBB\xBF");

        fputcsv($stream, ['日付', '出勤', '退勤', '休憩', '合計']);

        foreach ($this->getCsvRows($monthData) as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }

    /**
     * スタッフ別勤怠CSVのデータ行を組み立てる。
     *
     * @return array<int, array<int, string>>
     */
    private function getCsvRows(array $monthData): array
    {
        $rows = [];

        foreach ($monthData['dates'] as $date) {
            $attendance = $monthData['attendances']->get($date->format('Y-m-d'));

            $rows[] = [
                $date->format('m/d') . '(' . $date->isoFormat('ddd') . ')',
                $attendance?->check_in?->format('H:i') ?? '',
                $attendance?->check_out?->format('H:i') ?? '',
                $attendance?->break_time ?? '',
                $attendance?->work_time ?? '',
            ];
        }

        return $rows;
    }
}
