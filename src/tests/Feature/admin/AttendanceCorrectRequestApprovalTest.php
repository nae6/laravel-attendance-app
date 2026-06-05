<?php

namespace Tests\Feature\tests\Feature\admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Enums\AttendanceCorrectRequestStatus;
use App\Models\AttendanceCorrectRequest;
use App\Models\Attendance;
use App\Models\BreakCorrectRequest;
use App\Models\User;
use Tests\TestCase;

class AttendanceCorrectRequestApprovalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 承認待ちの修正申請が全て表示される
     */
    public function test_all_pending_requests_display_on_request_history_view(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user1 = User::factory()->create([
            'name' => '山田太郎'
        ]);
        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
        ]);
        AttendanceCorrectRequest::factory()->create([
            'attendance_id' => $attendance1->id,
            'reason' => '打刻漏れ',
            'approval_status' => AttendanceCorrectRequestStatus::Pending,
        ]);

        $user2 = User::factory()->create([
            'name' => '佐藤花子'
        ]);
        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
        ]);
        AttendanceCorrectRequest::factory()->create([
            'attendance_id' => $attendance2->id,
            'reason' => '間違っていたため',
            'approval_status' => AttendanceCorrectRequestStatus::Pending,
        ]);

        $response = $this->actingAs($admin)->get(route('request.list'));

        $response->assertOk();
        $response->assertSee('id="pending-tab"', false);
        $response->assertSee('checked', false);
        $response->assertSee('承認待ち');
        $response->assertSee('山田太郎');
        $response->assertSee('佐藤花子');
        $response->assertSee('打刻漏れ');
        $response->assertSee('間違っていたため');
    }

    /**
     * 承認済の修正申請が全て表示される
     */
    public function test_all_approved_requests_display_on_request_history_view(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user1 = User::factory()->create([
            'name' => 'テスト太郎'
        ]);
        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
        ]);
        AttendanceCorrectRequest::factory()->create([
            'attendance_id' => $attendance1->id,
            'reason' => '打刻忘れ',
            'approval_status' => AttendanceCorrectRequestStatus::Approved,
        ]);

        $user2 = User::factory()->create([
            'name' => 'テスト花子'
        ]);
        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
        ]);
        AttendanceCorrectRequest::factory()->create([
            'attendance_id' => $attendance2->id,
            'reason' => '間違い',
            'approval_status' => AttendanceCorrectRequestStatus::Approved,
        ]);

        $response = $this->actingAs($admin)->get(route('request.list'));

        $response->assertOk();
        $response->assertSee('id="approved-tab"', false);
        $response->assertSee('checked', false);
        $response->assertSee('承認済み');
        $response->assertSee('テスト太郎');
        $response->assertSee('テスト花子');
        $response->assertSee('打刻忘れ');
        $response->assertSee('間違い');
    }

    /**
     * 修正申請の詳細内容が正しく表示されている
     */
    public function test_admin_can_see_selected_request_detail(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'name' => 'テスト太郎'
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-06-02 10:00:00',
            'check_out' => '2026-06-02 17:00:00',
        ]);

        $correctRequest = AttendanceCorrectRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'requested_check_in' => '2026-06-02 11:00:00',
            'requested_check_out' => '2026-06-02 19:00:00',
            'reason' => '打刻を間違えました',
        ]);

        BreakCorrectRequest::factory()->create([
            'attendance_correct_request_id' => $correctRequest->id,
            'requested_break_start' => '2026-06-02 13:00:00',
            'requested_break_end' => '2026-06-02 14:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.request.show', $correctRequest));

        $response->assertOk();
        $response->assertSee('テスト太郎');
        $response->assertSee('2026年');
        $response->assertSee('6月2日');
        $response->assertSee('11:00');
        $response->assertSee('13:00');
        $response->assertSee('14:00');
        $response->assertSee('19:00');
        $response->assertSee('打刻を間違えました');
    }

    /**
     * 修正申請の承認処理が正しく行われる
     */
    public function test_admin_can_approve_requests(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-06-03 10:00:00',
            'check_out' => '2026-06-03 17:00:00',
        ]);

        $correctRequest = AttendanceCorrectRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'requested_check_in' => '2026-06-03 11:00:00',
            'requested_check_out' => '2026-06-03 19:00:00',
            'reason' => '打刻を間違えました',
            'approval_status' => AttendanceCorrectRequestStatus::Pending,
        ]);

        BreakCorrectRequest::factory()->create([
            'attendance_correct_request_id' => $correctRequest->id,
            'requested_break_start' => '2026-06-03 13:00:00',
            'requested_break_end' => '2026-06-03 14:00:00',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.request.approve', $correctRequest));

        $response->assertRedirect(route('request.list'));

        $this->assertDatabaseHas('attendance_correct_requests', [
            'id' => $correctRequest->id,
            'approval_status' => AttendanceCorrectRequestStatus::Approved,
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'check_in' => '2026-06-03 11:00:00',
            'check_out' => '2026-06-03 19:00:00',
        ]);

        $this->assertDatabaseHas('break_records', [
            'attendance_id' => $attendance->id,
            'break_start' => '2026-06-03 13:00:00',
            'break_end' => '2026-06-03 14:00:00',
        ]);
    }
}
