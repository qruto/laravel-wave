<?php

namespace Qruto\LaravelWave\Storage;

use Illuminate\Support\Collection;

interface BroadcastEventHistory
{
    public function getEventsFrom(string $id): Collection;

    public function pushEvent(BroadcastingEvent $event);

    public function lastEventTimestamp(): int;
}
