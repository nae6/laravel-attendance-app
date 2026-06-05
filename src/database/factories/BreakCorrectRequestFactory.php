<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\AttendanceCorrectRequest;
use App\Models\BreakCorrectRequest;

/**
 * @extends Factory<BreakCorrectRequest>
 */
class BreakCorrectRequestFactory extends Factory
{
    protected $model = BreakCorrectRequest::class;

    public function definition(): array
    {
        return [
            'attendance_correct_request_id' => AttendanceCorrectRequest::factory(),
            'requested_break_start' => fake()->dateTimeBetween('-1 day', 'now'),
            'requested_break_end' => fake()->dateTimeBetween('now', '+1 day'),
        ];
    }
}
