<?php

namespace Qruto\LaravelWave\Storage;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Collection;

class BroadcastEventHistoryCached implements BroadcastEventHistory
{
    protected int $lifetime;

    public function __construct(protected Repository $cache, ConfigRepository $config)
    {
        $this->lifetime = $config->get('wave.resume_lifetime', 60);
    }

    public function getEventsFrom(string $id): Collection
    {
        $events = $this->getEvents();

        $key = $events->search(
            fn (BroadcastingEvent $item) => $id === $item->id
        );

        return $key === false ? collect() : $events->slice($key + 1)->values();
    }

    public function lastEventTimestamp(): int
    {
        /** @var BroadcastingEvent|null $lastEvent */
        $lastEvent = $this->getEvents()->last();

        return $lastEvent instanceof BroadcastingEvent ? $lastEvent->timestamp : 0;
    }

    public function pushEvent(BroadcastingEvent $event)
    {
        $events = $this->getEvents();

        return cache()->lock('broadcasted_events:lock', 10)->block(5, function () use ($event, $events) {
            $events = $events->push($event)->filter(
                fn (BroadcastingEvent $event) => now()->getTimestamp() - $event->timestamp < $this->lifetime
            )->values();

            cache()->put('broadcasted_events', $events, $this->lifetime);

            return $events->last()->timestamp;
        });
    }

    protected function getEvents(): Collection
    {
        return cache()->lock('broadcasted_events:lock', 10)->block(5, fn () => cache()->get('broadcasted_events', collect()));
    }
}
