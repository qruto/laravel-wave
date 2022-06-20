<?php

namespace Qruto\LaravelWave;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Qruto\LaravelWave\Events\SseConnectionClosedEvent;

class RedisSubscriber implements ServerSentEventSubscriber
{
    public function start(callable $onMessage)
    {
        // ignore_user_abort(true);
        ini_set('default_socket_timeout', -1);
        set_time_limit(0);
        // Redis::connection('subscription')->setOption(\Redis::OPT_READ_TIMEOUT, -1);


        // pcntl_async_signals(true);
        // pcntl_signal(SIGTERM, fn () => ray('SIGTERM'));
        // pcntl_signal(SIGHUP, fn () => ray('SIGHUP'));
        // pcntl_signal(SIGUSR1, fn () => ray('SIGUSR1'));

        $connection = Redis::connection('subscription');

        register_shutdown_function(function () {
            if (connection_aborted()) {
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
