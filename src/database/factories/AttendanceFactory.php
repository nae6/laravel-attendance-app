<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        $date = $this->faker->dateTimeThisMonth();

        $checkIn = Carbon::instance($date)->setTime(rand(8, 10), rand(0, 59));
        $checkOut = (clone $checkIn)->addHours(rand(7, 10));

        return [
            'user_id' => User::factory(),
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => '退勤済',
        ];
    }
}
