<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Attendance::factory()->create([
            'user_id' => ,
            'check_in' => now(),
            'check_out' => now(),
            'status' => '退勤済',
        ]);

        Attendance::factory()->count(3)->create();
    }
}