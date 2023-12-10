<?php

namespace Qruto\Wave;

use Closure;
use Illuminate\Http\Request;

interface ServerSentEventSubscriber
{
    public function start(Closure $onMessage, Request $request, string $socket);
}
