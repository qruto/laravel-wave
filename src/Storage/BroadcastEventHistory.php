<?php

namespace Qruto\LaravelWave\Storage;

use Illuminate\Support\Collection;

interface BroadcastEventHistory
{
    public function getEventsFrom(string $id, string $channelPrefix): Collection;

    public function pushEvent(string $channel, $event);

    public function lastEventTimestamp(): int;
}
