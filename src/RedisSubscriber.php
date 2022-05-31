<?php
declare(ticks = 1);

namespace Qruto\LaravelWave;

use App\Events\SseConnectionClosedEvent;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class RedisSubscriber implements ServerSentEventSubscriber
{
    public function start(callable $onMessage)
    {
        // ignore_user_abort(true);
        ini_set('default_socket_timeout', -1);
        set_time_limit(0);
        Redis::connection('subscription')->setOption(\Redis::OPT_READ_TIMEOUT, -1);


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

        $connection->psubscribe('*', function ($message, $pattern) use ($onMessage) {
            $event = $this->eventName($pattern);

            $onMessage($message, $event);
        });
    }

    private function eventName(string $pattern): string
    {
        return Str::after($pattern, config('database.redis.options.prefix'));
    }
}
