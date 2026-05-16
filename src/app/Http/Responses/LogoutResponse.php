<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request) {
        if ($request->input('login_type') === 'admin') {
            return redirect()->route('admin.login');
        }

        return redirect()->route('login');
    }
}
