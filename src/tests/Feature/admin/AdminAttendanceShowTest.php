<?php

namespace Tests\Feature\admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\BreakRecord;
use App\Models\Attendance;
use App\Models\User;
use Tests\TestCase;

class AdminAttendanceShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 選択した勤怠情報が表示される
     */
    public function test_admin_attendance_detail_page_shows_selected_attendance_record(): void
    {
        $user = User::factory()->create([
            'name' => 'テスト太郎',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-05-02 10:00:00',
            'check_out' => '2026-05-02 17:00:00',
        ]);

        BreakRecord::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-05-02 11:00:00',
            'break_end' => '2026-05-02 12:00:00',
        ]);

        $response = $this->actingAs($user)->get(route('attendance.edit', $attendance->id));

        $response->assertOk();
        $response->assertSee('テスト太郎');
        $response->assertSee('2026年');
        $response->assertSee('5月2日');
        $response->assertSee('10:00');
        $response->assertSee('17:00');
        $response->assertSee('11:00');
        $response->assertSee('12:00');
        $response->assertSee(route('attendance.edit', $attendance->id), false);
    }


    /**
     * 出勤時間が退勤時間より後になっている場合のエラーメッセージ
     */
    public function test_error_message_for_check_in_after_check_out(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->from(route('attendance.edit', $attendance->id))
            ->put(route('attendance.update', $attendance->id), [
                'check_in' => '11:00',
                'check_out' => '09:00',
                'reason' => 'テスト',
            ]);

        $response->assertRedirect(route('attendance.edit', $attendance->id));
        $response->assertSessionHasErrors(['check_in' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合のエラーメッセージ
     */
    public function test_error_message_for_break_start_after_check_out(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->from(route('attendance.edit', $attendance->id))
            ->put(route('attendance.update', $attendance->id), [
                'check_in' => '09:00',
                'check_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '19:00',
                        'break_end' => null,
                    ],
                ],
                'reason' => 'テスト',
            ]);

        $response->assertRedirect(route('attendance.edit', $attendance->id));
        $response->assertSessionHasErrors(['breaks.0.break_start' => '休憩時間が不適切な値です']);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合のエラーメッセージ
     */
    public function test_error_message_for_break_end_after_check_out(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->from(route('attendance.edit', $attendance->id))
            ->put(route('attendance.update', $attendance->id), [
                'check_in' => '09:00',
                'check_out' => '18:00',
                'breaks' => [
                    [
                        'break_start' => '17:00',
                        'break_end' => '19:00',
                    ],
                ],
                'reason' => 'テスト',
            ]);

        $response->assertRedirect(route('attendance.edit', $attendance->id));
        $response->assertSessionHasErrors(['breaks.0.break_end' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 備考欄が未入力になっている場合のエラーメッセージ
     */
    public function test_error_message_for_no_reason(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->from(route('attendance.edit', $attendance->id))
            ->put(route('attendance.update', $attendance->id), [
                'check_in' => '09:00',
                'check_out' => '15:00',
                'reason' => null,
            ]);

        $response->assertRedirect(route('attendance.edit', $attendance->id));
        $response->assertSessionHasErrors(['reason' => '備考を記入してください']);
    }
}
