<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BreakRecord;
use App\Models\Attendance;
use App\Models\User;
use Carbon\CarbonPeriod;
use Carbon\Carbon;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::where('role', 'user')->each(function ($user) {
            // 前月の日付一覧
            $base = Carbon::now()->subMonthNoOverflow();

            $start = $base->copy()->startOfMonth();
            $end = $base->copy()->endOfMonth();

            $dates = collect(CarbonPeriod::create($start, $end));

            // 20日分のデータ作成
            $randomDates = $dates->shuffle()->take(20);

            foreach ($randomDates as $date) {
                $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'check_in' => $date->copy()->setTime(rand(8, 10), rand(0, 59)),
                    'check_out' => $date->copy()->setTime(rand(17, 21), rand(0, 59)),
                    'status' => '退勤済',
                ]);

                // 紐づく休憩データを作成
                $breakStart = $date->copy()->setTime(rand(11, 14), rand(0, 59));

                BreakRecord::factory()
                    ->create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $breakStart,
                        'break_end' => $breakStart->copy()->addMinutes(rand(30, 90)),
                    ]);
            }
        });
    }
}