<?php

namespace Qruto\LaravelWave\Sse;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Qruto\LaravelWave\Storage\BroadcastingEvent;

class EventFactory
{
    public static function fromRedisMessage(string $message, string $channel)
    {
        ['event' => $event, 'data' => $data] = is_array($message) ?
                $message : json_decode($message, true, 512, JSON_THROW_ON_ERROR);

        $id = Arr::pull($data, 'broadcast_event_id');
        $socket = Arr::pull($data, 'socket');

        return new BroadcastingEvent(
            static::removeRedisPrefixFromChannel($channel),
            $event,
            $id,
            $data,
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
            static::generateId(),
            $data,
            $socket,
        );
    }

    public static function fromBroadcastEvent(array $channels, $event, array &$payload = [])
    {
        $payload['broadcast_event_id'] = static::generateId();

        $events = [];

        foreach ($channels as $channel) {
            $events[] = new BroadcastingEvent(
                $channel,
                $event,
                $payload['broadcast_event_id'],
                $payload,
                $payload['socket'],
            );
        }

        return $events;
    }

    protected static function generateId(): string
    {
        return (string) Str::uuid();
    }

    protected static function removeRedisPrefixFromChannel(string $pattern): string
    {
        return Str::after($pattern, config('database.redis.options.prefix', ''));
    }
}
