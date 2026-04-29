<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BreakRecord;
use App\Models\Attendance;
use Carbon\Carbon;

/**
 * @extends Factory<BreakRecord>
 */
class BreakRecordFactory extends Factory
{
    protected $model = BreakRecord::class;

    public function definition(): array
    {
        $start = Carbon::instance($this->faker->dateTimeThisMonth())->setTime(12, 0);

        return [
            'attendance_id' => Attendance::factory(),
            'break_start' => $start,
            'break_end' => (clone $start)->addHour(),
        ];
    }
}
