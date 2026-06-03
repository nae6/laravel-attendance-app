<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Enums\AttendanceCorrectRequestStatus;
use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;

/**
 * @extends Factory<AttendanceCorrectRequest>
 */
class AttendanceCorrectRequestFactory extends Factory
{
    /**
     * １件の申請データ作成
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'requested_check_in' => fake()->dateTimeBetween('-1 month', 'now'),
            'requested_check_out' => fake()->dateTimeBetween('now', '+1 month'),
            'reason' => fake()->randomElement([
                '打刻漏れのため',
                '退勤時刻を誤って登録したため',
                '休憩時間の修正のため',
            ]),
            'approval_status' => AttendanceCorrectRequestStatus::Pending,
        ];
    }

    // 未承認
    public function pending(): static {
        return $this->state(fn() => [
            'approval_status' => AttendanceCorrectRequestStatus::Pending,
        ]);
    }

    // 承認
    public function approved(): static {
        return $this->state(fn() => [
            'approval_status' => AttendanceCorrectRequestStatus::Approved,
        ]);
    }
}
