<?php

namespace Qruto\LaravelWave;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EventsStorage
{
    // TODO: create abstraction
    public function __construct(protected Repository $cache)
    {
    }

    public function getEventsFrom(string $id, string $channelPrefix): Collection
    {
        $events = $this->getCached();

        $key = $events->search(function ($item) use ($id, $channelPrefix) {
            $channel = Str::after($item['channel'], $channelPrefix);

            return $id === ($channel.'.'.$item['event']['data']['uuid']);
        });

        return $events->slice($key === false ? 0 : $key + 1);
    }

    public function pushEvent($channel, $event)
    {
        $events = $this->getCached();

        $events->push([
            'channel' => $channel,
            'event' => $event,
            'timestamp' => time(),
        ]);

        $events = $events->filter(function ($event) {
            return time() - $event['timestamp'] < 60;
        })->values();

        cache()->put('broadcasted_events', $events, 60);

        return $events->last()['timestamp'];
    }

    protected function getCached()
    {
        return cache()->get('broadcasted_events', collect());
    }
}
