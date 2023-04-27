<?php

namespace Qruto\LaravelWave;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Qruto\LaravelWave\Events\SseConnectionClosedEvent;

class RedisSubscriber implements ServerSentEventSubscriber
{
    public function start(Closure $onMessage, Request $request)
    {
        $redisConnectionName = config('broadcasting.connections.redis.connection');

        /** @var \Illuminate\Redis\Connections\PhpRedisConnection|\Illuminate\Redis\Connections\PredisConnection $connection */
        $connection = Redis::connection("$redisConnectionName-subscription");

        register_shutdown_function(function () use ($request, $connection) {
            if (connection_aborted() !== 0) {
                event(new SseConnectionClosedEvent($request->user(), $request->header('X-Socket-Id')));
            }

            $connection->disconnect();
        });

        $connection->psubscribe('*', $onMessage);
    }
}
