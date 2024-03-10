<?php

namespace Qruto\Wave;

use Closure;
use Illuminate\Http\Request;

class DatabaseSubscriber implements ServerSentEventSubscriber
{
    public function start(Closure $onMessage, Request $request, string $socket)
    {
        $databaseDriver = config('database.default');
    }
}
