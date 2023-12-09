<?php

namespace Qruto\LaravelWave\Storage;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Connection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;

class BroadcastEventHistoryRedisStream implements BroadcastEventHistory
{
    protected int $lifetime;

    /** @var PhpRedisConnection|PredisConnection */
    private \Illuminate\Redis\Connections\Connection $db;

    public function __construct(ConfigRepository $config)
    {
        $this->db = Redis::connection(config('broadcasting.connections.redis.connection'));
        $this->lifetime = $config->get('wave.resume_lifetime', 60);
    }

    public function getEventsFrom(string $id): Collection
    {
        [$timestamp, $sequence] = explode('-', $id);
        $sequence = (int) $sequence + 1;

        return collect($this->db->xRange('broadcasted_events', $timestamp.'-'.$sequence, '+'))
            ->map(function ($event, $id) {
                $event['data'] = json_decode($event['data'], true);

                return new BroadcastingEvent(...['id' => $id] + $event);
            })->values();
    }

    public function lastEventTimestamp(): int
    {
        $keys = array_keys($this->db->xRevRange('broadcasted_events', '-', '+', 1));

        return empty($keys) ? 0 : explode('-', reset($keys))[0];
    }

    public function pushEvent(BroadcastingEvent $event)
    {
        $this->removeOldEvents();

        $eventData = \get_object_vars($event);
        $eventData['data'] = json_encode($eventData['data']);
        $id = $this->db->xAdd('broadcasted_events', '*', $eventData);

        $event->id = $id;

        return $id;
    }

    public function removeOldEvents()
    {
        // Calculate the threshold timestamp. Events older than this should be removed.
        $thresholdTimestamp = now()->subSeconds($this->lifetime)->getPreciseTimestamp(3);

        // Fetch all events up to the threshold
        $oldEvents = $this->db->xRange('broadcasted_events', '-', $thresholdTimestamp.'-0');

        $this->db->xDel('broadcasted_events', \array_keys($oldEvents));
    }
}
