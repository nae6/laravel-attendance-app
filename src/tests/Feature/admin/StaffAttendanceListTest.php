<?php

namespace Tests\Feature\admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\BreakRecord;
use App\Models\Attendance;
use App\Models\User;
use Tests\TestCase;
use Carbon\Carbon;

class StaffAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 選択したスタッフの月次勤怠一覧が表示される
     */
    public function test_admin_can_see_attendance_history_for_selected_staff(): void {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'name' => 'テスト太郎'
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-05-01 09:00:00',
            'check_out' => '2026-05-01 18:00:00',
        ]);
        BreakRecord::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-05-01 12:00:00',
            'break_end' => '2026-05-01 13:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-05-02 10:00:00',
            'check_out' => '2026-05-02 16:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('staff.attendance.list', ['staff' => $user->id]));

        $response->assertOk();

        $response->assertSee('テスト太郎');

        $response->assertSee('05/01(金)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');

        $response->assertSee('05/02(土)');
        $response->assertSee('10:00');
        $response->assertSee('16:00');
        $response->assertSee('6:00');
    }

    /**
     * 前月のボタンを押すと前月の情報が表示されている
     */
    public function test_admin_can_see_attendance_history_for_before_month(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        Carbon::setTestNow('2026-06-15');
        $lastMonth = now()->subMonth();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => $lastMonth->copy()->day(1)->setTime(9, 0),
            'check_out' => $lastMonth->copy()->day(1)->setTime(18, 0),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('staff.attendance.list', ['staff' => $user->id, 'month' => $lastMonth->format('Y-m')]));

        $response->assertOk();
        $response->assertSee('05/01(金)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('8:00');
    }

    /**
     * 翌月のボタンを押すと翌月の情報が表示されている
     */
    public function test_admin_can_see_attendance_history_for_next_month(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        Carbon::setTestNow('2026-06-15');
        $nextMonth = now()->addMonth();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => $nextMonth->copy()->day(1)->setTime(9, 0),
            'check_out' => $nextMonth->copy()->day(1)->setTime(18, 0),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('staff.attendance.list', ['staff' => $user->id, 'month' => $nextMonth->format('Y-m')]));

        $response->assertOk();
        $response->assertSee('07/01(水)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('8:00');
    }

    /**
     * 詳細リンクが一覧画面にある
     */
    public function test_staff_attendance_history_has_detail_link(): void
    {
        Carbon::setTestNow('2026-06-15');
        $currentMonth = now();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => $currentMonth->copy()->day(1)->setTime(9, 0),
            'check_out' => $currentMonth->copy()->day(1)->setTime(18, 0),
        ]);

        $response = $this->actingAs($admin)->get(
            route('staff.attendance.list', [
                'staff' => $user->id,
                'month' => $currentMonth->format('Y-m')
            ])
        );

        $response->assertOk();

        $response->assertSee(route('admin.attendance.edit', $attendance->id), false);
    }

    /**
     * 選択した日の詳細ページが正しく表示される
     */
    public function test_attendance_detail_page_is_displayed(): void
    {
        Carbon::setTestNow('2026-06-15');
        $currentMonth = now();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => $currentMonth->copy()->day(1)->setTime(9, 0),
            'check_out' => $currentMonth->copy()->day(1)->setTime(18, 0),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.attendance.edit', $attendance->id));

        $response->assertOk();
        $response->assertSee($attendance->check_in->format('Y年'));
        $response->assertSee($attendance->check_in->format('n月j日'));
    }
}
