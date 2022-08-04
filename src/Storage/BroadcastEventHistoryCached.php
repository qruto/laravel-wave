<?php

namespace Qruto\LaravelWave\Storage;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BroadcastEventHistoryCached implements BroadcastEventHistory
{
    protected int $lifetime;

    public function __construct(protected Repository $cache, ConfigRepository $config)
    {
        $this->lifetime = $config->get('wave.resume_lifetime', 60);
    }

    public function getEventsFrom(string $id, string $channelPrefix): Collection
    {
        $events = $this->getCached();

        $key = $events->search(function ($item) use ($id, $channelPrefix) {
            $channel = Str::after($item['channel'], $channelPrefix);

            return $id === ($channel.'.'.$item['event']['data']['broadcast_event_id']);
        });

        return $events->slice($key === false ? 0 : $key + 1);
    }

    public function lastEventTimestamp(): int
    {
        $lastEvent = $this->getCached()->last();

        return $lastEvent ? $lastEvent['timestamp'] : 0;
    }

    public function pushEvent(string $channel, $event)
    {
        $events = $this->getCached();

        $events->push([
            'channel' => $channel,
            'event' => $event,
            'timestamp' => time(),
        ]);

        $events = $events->filter(function ($event) {
            return time() - $event['timestamp'] < $this->lifetime;
        })->values();

        cache()->put('broadcasted_events', $events, $this->lifetime);

        return $events->last()['timestamp'];
    }

    protected function getCached()
    {
        return cache()->get('broadcasted_events', collect());
    }
}
