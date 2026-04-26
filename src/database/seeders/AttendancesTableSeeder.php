<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::all()->each(function ($user) {
            // 今日
            Attendance::factory()->create([
                'user_id' => $user->id,
                'check_in' => now(),
                'check_out' => now()->addHours(4),
                'status' => '退勤済',
            ]);

            // 前日〜3日前
            for ($i = 1; $i <= 3; $i++) {
                Attendance::factory()->count(3)->create([
                    'user_id' => $user->id,
                    'check_in' => Carbon::now()->subDays($i)->setTime(rand(8, 10), rand(0, 59)),
                    'check_out' => Carbon::now()->subDays($i)->setTime(rand(13, 19), rand(0, 59)),
                    'status' => '退勤済',
                ]);
            }
        });
    }
}