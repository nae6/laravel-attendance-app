<?php

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;
use App\Models\BreakCorrectRequest;
use App\Models\BreakRecord;
use App\Models\User;
use Tests\TestCase;

class RelationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * User::attendances() でログインユーザーに紐づくAttendance一覧が取得できる
     */
    public function test_user_attendances_relation(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue($user->attendances->contains('id', $attendance->id));
    }

    /**
     * BreakRecord::attendance() で紐づくAttendanceが取得できる
     */
    public function test_break_record_attendance_relation(): void
    {
        $attendance = Attendance::factory()->create();

        $breakRecord = BreakRecord::factory()->create([
            'attendance_id' => $attendance->id,
        ]);

        $this->assertTrue($breakRecord->attendance->is($attendance));
    }

    /**
     * BreakCorrectRequest::attendanceCorrectRequest() で紐づくAttendanceCorrectRequestが取得できる
     */
    public function test_break_correct_request_attendance_correct_request_relation(): void
    {
        $correctRequest = AttendanceCorrectRequest::factory()->create();

        $breakCorrectRequest = BreakCorrectRequest::factory()->create([
            'attendance_correct_request_id' => $correctRequest->id,
        ]);

        $this->assertTrue($breakCorrectRequest->attendanceCorrectRequest->is($correctRequest));
    }
}
