<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    /**
     * 本人の勤怠情報か判定
     */
    public function view(User $user, Attendance $attendance): bool
    {
        return $user->id === $attendance->user_id;
    }
}
