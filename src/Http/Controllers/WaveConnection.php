<?php

namespace Qruto\Wave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Qruto\Wave\Events\SsePingEvent;
use Qruto\Wave\Sse\ServerSentEventStream;
use Qruto\Wave\Storage\BroadcastEventHistory;

class WaveConnection extends Controller
{
    public function __construct(protected BroadcastEventHistory $eventHistory)
    {
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

        return now()->getTimestamp() - $this->eventHistory->lastEventTimestamp() > config('wave.ping.frequency', 30);
    }
}
