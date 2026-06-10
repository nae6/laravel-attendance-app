<?php

namespace Tests\Feature\tests\Feature\user;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Enums\AttendanceCorrectRequestStatus;
use App\Models\AttendanceCorrectRequest;
use App\Models\Attendance;
use App\Models\User;
use Tests\TestCase;

class AttendanceCorrectRequestTest extends TestCase
{
    use RefreshDatabase;

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

    /**
     * 修正申請処理が実行さる
     */
    public function test_user_can_apply_attendance_correct_request(): void {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-06-10 08:00:00',
            'check_out' => '2026-06-10 17:00:00',
        ]);

        $response = $this->actingAs($user)
            ->from(route('attendance.edit', $attendance->id))
            ->put(route('attendance.update', $attendance->id), [
                'date' => '2026-06-10',
                'check_in' => '09:00',
                'check_out' => '15:00',
                'reason' => 'あいうえお',
            ]);

        $response->assertRedirect(route('attendance.edit', $attendance->id));

        $this->assertDatabaseHas('attendance_correct_requests', [
            'attendance_id' => $attendance->id,
            'reason' => 'あいうえお',
        ]);
    }

    /**
     * 一般ユーザーが行った修正申請が管理者の承認画面に表示される
     */
    public function test_admin_can_view_attendance_correct_request_list(): void {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        AttendanceCorrectRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'reason' => 'あいうえお',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('request.list'));

        $response->assertOk();
        $response->assertSee($user->name);
        $response->assertSee('あいうえお');
    }

    /**
     * 一般ユーザーが行った修正申請が管理者の申請一覧に表示される
     */
    public function test_admin_can_view_attendance_correct_request_detail(): void {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-06-10 08:00:00',
            'check_out' => '2026-06-10 17:00:00',
        ]);

        $correctRequest = AttendanceCorrectRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'requested_check_in' => '2026-06-10 09:00',
            'requested_check_out' => '2026-06-10 15:00',
            'reason' => 'あいうえお',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.request.show', $correctRequest->id));

        $response->assertOk();
        $response->assertSee($user->name);
        $response->assertSee('09:00');
        $response->assertSee('15:00');
        $response->assertSee('あいうえお');
    }

    /**
     * 承認待ちに未承認の申請が全て表示される
     */
    public function test_pending_requests_are_displayed_in_pending_tab(): void
    {
        $user = User::factory()->create();

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        AttendanceCorrectRequest::factory()->create([
            'attendance_id' => $attendance1->id,
            'reason' => '未承認申請1',
            'approval_status' => AttendanceCorrectRequestStatus::Pending,
        ]);

        AttendanceCorrectRequest::factory()->create([
            'attendance_id' => $attendance2->id,
            'reason' => '未承認申請2',
            'approval_status' => AttendanceCorrectRequestStatus::Pending,
        ]);

        $response = $this->actingAs($user)->get(route('request.list'));

        $response->assertOk();

        $response->assertSee('未承認申請1');
        $response->assertSee('未承認申請2');
    }

    /**
     * 承認済に承認済みの申請が全て表示される
     */
    public function test_approved_requests_are_displayed_in_approved_tab(): void
    {
        $user = User::factory()->create();

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $approvedRequest1 = AttendanceCorrectRequest::factory()->approved()->create([
            'attendance_id' => $attendance1->id,
            'reason' => '承認済申請1',
        ]);

        $approvedRequest2 = AttendanceCorrectRequest::factory()->approved()->create([
            'attendance_id' => $attendance2->id,
            'reason' => '承認済申請2',
        ]);

        AttendanceCorrectRequest::factory()->pending()->create([
            'attendance_id' => $attendance2->id,
            'reason' => '未承認申請',
        ]);

        $response = $this->actingAs($user)->get(route('request.list'));

        $response->assertOk();
        $response->assertSee('承認済申請1');
        $response->assertSee('承認済申請2');

        $response->assertViewHas('approvedRequests', function ($approvedRequests) use ($approvedRequest1, $approvedRequest2) {
            return $approvedRequests->count() === 2
                && $approvedRequests->contains('id', $approvedRequest1->id)
                && $approvedRequests->contains('id', $approvedRequest2->id)
                && ! $approvedRequests->contains('reason', '未承認申請');
        });
    }

    /**
     * 一般ユーザーの申請一覧画面で詳細ボタンを押すと勤怠詳細画面に遷移する
     */
    public function test_user_can_access_attendance_detail_from_request_list(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-06-10 09:00:00',
            'check_out' => '2026-06-10 18:00:00',
        ]);

        AttendanceCorrectRequest::factory()->pending()->create([
            'attendance_id' => $attendance->id,
            'reason' => '修正申請の理由',
        ]);

        $response = $this->actingAs($user)
            ->get(route('request.list'));

        $response->assertOk();

        $response->assertSee(route('attendance.edit', $attendance->id), false);

        $detailResponse = $this->actingAs($user)
            ->get(route('attendance.edit', $attendance->id));
        $detailResponse->assertOk();
    }
}
