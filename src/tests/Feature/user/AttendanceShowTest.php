<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\BreakRecord;
use App\Models\Attendance;
use App\Models\User;
use Tests\TestCase;

class AttendanceShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 詳細ページにログインユーザーの名前が表示される
     */
    public function test_attendance_detail_page_shows_login_user_name(): void
    {
        $user = User::factory()->create([
            'name' => 'テスト太郎',
        ]);
        User::factory()->create([
            'name' => '別ユーザー',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.edit', $attendance->id));

        $response->assertOk();
        $response->assertSee('テスト太郎');
        $response->assertDontSee('別ユーザー');
    }

    /**
     * 詳細画面の日付が選択した日付になっている
     * (詳細ページの日付の表示確認)
     */
    public function test_attendance_detail_page_shows_correct_date(): void
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

    /**
     * 詳細画面の日付が選択した日付になっている
     * (一覧画面の詳細リンクは選択した日の詳細画面に繋がっている)
     */
    public function test_attendance_history_detail_link_points_to_selected_attendance(): void
    {
        $user = User::factory()->create();

        $selectedAttendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-05-02 10:00:00',
            'check_out' => '2026-05-02 17:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.edit', $selectedAttendance->id));

        $response->assertOk();
        $response->assertSee(route('attendance.edit', $selectedAttendance->id), false);
    }

    /**
     * 出勤・退勤時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_shows_check_in_and_out_for_login_user(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-05-01 09:00:00',
            'check_out' => '2026-05-01 18:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.edit', $attendance->id));

        $response->assertOk();
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 休憩の合計時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_shows_break_records_for_login_user(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-05-01 09:00:00',
            'check_out' => '2026-05-01 18:00:00',
        ]);
        BreakRecord::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-05-01 11:00:00',
            'break_end' => '2026-05-01 12:00:00',
        ]);
        BreakRecord::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-05-01 15:00:00',
            'break_end' => '2026-05-01 16:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.index', $attendance->id));

        $response->assertOk();
        $response->assertSee('2:00');
    }
}
