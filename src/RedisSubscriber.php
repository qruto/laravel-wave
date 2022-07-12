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
        register_shutdown_function(function () use ($request) {
            if (connection_aborted()) {
                event(new SseConnectionClosedEvent($request->user(), $request->header('X-Socket-Id')));
            }
        });

        $this->setupSubscription($onMessage);
    }

    private function setupSubscription(callable $onMessage)
    {
        $redisConnectionName = config('broadcasting.connections.redis.connection');

        $connection = Redis::connection("$redisConnectionName-subscription");

        try {
            $connection->psubscribe('*', function ($event, $pattern) use ($onMessage) {
                $channel = $this->channelName($pattern);

                $onMessage($event, $channel);
            });
        } catch (\Exception $e) {
            $connection->disconnect();

            $this->setupSubscription($onMessage);
        }
    }

    private function channelName(string $pattern): string
    {
        return Str::after($pattern, config('database.redis.options.prefix', ''));
    }
}
