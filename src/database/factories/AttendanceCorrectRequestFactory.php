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
        $date = fake()->dateTimeBetween('-1 month', 'now');

        $checkIn = (clone $date)->setTime(
            rand(8, 10),
            rand(0, 59)
        );

        $checkOut = (clone $checkIn)->modify('+' . rand(1, 10) . ' hours');

        return [
            'attendance_id' => Attendance::factory(),
            'requested_check_in' => $checkIn,
            'requested_check_out' => $checkOut,
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
