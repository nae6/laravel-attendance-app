<?php

namespace Tests\Feature\user;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Attendance;
use App\Models\BreakRecord;
use App\Models\User;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログインユーザーの勤怠情報が全て表示されている
     */
    public function test_attendance_history_shows_all_records_for_login_user(): void {
        $user = User::factory()->create();

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-05-01 09:00:00',
            'check_out' => '2026-05-01 18:00:00',
        ]);
        BreakRecord::factory()->create([
            'attendance_id' => $attendance1->id,
            'break_start' => '2026-05-01 12:00:00',
            'break_end' => '2026-05-01 13:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-05-02 10:00:00',
            'check_out' => '2026-05-02 17:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertOk();
        $response->assertSee('05/01(金)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');

        $response->assertSee('05/02(土)');
        $response->assertSee('10:00');
        $response->assertSee('17:00');
        $response->assertSee('7:00');
    }

    /**
     * 勤怠一覧画面に現在の月が表示されている
     */
    public function test_attendance_history_shows_current_month(): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertOk();

        $expectedMonth = now()->format('Y/m');
        $response->assertSee($expectedMonth);
    }

    /**
     * 前月のボタンを押すと前月の月が表示されている
     */
    public function test_attendance_history_shows_before_month(): void
    {
        $user = User::factory()->create();
        $lastMonth = now()->subMonth()->format('Y-m');

        $response = $this->actingAs($user)->get(route('attendance.index', ['month' => $lastMonth]));

        $response->assertOk();
        $response->assertSee($lastMonth);
    }

    /**
     * 翌月のボタンを押すと翌月の月が表示されている
     */
    public function test_attendance_history_shows_next_month(): void
    {
        $user = User::factory()->create();
        $nextMonth = now()->addMonth()->format('Y-m');

        $response = $this->actingAs($user)->get(route('attendance.index', ['month' => $nextMonth]));

        $response->assertOk();
        $response->assertSee($nextMonth);
    }

    /**
     * 詳細リンクが一覧画面にある
     */
    public function test_attendance_history_has_detail_link(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-05-02 10:00:00',
            'check_out' => '2026-05-02 17:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index'));

        $response->assertOk();
        $response->assertSee(route('attendance.edit', $attendance->id));
    }

    /**
     * 詳細ページが正しく表示される
     */
    public function test_attendance_detail_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-05-02 10:00:00',
            'check_out' => '2026-05-02 17:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.edit', $attendance->id));

        $response->assertOk();
        $response->assertSee('2026年');
        $response->assertSee('5月2日');
    }
}
