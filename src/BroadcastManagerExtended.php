<?php

namespace Qruto\Wave;

use Illuminate\Broadcasting\Broadcasters\RedisBroadcaster;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Redis\Factory as Redis;
use Qruto\Wave\Sse\EventFactory;
use Qruto\Wave\Storage\BroadcastEventHistory;

class BroadcastManagerExtended extends BroadcastManager
{
    protected function createRedisDriver(array $config)
    {
        return new class($this->app->make(BroadcastEventHistory::class), $this->app->make('redis'), $config['connection'] ?? null, $this->app['config']->get('database.redis.options.prefix', '')) extends RedisBroadcaster
        {
            public function __construct(
                //TODO: make readonly after update minimum required PHP version
                private BroadcastEventHistory $history,
                Redis $redis,
                $connection = null,
                $prefix = ''
            ) {
                parent::__construct($redis, $connection, $prefix);
            }

            public function broadcast(array $channels, $event, array $payload = [])
            {
                foreach (EventFactory::fromBroadcastEvent($channels, $event, $payload) as $item) {
                    $id = $this->history->pushEvent($item);

                    $payload['broadcast_event_id'] = $id;
                }

                parent::broadcast($channels, $event, $payload);
            }
        };
    }
}
