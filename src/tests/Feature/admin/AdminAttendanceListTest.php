<?php

namespace Tests\Feature\admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Attendance;
use App\Models\BreakRecord;
use App\Models\User;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * その日の勤怠情報が全て表示されている
     */
    public function test_admin_attendance_history_shows_all_records_for_the_day(): void
    {
        $today = today();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user1 = User::factory()->create([
            'name' => '山田太郎'
        ]);

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
            'check_in' => $today->copy()->setTime(9, 0),
            'check_out' => $today->copy()->setTime(18, 0),
        ]);
        BreakRecord::factory()->create([
            'attendance_id' => $attendance1->id,
            'break_start' => $today->copy()->setTime(12, 0),
            'break_end' => $today->copy()->setTime(13, 0),
        ]);

        $user2 = User::factory()->create([
            'name' => '鈴木花子'
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'check_in' => $today->copy()->setTime(10, 0),
            'check_out' => $today->copy()->setTime(17, 0),
        ]);
        BreakRecord::factory()->create([
            'attendance_id' => $attendance2->id,
            'break_start' => $today->copy()->setTime(12, 0),
            'break_end' => $today->copy()->setTime(14, 0),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.index'));

        $response->assertOk();

        $response->assertSee('山田太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');

        $response->assertSee('鈴木花子');
        $response->assertSee('10:00');
        $response->assertSee('17:00');
        $response->assertSee('2:00');
        $response->assertSee('5:00');
    }

    /**
     * 遷移した際に現在の日付が表示されている
     */
    public function test_admin_attendance_history_shows_today(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.index'));

        $response->assertOk();

        $expectedDay = today()->format('Y/m/d');
        $response->assertSee($expectedDay);
    }

    /**
     * 前日のボタンを押すと前日の日付が表示されている
     */
    public function test_admin_attendance_history_shows_previous_day(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $lastDay = now()->subDay();

        $response = $this->actingAs($admin)->get(route('admin.attendance.index', ['date' => $lastDay->format('Y-m-d')]));

        $response->assertOk();
        $response->assertSee($lastDay->format('Y/m/d'));
    }

    /**
     * 翌日のボタンを押すと翌日の日付が表示されている
     */
    public function test_admin_attendance_history_shows_next_day(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $nextDay = now()->addDay();

        $response = $this->actingAs($admin)->get(route('admin.attendance.index', ['date' => $nextDay->format('Y-m-d')]));

        $response->assertOk();
        $response->assertSee($nextDay->format('Y/m/d'));
    }
}
