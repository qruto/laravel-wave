<?php

namespace Qruto\LaravelWave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Qruto\LaravelWave\Events\SsePingEvent;
use Qruto\LaravelWave\Http\Middleware\GenerateSseSocketId;
use Qruto\LaravelWave\Sse\ServerSentEventStream;
use Qruto\LaravelWave\Storage\BroadcastEventHistory;

class WaveConnection extends Controller
{
    public function __construct(protected BroadcastEventHistory $eventHistory)
    {
        $this->middleware(GenerateSseSocketId::class);
    }

    public function __invoke(Request $request, ServerSentEventStream $responseFactory)
    {
        if ($this->shouldSendPing()) {
            event(new SsePingEvent());
        }

        return $responseFactory->toResponse($request);
    }

    protected function shouldSendPing(): bool
    {
        if (! config('wave.ping.enable', true)) {
            return false;
        }

        if (app()->environment(config('wave.ping.eager_env', 'local'))) {
            return true;
        }

        return now()->timestamp - $this->eventHistory->lastEventTimestamp() > config('wave.ping.frequency', 30);
    }
}
