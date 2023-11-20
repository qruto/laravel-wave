<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Redis;
use Qruto\LaravelWave\Storage\PresenceChannelUsersRedisRepository;

beforeEach(function () {
    Redis::partialMock()->shouldReceive('connection')->once()->andReturnSelf();
    $this->repository = new PresenceChannelUsersRedisRepository();
});

$connectionId = 'random-connection-id';
$channel = 'community';

function channelMemberKey(string $channel, string ...$suffixes): string
{
    return implode(':', \array_merge(["laravel_database_channels:$channel"], $suffixes));
}

function userChannelsKey(Authenticatable $user): string
{
    return implode(':', ['laravel_database_channels', $user->getAuthIdentifier(), 'user_channels']);
}

it('can add a new user to a presence channel', function () use ($connectionId, $channel) {
    Redis::shouldReceive('sadd')->once()->withArgs([
        'laravel_database_channels:community:1:user_sockets',
        $connectionId,
    ])->andReturn(null);

    Redis::shouldReceive('hset')->once()->withArgs([
        'laravel_database_channels:community:users',
        $this->user->getAuthIdentifierForBroadcasting(),
        json_encode(['email' => $this->user->email]),
    ])->andReturn(null);

    Redis::shouldReceive('sadd')->once()->withArgs([
        userChannelsKey($this->user),
        $channel,
    ])->andReturn(null);

    expect($this->repository->join(
        $channel,
        $this->user,
        ['email' => $this->user->email],
        $connectionId
    ))->toBe(true);
});

it('successfully saves second user connection', function () {
    Redis::shouldReceive('hexists')->once()->withArgs([
        'laravel_database_channels:community:users',
        $this->user->getAuthIdentifierForBroadcasting(),
    ])->andReturn(true);

    expect($this->repository->join(
        'community',
        $this->user,
        ['email' => $this->user->email],
        'another-connection-id',
    ))->toBe(false);
});

it('can remove a user connection from a presence channel', function () use ($connectionId, $channel) {
    Redis::shouldReceive('srem')->once()->withArgs([
        'laravel_database_channels:community:1:user_sockets',
        $connectionId,
    ])->andReturn(null);

    Redis::shouldReceive('scard')->once()->withArgs([
        channelMemberKey($channel, '1', 'user_sockets'),
    ])->andReturn(2);

    Redis::shouldReceive('srem')->never();
    Redis::shouldReceive('hdel')->never();

    expect($this->repository->leave($channel, $this->user, $connectionId))->toBe(false);
});

it('removes a user from a presence channel list when last connection is removed', function () use ($connectionId, $channel) {
    Redis::shouldReceive('srem')->once()->withArgs([
        'laravel_database_channels:community:1:user_sockets',
        $connectionId,
    ])->andReturn(1);

    Redis::shouldReceive('scard')->once()->withArgs([
        channelMemberKey($channel, '1', 'user_sockets'),
    ])->andReturn(1);

    Redis::shouldReceive('srem')->once()->withArgs([
        userChannelsKey($this->user),
        $channel,
    ])->andReturn(1);

    Redis::shouldReceive('hdel')->withArgs([
        'laravel_database_channels:community:users',
        $this->user->getAuthIdentifierForBroadcasting(),
    ])->andReturn(null);

    expect($this->repository->leave($channel, $this->user, $connectionId))->toBe(true);
});

it('will do nothing if a non-existent user tries to leave a presence channel', function () use ($connectionId, $channel) {
    expect($this->repository->leave($channel, $this->user, $connectionId))->toBe(false);
});

it('can return all users for a specific channel', function () use ($channel) {
    // get hgetall keys for all users in the channel
    Redis::shouldReceive('hgetall')
        ->once()
        ->with(channelMemberKey($channel, 'users'))
        ->andReturn([
            '1' => json_encode(['email' => $this->user->email]),
            '2' => json_encode(['email' => 'rick@unity.io']),
        ]);

    $result = $this->repository->getUsers($channel);

    expect($result)->toBe([
        ['email' => $this->user->email],
        ['email' => 'rick@unity.io'],
    ]);
});

it('can handle when there are no users in a specific channel', function () use ($channel) {
    Redis::shouldReceive('hgetall')->once()->andReturn([]);

    $result = $this->repository->getUsers($channel);

    expect($result)->toBe([]);
});

it('can remove a connection from all channels', function () use ($connectionId) {
    Redis::shouldReceive('smembers')
        ->once()
        ->with(userChannelsKey($this->user))
        ->andReturn(['channel1', 'channel2']);

    Redis::shouldReceive('hget')->once()->withArgs([
        channelMemberKey('channel1', 'users'),
        $this->user->getAuthIdentifierForBroadcasting(),
    ])->andReturn(json_encode(['email' => 'test1@example.com']));

    Redis::shouldReceive('hget')->once()->withArgs([
        channelMemberKey('channel2', 'users'),
        $this->user->getAuthIdentifierForBroadcasting(),
    ])->andReturn(json_encode(['email' => 'test2@example.com']));

    Redis::shouldReceive('scard')->once()->withArgs([
        channelMemberKey('channel1', $this->user->id, 'user_sockets'),
    ])->andReturn(2);

    Redis::shouldReceive('srem')->once()->withArgs([
        channelMemberKey('channel1', $this->user->id, 'user_sockets'),
        $connectionId,
    ])->andReturn(1);

    Redis::shouldReceive('srem')->never()->withArgs([
        userChannelsKey($this->user),
        'channel1',
    ]);

    // channel2

    Redis::shouldReceive('scard')->once()->withArgs([
        channelMemberKey('channel2', $this->user->id, 'user_sockets'),
    ])->andReturn(1);

    Redis::shouldReceive('srem')->once()->withArgs([
        channelMemberKey('channel2', $this->user->id, 'user_sockets'),
        $connectionId,
    ])->andReturn(1);

    Redis::shouldReceive('srem')->once()->withArgs([
        userChannelsKey($this->user),
        'channel2',
    ]);

    Redis::shouldReceive('hdel')->once()->withArgs([
        channelMemberKey('channel2', 'users'),
        $this->user->getAuthIdentifierForBroadcasting(),
    ])->andReturn(1);

    $removedConnections = $this->repository->removeConnection($this->user, $connectionId);

    expect($removedConnections)->toEqual([
        //        [
        //            'channel' => 'channel1',
        //            'user_info' => ['email' => 'test1@example.com'],
        //        ],
        [
            'channel' => 'channel2',
            'user_info' => ['email' => 'test2@example.com'],
        ],
    ]);
});
