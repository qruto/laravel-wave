<?php

namespace Qruto\LaravelWave\Http\Middleware;

use Closure;
use Qruto\LaravelWave\PresenceChannelUsersRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GenerateSseSocketId
{
    public function __construct(private PresenceChannelUsersRepository $store)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('X-Socket-Id', Str::random(16));

        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function terminate($request, $response)
    {
    }
}
