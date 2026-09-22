<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * 管理者が対象スタッフの勤怠情報を閲覧できるか判定(一般ユーザーのみ対象)
     */
    public function viewAttendance(User $admin, User $staff): Response
    {
        return $staff->role === 'user'
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
