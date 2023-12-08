<?php

namespace Qruto\LaravelWave\Tests;

use Closure;
use Illuminate\Contracts\Redis\Connection;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Str;
use M6Web\Component\RedisMock\RedisMock;

/**
 * @mixin \Redis
 */
class RedisConnectionMock extends RedisMock implements Connection, Factory
{
    public static $events = [];

    protected $streams = [];

    public function eval($script, $numberOfKeys, ...$arguments)
    {
        if (Str::contains($script, 'publish')) {
            collect(self::$events)->keys()->each(function ($connection) use ($arguments) {
                self::$events[$connection][] = [
                    'message' => $arguments[0],
                    'pattern' => $arguments[1],
                ];
            });

            $socket = Broadcast::socket();

            if (! $socket) {
                return;
            }

            if (! isset(self::$events[$socket])) {
                self::$events[$socket] = [[
                    'message' => $arguments[0],
                    'pattern' => $arguments[1],
                ]];
            }

            return;
        }

        if (strpos($script, 'firstJoin') !== false) {
            // Handle 'join' script logic
            $firstJoin = ! $this->hExists($arguments[0], $arguments[3]);
            $this->sAdd($arguments[1], $arguments[5]);
            $this->hSet($arguments[0], $arguments[3], $arguments[4]);
            $this->sAdd($arguments[2], $arguments[6]);

            return $firstJoin ? 1 : 0;
        }

        if (strpos($script, 'sismember') !== false) {
            // Handle 'leave' script logic
            if ($this->sIsMember($arguments[0], $arguments[3])) {
                $this->sRem($arguments[0], $arguments[3]);
                if ($this->sCard($arguments[0]) == 0) {
                    $this->sRem($arguments[2], $arguments[5]);
                    $this->hDel($arguments[1], $arguments[4]);

                    return 1;
                }
            }

            return 0;
        }
    }

    public function psubscribe($channels, Closure $callback)
    {
        if ($channels === '*') {
            $socket = Broadcast::socket();

            if (! $socket || ! isset(self::$events[$socket])) {
                return;
            }

            Collection::make(self::$events[$socket])
                ->each(function ($event) use ($callback) {
                    $callback($event['message'], $event['pattern']);
                });
        }
    }

    public function flushEventsQueue()
    {
        self::$events = [];
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

    public function disconnect()
    {
        return $this;
    }

    public function watch($key)
    {
        return $this;
    }

    // transaction method mock
    public function transaction($callback)
    {
        $callback($this);

        return true;
    }

    public function xAdd($stream, $id, array $fields)
    {
        // Create the stream if it doesn't exist
        if (! isset($this->streams[$stream])) {
            $this->streams[$stream] = [];
        }

        // Generate the entry ID
        if ($id === '*') {
            $timestamp = now()->getPreciseTimestamp(3); // Convert to milliseconds
            $sequence = isset($this->lastId[$stream]) ? $this->lastId[$stream][1] + 1 : 0;
            $entryId = $timestamp.'-'.$sequence;

            // Update the last ID for the stream
            $this->lastId[$stream] = [$timestamp, $sequence];
        } else {
            $entryId = $id;
        }

        // Add the entry
        $this->streams[$stream][$entryId] = $fields;

        return $entryId;
    }

    public function xRange($stream, $start, $end, $count = null)
    {
        if (! isset($this->streams[$stream])) {
            return [];
        }

        // Filter entries based on start and end IDs
        $entries = array_filter(
            $this->streams[$stream],
            function ($entryId) use ($start, $end) {
                $startCheck = $start === '-' || strcmp($entryId, $start) >= 0;
                $endCheck = $end === '+' || strcmp($entryId, $end) <= 0;

                return $startCheck && $endCheck;
            },
            ARRAY_FILTER_USE_KEY
        );

        // Limit the number of entries if count is specified
        if ($count !== null) {
            $entries = array_slice($entries, 0, $count, true);
        }

        return $entries;
    }

    public function xRevRange($stream, $end, $start, $count = null)
    {
        if (! isset($this->streams[$stream])) {
            return [];
        }

        // Filter entries based on start and end IDs
        $entries = array_filter(
            $this->streams[$stream],
            function ($entryId) use ($start, $end) {
                $startCheck = $start === '+' || strcmp($entryId, $start) <= 0;
                $endCheck = $end === '-' || strcmp($entryId, $end) >= 0;

                return $startCheck && $endCheck;
            },
            ARRAY_FILTER_USE_KEY
        );

        // Reverse the entries and apply count limit
        $entries = array_reverse($entries, true);
        if ($count !== null) {
            $entries = array_slice($entries, 0, $count, true);
        }

        return $entries;
    }

    public function xDel($stream, array $ids)
    {
        if (! isset($this->streams[$stream])) {
            return 0;
        }

        $deletedCount = 0;

        foreach ($ids as $id) {
            if (isset($this->streams[$stream][$id])) {
                unset($this->streams[$stream][$id]);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}
