<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * 管理者判定
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response {
        Gate::authorize('access-admin');

        return $next($request);
    }
}
