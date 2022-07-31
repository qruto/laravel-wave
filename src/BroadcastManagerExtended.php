<?php

namespace Qruto\LaravelWave;

use Illuminate\Broadcasting\Broadcasters\RedisBroadcaster;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Redis\Factory as Redis;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Qruto\LaravelWave\Storage\BroadcastEventHistory;

class BroadcastManagerExtended extends BroadcastManager
{
    protected function createRedisDriver(array $config)
    {
        return new class($this->app->make(BroadcastEventHistory::class), $this->app->make('redis'), $config['connection'] ?? null, $this->app['config']->get('database.redis.options.prefix', '')) extends RedisBroadcaster
        {
            public function __construct(private BroadcastEventHistory $history, Redis $redis, $connection = null, $prefix = '')
            {
                parent::__construct($redis, $connection, $prefix);
            }

            public function broadcast(array $channels, $event, array $payload = [])
            {
                $payload['broadcast_event_id'] = (string) Str::uuid();

                foreach ($this->formatChannels($channels) as $channel) {
                    $this->history->pushEvent($channel, [
                        'event' => $event,
                        'data' => $payload,
                        'socket' => Arr::get($payload, 'socket'),
                    ]);
                }

                parent::broadcast($channels, $event, $payload);
            }
        };
    }
}
