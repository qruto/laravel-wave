<?php

namespace Qruto\Wave;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Qruto\Wave\Events\SseConnectionClosedEvent;

class RedisSubscriber implements ServerSentEventSubscriber
{
    public function start(Closure $onMessage, Request $request, string $socket)
    {
        $redisConnectionName = config('broadcasting.connections.redis.connection');

        /** @var \Illuminate\Redis\Connections\PhpRedisConnection|\Illuminate\Redis\Connections\PredisConnection $connection */
        $connection = Redis::connection("$redisConnectionName-subscription");

        register_shutdown_function(function () use ($request, $connection, $socket) {
            if (connection_aborted() !== 0) {
                event(new SseConnectionClosedEvent($request->user(), $socket));
            }

            $connection->disconnect();
        });

        $connection->psubscribe('*', $onMessage);
    }
}
