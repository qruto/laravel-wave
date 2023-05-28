<?php

namespace Qruto\LaravelWave\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Auth
{
    /**
     * Generate socket id for SSE connection to communicate with native broadcasting system.
     */
    public function handle(Request $request, Closure $next)
    {
        if($request->bearerToken) $request->headers->set('Authorization', 'Bearer '.$request->bearerToken);

        return $next($request);
    }
}
