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
        $attendances = Attendance::whereHas('user', function ($query) {
            $query->where('role', 'user');
        })
            ->inRandomOrder()
            ->take(5)
            ->get();

        // 未承認3件
        $attendances->take(3)->each(function ($attendance) {
            AttendanceCorrectRequest::factory()
                ->pending()
                ->forAttendance($attendance)
                ->create();
        });

        // 承認済2件
        $attendances->skip(3)->take(2)->each(function ($attendance) {
            AttendanceCorrectRequest::factory()
                ->approved()
                ->forAttendance($attendance)
                ->create();
        });
    }
}
