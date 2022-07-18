<?php

namespace Qruto\LaravelWave;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Qruto\LaravelWave\Events\SseConnectionClosedEvent;

class RedisSubscriber implements ServerSentEventSubscriber
{
    public function start(callable $onMessage, Request $request)
    {
        $redisConnectionName = config('broadcasting.connections.redis.connection');

        /** @var \Illuminate\Redis\Connections\PhpRedisConnection|\Illuminate\Redis\Connections\PredisConnection */
        $connection = Redis::connection("$redisConnectionName-subscription");

        register_shutdown_function(function () use ($request, $connection) {
            if (connection_aborted()) {
                event(new SseConnectionClosedEvent($request->user(), $request->header('X-Socket-Id')));
            }

            $connection->disconnect();
        });

        $connection->psubscribe('*', function ($event, $pattern) use ($onMessage) {
            $channel = $this->channelName($pattern);

            $onMessage($event, $channel);
        });
    }

    private function channelName(string $pattern): string
    {
        return Str::after($pattern, config('database.redis.options.prefix', ''));
    }
}
