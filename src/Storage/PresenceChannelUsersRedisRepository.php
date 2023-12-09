<?php

namespace Qruto\LaravelWave\Storage;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Illuminate\Support\Facades\Redis;
use Qruto\LaravelWave\BroadcastingUserIdentifier;

class PresenceChannelUsersRedisRepository implements PresenceChannelUsersRepository
{
    use BroadcastingUserIdentifier;

    /** @var PhpRedisConnection|PredisConnection */
    private \Illuminate\Redis\Connections\Connection $db;

    public function __construct()
    {
        $this->db = Redis::connection(config('broadcasting.connections.redis.connection'));
    }

    protected function channelMemberKey(string $channel, string ...$suffixes): string
    {
        return implode(':', \array_merge(['broadcasting_channels', $channel], $suffixes));
    }

    protected function userChannelsKey(Authenticatable $user): string
    {
        return implode(':', ['broadcasting_channels', $this->userKey($user), 'user_channels']);
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
        $socketsSetKey = $this->channelMemberKey($channel, $userKey, 'user_sockets');
        $userChannelsKey = $this->userChannelsKey($user);

        $luaScript = <<<'LUA'
            local firstJoin = redis.call('hexists', KEYS[1], ARGV[1]) == 0
            redis.call('sadd', KEYS[2], ARGV[3])
            redis.call('hset', KEYS[1], ARGV[1], ARGV[2])
            redis.call('sadd', KEYS[3], ARGV[4])
            return firstJoin and 1 or 0
        LUA;

        return $this->db->eval(
            $luaScript,
            3,
            $usersHashKey, $socketsSetKey, $userChannelsKey,
            $userKey, $this->serialize($userInfo), $connectionId, $channel
        );
    }

    public function leave(string $channel, Authenticatable $user, string $connectionId): bool
    {
        $userKey = $this->userKey($user);
        $socketsSetKey = $this->channelMemberKey($channel, $userKey, 'user_sockets');
        $usersHashKey = $this->channelMemberKey($channel, 'users');
        $userChannelsKey = $this->userChannelsKey($user);

        $luaScript = <<<'LUA'
            if redis.call('sismember', KEYS[1], ARGV[1]) == 1 then
                redis.call('srem', KEYS[1], ARGV[1])
                if redis.call('scard', KEYS[1]) == 0 then
                    redis.call('srem', KEYS[3], ARGV[3])
                    redis.call('hdel', KEYS[2], ARGV[2])
                    return 1
                end
            end
            return 0
        LUA;

        return $this->db->eval(
            $luaScript,
            3,
            $socketsSetKey, $usersHashKey, $userChannelsKey,
            $connectionId, $userKey, $channel
        );
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
        return collect($this->db->smembers($this->userChannelsKey($user)))
            ->map(function ($channel) use ($user, $connectionId) {
                $userInfo = $this->unserialize($this->db->hget(
                    $this->channelMemberKey($channel, 'users'),
                    $this->userKey($user)
                ));

                if ($this->leave($channel, $user, $connectionId)) {
                    return [
                        'channel' => $channel,
                        'user_info' => $userInfo,
                    ];
                }

                return null;
            })->filter()->values()->toArray();
    }
}
