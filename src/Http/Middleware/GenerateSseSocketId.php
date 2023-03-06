<?php

namespace Qruto\LaravelWave\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GenerateSseSocketId
{
    /**
     * Generate socket id for SSE connection to communicate with native broadcasting system.
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $request->headers->set('X-Socket-Id', sprintf('%d.%d', random_int(1, 1_000_000_000), random_int(1, 1_000_000_000)));

        return $next($request);
    }
}
