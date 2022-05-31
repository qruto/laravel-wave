<?php

namespace Qruto\LaravelWave;

interface ServerSentEventSubscriber
{
    public function start(callable $onMessage);
}
