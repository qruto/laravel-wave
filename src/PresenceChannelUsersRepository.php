<?php

namespace Qruto\LaravelWave;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Redis\Connection;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class PresenceChannelUsersRepository
{
    /** @var \Illuminate\Redis\Connections\PhpRedisConnection|\Illuminate\Redis\Connections\PredisConnection */
    private Connection $db;

    public function __construct()
    {
        //TODO: select database
        $this->db = Redis::connection();
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

    public function join(string $channel, Authenticatable $user, string $connectionId)
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

    // remove user from the presence channel
    public function leave(string $channel, Authenticatable $user, string $connectionId)
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

    public function getUsers(string $channel)
    {
        return collect($this->db->keys("presence_channel:$channel:user:*"))->map(function ($key) {
            // TODO: change provider name
            return config('auth.providers.users.model')::find(Str::afterLast($key, ':'));
        });
    }

    // get all users from all channels
    public function getChannels(Authenticatable $user)
    {
        return collect($this->db->keys("presence_channel:*:user:".$this->userKey($user)))->map(function ($key) {
            return Str::between($key, 'presence_channel:', ':user');
        });
    }
}
