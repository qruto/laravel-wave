<?php

namespace Qruto\LaravelWave;

use Illuminate\Broadcasting\Broadcasters\RedisBroadcaster;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BroadcastManagerExtended extends BroadcastManager
{
    protected function createRedisDriver(array $config)
    {
        return new class($this->app->make('redis'), $config['connection'] ?? null, $this->app['config']->get('database.redis.options.prefix', '')) extends RedisBroadcaster
        {
            public function broadcast(array $channels, $event, array $payload = [])
            {
                // TODO: change uuid name
                $payload['uuid'] = (string) Str::uuid();

                //TODO: move service resolve
                /** @var EventStorage $storage */
                $storage = app()->make(EventsStorage::class);

                foreach ($this->formatChannels($channels) as $channel) {
                    $storage->pushEvent($channel, [
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
