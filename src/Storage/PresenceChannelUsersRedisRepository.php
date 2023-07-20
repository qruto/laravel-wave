<?php

namespace Qruto\LaravelWave\Storage;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Redis\Connection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Qruto\LaravelWave\BroadcastingUserIdentifier;

class PresenceChannelUsersRedisRepository implements PresenceChannelUsersRepository
{
    use BroadcastingUserIdentifier;

    /** @var PhpRedisConnection|PredisConnection */
    private Connection $db;

    private string $prefix;

    public function __construct()
    {
        $this->db = Redis::connection(config('broadcasting.connections.redis.connection'));
        $this->prefix = config('database.redis.options.prefix');
    }

    protected function connectionsKey(string $channel, Authenticatable $user): string
    {
        return "presence_channel:$channel:user:".$this->userKey($user);
    }

    protected function serialize(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function unserialize(string $value)
    {
        return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
    }

    public function join(string $channel, Authenticatable $user, array $userInfo, string $connectionId): bool
    {
        $key = $this->connectionsKey($channel, $user);

        $firstJoin = false;

        while (true) {
            $this->db->watch($key);

            if ((bool) $this->db->exists($key)) {
                $value = $this->db->hget($key, 'connections');
                $connections = $this->unserialize($value);

                if (! in_array($connectionId, $connections)) {
                    $connections[] = $connectionId;
                }

                $fields = ['connections' => $this->serialize($connections)];
            } else {
                $fields = [
                    'connections' => $this->serialize([$connectionId]),
                    'user_info' => $this->serialize($userInfo),
                ];

                $firstJoin = true;
            }

            if ($this->db->transaction(fn ($transaction) => $transaction->hmset($key, $fields))) {
                break;
            }
        }

        return $firstJoin;
    }

    public function leave(string $channel, Authenticatable $user, string $connectionId): bool
    {
        $key = $this->connectionsKey($channel, $user);

        $lastLeave = false;

        while (true) {
            $this->db->watch($key);

            if ((bool) $this->db->exists($key)) {
                $connections = $this->unserialize($this->db->hget($key, 'connections'));

                $connections = array_values(array_filter($connections, fn ($connection) => $connection !== $connectionId));

                if ($connections === []) {
                    if ($this->db->transaction(fn ($transaction) => $transaction->del($key))) {
                        $lastLeave = true;

                        break;
                    }
                } elseif ($this->db->transaction(fn ($transaction) => $transaction->hset($key, 'connections', $this->serialize($connections)))) {
                    break;
                }
            } else {
                break;
            }
        }

        return $lastLeave;
    }

    public function getUsers(string $channel): array
    {
        // TODO: test for Redis cluster
        $keys = $this->db->keys("presence_channel:$channel:user:*");

        $users = [];

        foreach ($keys as $key) {
            $userInfo = $this->db->hget(Str::after($key, $this->prefix), 'user_info');

            $users[] = $this->unserialize($userInfo);
        }

        return $users;
    }

    public function removeConnection(string $connectionId): array
    {
        // TODO: test for Redis cluster
        return $this->lock('remove_connection', function () use ($connectionId) {
            $keys = $this->db->keys('presence_channel:*');
            $fullyExitedChannels = [];

            foreach ($keys as $key) {
                $key = Str::after($key, $this->prefix);

                $connections = $this->unserialize($this->db->hget($key, 'connections'));

                $connections = array_values(array_filter($connections, fn ($connection) => $connection !== $connectionId));

                if ($connections === []) {
                    $userInfo = $this->unserialize($this->db->hget($key, 'user_info'));
                    $this->db->del($key);

                    $fullyExitedChannels[] = [
                        'channel' => $this->extractChannelNameFromKey($key),
                        'user_info' => $userInfo,
                    ];
                } else {
                    $this->db->hset($key, 'connections', $this->serialize($connections));
                }
            }

            return $fullyExitedChannels;
        });
    }

    private function extractChannelNameFromKey(string $key): string
    {
        $keyParts = explode(':', $key);
        // It is assumed that the channel name is always the second part of the key.
        // Adjust this according to your key structure.
        return $keyParts[1];
    }

    private function lock(string $key, Closure $callback)
    {
        return cache()->lock($key.':lock', 10)->block(5, $callback);
    }
}
