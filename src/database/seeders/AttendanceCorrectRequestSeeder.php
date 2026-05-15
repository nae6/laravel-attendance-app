<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;

class AttendanceCorrectRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attendances = Attendance::take(15)->get();

        // 未承認10件
        $attendances->take(10)->each(function ($attendance) {
            AttendanceCorrectRequest::factory()
                ->pending()
                ->create([
                    'attendance_id' => $attendance->id,
                ]);
        });

        // 承認済5件
        $attendances->skip(10)->take(5)->each(function ($attendance) {
            AttendanceCorrectRequest::factory()
                ->approved()
                ->create([
                    'attendance_id' => $attendance->id,
                ]);
        });
    }
}
