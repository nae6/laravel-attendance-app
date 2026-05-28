<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SwitchDisplay
{
    /**
     * 勤怠修正の申請一覧画面の表示分岐
     *
     * @param  Closure(Request): (Response)  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response {
        $user = $request->user();

        $request->attributes->set(
            'view_type',
            $user->isAdmin() ? 'admin' : 'user',
        );

        return $next($request);
    }
}
