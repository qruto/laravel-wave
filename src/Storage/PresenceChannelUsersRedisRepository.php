<?php

namespace Qruto\LaravelWave\Storage;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Redis\Connection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class PresenceChannelUsersRedisRepository implements PresenceChannelUsersRepository
{
    /** @var PhpRedisConnection|PredisConnection */
    private Connection $db;

    private string $prefix;

    public function __construct()
    {
        $this->db = Redis::connection(config('broadcasting.connections.redis.connection'));
        $this->prefix = config('database.redis.options.prefix');
    }
    protected function userKey(Authenticatable $user): string
    {
        return method_exists($user, 'getAuthIdentifierForBroadcasting')
                ? $user->getAuthIdentifierForBroadcasting()
                : $user->getAuthIdentifier();
    }

    protected function connectionsKey(string $channel, Authenticatable $user): string
    {
        return "presence_channel:$channel:user:".$this->userKey($user);
    }

    protected function serialize(array $value): string
    {
        return json_encode($value);
    }

    private function unserialize(string $value)
    {
        return json_decode($value, true);
    }

    public function join(string $channel, Authenticatable $user, array $userInfo, string $connectionId): bool
    {
        $firstJoin = false;

        $key = $this->connectionsKey($channel, $user);

        $fields = [];

        if ((bool) $this->db->exists($key)) {
            /** @var \Illuminate\Support\Collection $userConnections */
            $connections = $this->unserialize($this->db->hget($key, 'connections'));

            if (!in_array($connectionId, $connections)) {
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

        $this->db->hmset($key, $fields);

        return $firstJoin;
    }

    public function leave(string $channel, Authenticatable $user, string $connectionId): bool
    {
        $lastLeave = false;

        $key = $this->connectionsKey($channel, $user);

        if ((bool) $this->db->exists($key)) {
            $connections = $this->unserialize($this->db->hget($key, 'connections'));

            $connections = array_values(array_filter($connections, function ($connection) use ($connectionId) {
                return $connection !== $connectionId;
            }));

            if (empty($connections)) {
                $this->db->del($key);
                $lastLeave = true;
            } else {
                $this->db->hset($key, 'connections', $this->serialize($connections));
            }
        }

        return $lastLeave;
    }

    public function getUsers(string $channel): array
    {
        $keys = $this->db->keys("presence_channel:$channel:user:*");

        $users = [];

        foreach ($keys as $key) {
            $userInfo = $this->db->hget(Str::after($key, $this->prefix), 'user_info');

            if ($userInfo !== null) {
                $users[] = $this->unserialize($userInfo);
            }
        }

        return $users;
    }

    public function removeConnection(string $connectionId): array
    {
        // TODO: test for Redis cluster
        $keys = $this->db->keys('presence_channel:*');
        $fullyExitedChannels = [];

        foreach ($keys as $key) {
            $key = Str::after($key, $this->prefix);
            $connections = $this->unserialize($this->db->hget($key, 'connections'));

            $connections = array_values(array_filter($connections, function ($connection) use ($connectionId) {
                return $connection !== $connectionId;
            }));

            if (empty($connections)) {
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
    }

    private function extractChannelNameFromKey(string $key): string
    {
        $keyParts = explode(':', $key);
        // It is assumed that the channel name is always the second part of the key.
        // Adjust this according to your key structure.
        return $keyParts[1];
    }
}
