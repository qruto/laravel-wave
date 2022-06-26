<?php

namespace Qruto\LaravelWave\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GenerateSseSocketId
{
    /**
     * Generate socket id for SSE connection to communicate with native broadcasting system.
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('X-Socket-Id', sprintf('%d.%d', random_int(1, 1000000000), random_int(1, 1000000000)));

        return $next($request);
    }
}
