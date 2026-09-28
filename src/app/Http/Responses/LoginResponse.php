<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.attendance.index');
        }

        return redirect()->intended(route('attendance'));
    }
}
