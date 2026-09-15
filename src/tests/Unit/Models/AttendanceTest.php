<?php

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Attendance;
use App\Models\BreakRecord;
use App\Models\User;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 休憩が終了していない場合、当該休憩は0分として扱われる
     */
    public function test_break_minutes_treats_unfinished_break_as_zero(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-06-10 09:00:00',
            'check_out' => '2026-06-10 18:00:00',
        ]);

        // 終了している休憩(30分)
        BreakRecord::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-06-10 12:00:00',
            'break_end' => '2026-06-10 12:30:00',
        ]);

        // 終了していない(進行中の)休憩
        BreakRecord::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-06-10 15:00:00',
            'break_end' => null,
        ]);

        $attendance->refresh();

        $this->assertSame(30, $attendance->break_minutes);
        $this->assertSame('0:30', $attendance->break_time);
    }
}
