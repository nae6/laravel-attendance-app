<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        $loginType = $request->session()->get('login_type');

        if ($loginType === 'admin') {
            return redirect()->route('admin.login');
        }

        return redirect()->route('login');
    }
}
