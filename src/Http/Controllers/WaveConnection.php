<?php

namespace Qruto\Wave\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Qruto\Wave\Events\SsePingEvent;
use Qruto\Wave\Http\Middleware\PingConnections;
use Qruto\Wave\Sse\ServerSentEventStream;
use Qruto\Wave\Storage\BroadcastEventHistory;

class WaveConnection extends Controller
{
    public function __construct(protected BroadcastEventHistory $eventHistory)
    {
        $this->middleware(PingConnections::class);
    }

    public function __invoke(Request $request, ServerSentEventStream $responseFactory)
    {
        return $responseFactory->toResponse($request);
    }

}
