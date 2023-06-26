<?php

namespace Qruto\LaravelWave\Storage;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Collection;

class BroadcastEventHistoryCached implements BroadcastEventHistory
{
    protected int $lifetime;

    /** @var Collection<BroadcastingEvent> */
    protected static Collection $events;

    public function __construct(protected Repository $cache, ConfigRepository $config)
    {
        $this->lifetime = $config->get('wave.resume_lifetime', 60);
        self::$events = cache()->get('broadcasted_events', collect());
    }

    public function getEventsFrom(string $id): Collection
    {
        $key = self::$events->search(
            fn (BroadcastingEvent $item) => $id === $item->id
        );

        return $key === false ? collect() : self::$events->slice($key + 1)->values();
    }

    public function lastEventTimestamp(): int
    {
        /** @var BroadcastingEvent $lastEvent */
        $lastEvent = self::$events->last();

        return $lastEvent ? $lastEvent->timestamp : 0;
    }

    public function pushEvent(BroadcastingEvent $event)
    {
        self::$events->push($event);

        $events = self::$events->filter(
            fn (BroadcastingEvent $event) => now()->timestamp - $event->timestamp < $this->lifetime
        )->values();

        cache()->put('broadcasted_events', $events, $this->lifetime);
        self::$events = $events;

        return self::$events->last()->timestamp;
    }
}
