<?php

namespace Qruto\LaravelWave\Tests;

use Closure;
use Illuminate\Contracts\Redis\Connection;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @mixin \Redis
 */
class RedisConnectionMock implements Connection, Factory
{
    private $events = [];

    public function eval($script, $numberOfKeys, ...$arguments)
    {
        if (Str::contains($script, 'publish')) {
            $this->events[] = [
                'message' => $arguments[0],
                'pattern' => $arguments[1],
            ];

            return;
        }
    }

    public function psubscribe($channels, Closure $callback)
    {
        if ($channels === '*') {
            Collection::make($this->events)->each(fn ($event) => $callback($event['message'], $event['pattern']));
        }
    }

    public function connection($name = null)
    {
        return $this;
    }

    public function subscribe($channels, Closure $callback)
    {
        return $this;
    }

    public function command($method, array $parameters = [])
    {
        return $this;
    }
}
