<?php

namespace App\Http\Middleware;

use Closure;

class AuthenticateMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if (!auth("api")->check()) {
            return response()->json([
                "message" => "Unauthenticated",
                "code" => 401,
                "status" => false,
            ], 401);
        }

        return $next($request);
    }
}
