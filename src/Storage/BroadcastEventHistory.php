<?php

namespace Qruto\LaravelWave\Storage;

interface BroadcastEventHistory
{
    public function getEventsFrom(string $id, string $channelPrefix): iterable;

    public function pushEvent(string $channel, $event);
}
