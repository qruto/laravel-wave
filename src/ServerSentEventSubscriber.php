<?php

namespace Qruto\LaravelWave;

use Illuminate\Http\Request;

interface ServerSentEventSubscriber
{
    public function start(callable $onMessage, Request $request);
}
