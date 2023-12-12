<?php

namespace Qruto\Wave\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Qruto\Wave\Events\SsePingEvent;
use Qruto\Wave\Storage\BroadcastEventHistory;
use Symfony\Component\HttpFoundation\Response;

class PingConnections
{
    public function __construct(protected BroadcastEventHistory $eventHistory)
    {
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSendPing()) {
            event(new SsePingEvent());
        }

        return $next($request);
    }

    protected function shouldSendPing(): bool
    {
        if (! config('wave.ping.enable', true)) {
            return false;
        }

        if (app()->environment(config('wave.ping.eager_env', 'local'))) {
            return true;
        }

        return now()->getTimestamp() - $this->eventHistory->lastEventTimestamp() > config('wave.ping.frequency', 30);
    }
}
