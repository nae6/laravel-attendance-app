<?php

namespace Tests\Feature\user;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Attendance;
use App\Models\BreakRecord;
use App\Models\User;
use Tests\TestCase;

class AttendanceActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠登録画面の現在日時の表示
     */
    public function test_attendance_page_shows_current_date_and_time(): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('attendance'));

        $response->assertOk();
        $response->assertSee(now()->isoFormat('YYYY年MM月DD日(ddd)'));
        $response->assertSee(now()->format('H:i'));
    }

    /**
     * status:勤務外の表示
     */
    public function test_attendance_page_shows_out_of_work_status(): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('attendance'));

        $response->assertOk();
        $response->assertSee('勤務外');
    }

    /**
     * status:出勤中の表示
     */
    public function test_attendance_page_shows_working_status(): void {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => now(),
            'check_out' => null,
            'status' => '出勤中',
        ]);

        $response = $this->actingAs($user)->get(route('attendance'));

        $response->assertOk();
        $response->assertSee('出勤中');
    }

    /**
     * status:休憩中の表示
     */
    public function test_attendance_page_shows_break_status(): void {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => now(),
            'check_out' => null,
            'status' => '休憩中',
        ]);

        $response = $this->actingAs($user)->get(route('attendance'));

        $response->assertOk();
        $response->assertSee('休憩中');
    }

    /**
     * status:退勤済の表示
     */
    public function test_attendance_page_shows_finished_status(): void {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => now()->subHours(8),
            'check_out' => now(),
            'status' => '退勤済',
        ]);

        $response = $this->actingAs($user)->get(route('attendance'));

        $response->assertOk();
        $response->assertSee('退勤済');
    }

    /**
     * 出勤ボタンの表示
     */
    public function test_attendance_page_shows_start_button_when_not_working(): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('attendance'));

        $response->assertOk();
        $response->assertSee('勤務外');

        $response->assertSee('出勤');
        $response->assertSee('<button type="submit" class="form__btn--black">出勤</button>', false);
    }

    /**
     * 出勤処理でステータスが出勤中になる
     */
    public function test_user_can_start_work(): void {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('attendance.start'));

        $response->assertRedirect(route('attendance'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => '出勤中',
        ]);

        $response = $this->actingAs($user)->get(route('attendance'));
        $response->assertOk();
        $response->assertSee('出勤中');
    }

    /**
     * 出勤は１日に１回のみ行える
     */
    public function test_user_cannot_start_work_more_than_once_per_day(): void
    {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => now()->subHours(8),
            'check_out' => now(),
            'status' => '退勤済',
        ]);

        $response = $this->actingAs($user)->get(route('attendance'));

        $response->assertOk();
        $response->assertDontSee('<button type="submit" class="form__btn--black">出勤</button>', false);
    }

    /**
     * test6-3:出勤時刻が勤怠一覧画面で確認できる
     */

    /**
     * 休憩ボタンの表示
     */
    public function test_attendance_page_shows_break_start_button_when_working(): void
    {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => now(),
            'check_out' => null,
            'status' => '出勤中',
        ]);

        $response = $this->actingAs($user)->get(route('attendance'));

        $response->assertOk();
        $response->assertSee('出勤中');

        $response->assertSee('休憩入');
        $response->assertSee('<button type="submit" class="form__btn--white">休憩入</button>', false);
    }

    /**
     * 休憩処理でステータスが休憩中になる
     */
    public function test_user_can_start_break(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => now(),
            'check_out' => null,
            'status' => '出勤中',
        ]);

        $response = $this->actingAs($user)->post(route('break.start'));

        $response->assertRedirect(route('attendance'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => '休憩中',
        ]);

        $this->assertDatabaseHas('break_records', [
            'attendance_id' => $attendance->id,
            'break_end' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance'));
        $response->assertOk();
        $response->assertSee('休憩中');
    }

    /**
     * 休憩は１日に何回でもできる
     */
    public function test_user_can_stamp_some_breaks(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => now()->subHours(5),
            'check_out' => null,
            'status' => '出勤中',
        ]);
        BreakRecord::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => now()->subHours(2),
            'break_end' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance'));

        $response->assertOk();
        $response->assertSee('休憩入');

        $response->assertSee('<button type="submit" class="form__btn--white">休憩入</button>', false);
    }

    /**
     * 休憩戻りボタンの表示
     */
    public function test_attendance_page_shows_break_end_button_when_breaking(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => now()->subHours(4),
            'check_out' => null,
            'status' => '休憩中',
        ]);
        BreakRecord::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => now()->subHours(1),
            'break_end' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance'));

        $response->assertOk();
        $response->assertSee('<button type="submit" class="form__btn--black">休憩戻</button>', false);
    }

    /**
     * 休憩戻り処理でステータスが出勤中になる
     */
    public function test_user_can_end_break(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => now()->subHours(4),
            'check_out' => null,
            'status' => '出勤中',
        ]);
        BreakRecord::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => now()->subHours(1),
            'break_end' => null,
        ]);

        $response = $this->actingAs($user)->post(route('break.end'));

        $response->assertRedirect(route('attendance'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => '出勤中',
        ]);

        $this->assertDatabaseHas('break_records', [
            'attendance_id' => $attendance->id,
            'break_end' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('attendance'));
        $response->assertOk();
        $response->assertSee('出勤中');
    }

    /**
     * 休憩戻りは１日に何回でもできる
     */
    public function test_user_can_end_break_multiple_times_in_a_day(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => now()->subHours(5),
            'check_out' => null,
            'status' => '休憩中',
        ]);
        BreakRecord::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => now()->subHours(2),
            'break_end' => now()->subHour(),
        ]);
        BreakRecord::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => now(),
            'break_end' => null,
        ]);

        $response = $this->actingAs($user)->get(route('attendance'));

        $response->assertOk();
        $response->assertSee('<button type="submit" class="form__btn--black">休憩戻</button>', false);
    }

    /**
     * 退勤ボタンの表示
     */
    public function test_attendance_page_shows_finished_button_when_working(): void
    {
        $user = User::factory()->create();
        Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => now(),
            'check_out' => null,
            'status' => '出勤中',
        ]);

        $response = $this->actingAs($user)->get(route('attendance'));

        $response->assertOk();
        $response->assertSee('出勤中');
        $response->assertSee('<button type="submit" class="form__btn--black">退勤</button>', false);
    }
}