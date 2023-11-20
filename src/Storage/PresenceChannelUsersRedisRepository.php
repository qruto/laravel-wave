<?php

namespace Qruto\LaravelWave\Storage;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Redis\Connection;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Facades\Redis;
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

    protected function channelMemberKey(string $channel, string ...$suffixes): string
    {
        return implode(':', \array_merge([$this->prefix.'channels', $channel], $suffixes));
    }

    protected function userChannelsKey(Authenticatable $user): string
    {
        return implode(':', [$this->prefix.'channels', $this->userKey($user), 'user_channels']);
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
        $userKey = $this->userKey($user);
        $usersHashKey = $this->channelMemberKey($channel, 'users');

        $firstJoin = false;

        if (! $this->db->hexists($usersHashKey, $this->userKey($user))) {
            $firstJoin = true;
        }

        $this->db->transaction(function ($transaction) use (
            $user,
            $channel,
            $userKey,
            $userInfo,
            $usersHashKey,
            $connectionId,
        ) {
            $transaction->sadd(
                $this->channelMemberKey($channel, $userKey, 'user_sockets'),
                $connectionId
            );

            $transaction->hset(
                $usersHashKey,
                $this->userKey($user),
                $this->serialize($userInfo)
            );

            $transaction->sadd(
                $this->userChannelsKey($user),
                $channel
            );
        });

        return $firstJoin;
    }

    public function leave(string $channel, Authenticatable $user, string $connectionId): bool
    {
        $userKey = $this->userKey($user);
        $usersHashKey = $this->channelMemberKey($channel, 'users');
        $socketsSetKey = $this->channelMemberKey($channel, $userKey, 'user_sockets');

        $lastLeave = false;

        if ($this->db->scard($socketsSetKey) === 1) {
            $lastLeave = true;
        }

        $this->db->transaction(function ($transaction) use (
            $user,
            $channel,
            $connectionId,
            $usersHashKey,
            $socketsSetKey,
            &$lastLeave,
        ) {
            $transaction->srem($socketsSetKey, $connectionId);

            if ($lastLeave) {
                $transaction->srem($this->userChannelsKey($user), $channel);
                $transaction->hdel($usersHashKey, $this->userKey($user));
            }
        });

        return $lastLeave;
    }

    public function getUsers(string $channel): array
    {
        return collect($this->db->hgetall($this->channelMemberKey($channel, 'users')))
            ->map(fn ($userInfo) => $this->unserialize($userInfo))
            ->values()
            ->toArray();
    }

    public function removeConnection(Authenticatable $user, string $connectionId): array
    {
        $fullyExitedChannels = [];

        collect($this->db->smembers($this->userChannelsKey($user)))
            ->each(function ($channel) use ($user, $connectionId, &$fullyExitedChannels) {
                $userInfo = $this->unserialize($this->db->hget(
                    $this->channelMemberKey($channel, 'users'),
                    $this->userKey($user)
                ));

                if ($this->leave($channel, $user, $connectionId)) {
                    $fullyExitedChannels[] = [
                        'channel' => $channel,
                        'user_info' => $userInfo,
                    ];
                }
            });

        return $fullyExitedChannels;
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
