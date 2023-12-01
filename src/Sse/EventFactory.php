<?php

namespace Qruto\LaravelWave\Sse;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Qruto\LaravelWave\Storage\BroadcastingEvent;

class EventFactory
{
    public static function fromRedisMessage(string|array $message, string $channel)
    {
        ['event' => $event, 'data' => $data] = is_array($message) ?
                $message : json_decode($message, true, 512, JSON_THROW_ON_ERROR);

        $id = Arr::pull($data, 'broadcast_event_id');
        $socket = Arr::pull($data, 'socket');

        return new BroadcastingEvent(
            static::removeRedisPrefixFromChannel($channel),
            $event,
            $data,
            $id,
            $socket,
        );
    }

    public static function create(
        string $channel,
        string $event,
        string $data,
        ?string $socket,
    ): BroadcastingEvent {
        return new BroadcastingEvent(
            $channel,
            $event,
            $data,
            null,
            $socket,
        );
    }

    public static function fromBroadcastEvent(array $channels, $event, array &$payload = [])
    {
        $events = [];

        foreach ($channels as $channel) {
            $events[] = new BroadcastingEvent(
                $channel,
                $event,
                $payload,
                null,
                $payload['socket'],
            );
        }

        return $events;
    }

    protected static function generateId(): string
    {
        return (string) Str::ulid();
    }

    protected static function removeRedisPrefixFromChannel(string $pattern): string
    {
        return Str::after($pattern, config('database.redis.options.prefix', ''));
    }
}
