<?php

namespace Qruto\LaravelWave;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Qruto\LaravelWave\Events\SseConnectionClosedEvent;

class RedisSubscriber implements ServerSentEventSubscriber
{
    public function start(callable $onMessage)
    {
        $connection = Redis::connection('subscription');

        register_shutdown_function(function () {
            if (connection_aborted()) {
                // TODO: pass request through params
                event(new SseConnectionClosedEvent(request()->user(), request()->header('X-Socket-Id')));
            }
        });

        $connection->psubscribe('*', function ($event, $pattern) use ($onMessage) {
            $channel = $this->channelName($pattern);

            $onMessage($event, $channel);
        });
    }

    private function channelName(string $pattern): string
    {
        return Str::after($pattern, config('database.redis.options.prefix'));
    }
}
