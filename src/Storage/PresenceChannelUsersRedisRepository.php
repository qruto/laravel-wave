<?php

namespace Qruto\LaravelWave\Storage;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Redis\Connection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class PresenceChannelUsersRedisRepository implements PresenceChannelUsersRepository
{
    /** @var PhpRedisConnection|PredisConnection */
    private Connection $db;

    public function __construct()
    {
        $this->db = Redis::connection(config('broadcasting.connections.redis.connection'));
    }

    protected function userKey(Authenticatable $user): string
    {
        return method_exists($user, 'getAuthIdentifierForBroadcasting')
                ? $user->getAuthIdentifierForBroadcasting()
                : $user->getAuthIdentifier();
    }

    protected function connectionsKey(Authenticatable $user, string $channel): string
    {
        return "presence_channel:$channel:user:".$this->userKey($user);
    }

    public function join(string $channel, Authenticatable $user, string $connectionId): bool
    {
        $firstJoin = false;

        $key = $this->connectionsKey($user, $channel);

        $userConnections = collect();

        if ($this->db->exists($key)) {
            /** @var \Illuminate\Support\Collection $userConnections */
            $userConnections = unserialize($this->db->get($key));
            if ($userConnections->doesntContain($connectionId)) {
                $userConnections->push($connectionId);
            }
        } else {
            $userConnections->push($connectionId);

            $firstJoin = true;
        }

        $this->db->set($key, serialize($userConnections));

        return $firstJoin;
    }

    public function leave(string $channel, Authenticatable $user, string $connectionId): bool
    {
        $disconnected = false;

        $key = $this->connectionsKey($user, $channel);

        if (! $this->db->exists($key)) {
            return $disconnected;
        }

        /** @var \Illuminate\Support\Collection $userConnections */
        $userConnections = unserialize($this->db->get($key));

        if ($userConnections->isEmpty() || $userConnections->count() === 1 && $userConnections->contains($connectionId)) {
            $this->db->del($key);
            $disconnected = true;

            return $disconnected;
        }

        $this->db->set($key, serialize($userConnections->filter(fn ($id) => $id !== $connectionId)));

        return $disconnected;
    }

    public function getUsers(string $channel, $user)
    {
        return $user->newModelQuery()->whereIn(
            $user->getAuthIdentifierName(),
            collect($this->db->keys("presence_channel:$channel:user:*"))->map(fn ($key) => Str::afterLast($key, ':'))
        )->get();
    }

    // get all users from all channels
    public function getChannels(Authenticatable $user): Collection
    {
        return collect($this->db->keys('presence_channel:*:user:'.$this->userKey($user)))->map(function ($key) {
            return Str::between($key, 'presence_channel:', ':user');
        });
    }
}
