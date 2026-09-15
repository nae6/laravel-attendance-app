<?php

namespace Tests\Feature\admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\BreakRecord;
use App\Models\Attendance;
use App\Models\User;
use Tests\TestCase;
use Carbon\Carbon;

class StaffAttendanceExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CSVダウンロードのレスポンス形式・ファイル名を確認
     */
    public function test_csv_export_response_headers_and_filename(): void
    {
        Carbon::setTestNow('2026-06-15');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'name' => 'テスト太郎',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('staff.attendance.export', [
                'staff' => $user->id,
                'month' => '2026-06',
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $expectedFileName = 'テスト太郎_2026-06_attendance.csv';
        $contentDisposition = $response->headers->get('Content-Disposition');

        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString(rawurlencode($expectedFileName), $contentDisposition);
    }

    /**
     * CSVの中身(ヘッダー行・日付ごとの出退勤/休憩/合計)を確認
     */
    public function test_csv_export_content_includes_header_and_daily_rows(): void
    {
        Carbon::setTestNow('2026-06-15');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'check_in' => '2026-06-01 09:00:00',
            'check_out' => '2026-06-01 18:00:00',
        ]);

        BreakRecord::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-06-01 12:00:00',
            'break_end' => '2026-06-01 13:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('staff.attendance.export', [
                'staff' => $user->id,
                'month' => '2026-06',
            ]));

        $content = $response->streamedContent();

        $this->assertStringContainsString('日付,出勤,退勤,休憩,合計', $content);
        $this->assertStringContainsString('06/01(月),09:00,18:00,1:00,8:00', $content);

        // 勤怠記録がない日は空欄になる
        $this->assertStringContainsString('06/02(火),,,,', $content);
    }
}
