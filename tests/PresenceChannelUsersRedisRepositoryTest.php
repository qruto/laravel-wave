<?php

use Illuminate\Support\Facades\Redis;
use Qruto\LaravelWave\Storage\PresenceChannelUsersRedisRepository;

beforeEach(function () {
    Redis::partialMock()->shouldReceive('connection')->once()->andReturnSelf();
    Redis::partialMock()->shouldReceive('watch')->zeroOrMoreTimes()->andReturnSelf();
    $this->repository = new PresenceChannelUsersRedisRepository();
});

$connectionId = 'random-connection-id';
$channel = 'community';

// generate key for user function
function connectionsKey(string $channel, string $userKey): string
{
    return "presence_channel:$channel:user:$userKey";
}

function expectSavedData(string $channel, string $userKey, array $connections, ?array $userInfo = null)
{
    $data = [
        'connections' => json_encode($connections),
    ];

    if ($userInfo) {
        $data['user_info'] = json_encode($userInfo);
    }

    Redis::shouldReceive('hmset')->once()->with(
        connectionsKey($channel, $userKey),
        $data,
    );
}

it('can add a new user to a presence channel', function () use ($connectionId, $channel) {
    Redis::shouldReceive('exists')->once()->andReturn(false);

    expectSavedData($channel, $this->user->id, [$connectionId], ['email' => $this->user->email]);

    expect($this->repository->join(
        $channel,
        $this->user,
        ['email' => $this->user->email],
        $connectionId
    ))->toBe(true);
});

it('successfully saves second user connection', function () use ($connectionId, $channel) {
    Redis::shouldReceive('exists')->once()->andReturn(true);

    Redis::shouldReceive('hget')->once()
        ->with(connectionsKey($channel, $this->user->id), 'connections')
        ->andReturn(json_encode([$connectionId]));

    expectSavedData($channel, $this->user->id, [$connectionId, 'another-connection-id']);

    expect($this->repository->join(
        'community',
        $this->user,
        ['email' => $this->user->email],
        'another-connection-id',
    ))->toBe(false);
});

it('can remove a user connection from a presence channel', function () use ($connectionId, $channel) {
    Redis::shouldReceive('exists')->once()->andReturn(true);
    Redis::shouldReceive('hget')->once()->andReturn(json_encode([$connectionId, 'another-connection-id']));

    Redis::shouldReceive('hset')->once()->with(
        connectionsKey($channel, $this->user->id),
        'connections',
        json_encode(['another-connection-id'])
    );

    expect($this->repository->leave($channel, $this->user, $connectionId))->toBe(false);
});

it('can remove a user from a presence channel when last connection is removed', function () use ($connectionId, $channel) {
    Redis::shouldReceive('exists')->once()->andReturn(true);
    Redis::shouldReceive('hget')->once()->andReturn(json_encode([$connectionId]));

    Redis::shouldReceive('del')->once()->with(
        connectionsKey($channel, $this->user->id)
    );

    expect($this->repository->leave($channel, $this->user, $connectionId))->toBe(true);
});

it('will do nothing if a non-existent user tries to leave a presence channel', function () use ($connectionId, $channel) {
    Redis::shouldReceive('exists')->once()->andReturn(false);

    expect($this->repository->leave($channel, $this->user, $connectionId))->toBe(false);
});

it('can return all users for a specific channel', function () use ($channel) {
    $userKeys = [
        "presence_channel:$channel:user:".$this->user->id,
        "presence_channel:$channel:user:2",
    ];

    Redis::shouldReceive('keys')->once()->andReturn($userKeys);
    Redis::shouldReceive('hget')->once()
        ->with($userKeys[0], 'user_info')
        ->andReturn(json_encode(['email' => $this->user->email]));
    Redis::shouldReceive('hget')->once()
        ->with($userKeys[1], 'user_info')
        ->andReturn(json_encode(['email' => 'rick@unity.io']));

    $result = $this->repository->getUsers($channel);

    expect($result)->toBe([
        ['email' => $this->user->email],
        ['email' => 'rick@unity.io'],
    ]);
});

it('can handle when there are no users in a specific channel', function () use ($channel) {
    Redis::shouldReceive('keys')->once()->andReturn([]);

    $result = $this->repository->getUsers($channel);

    expect($result)->toBe([]);
});

it('can remove a connection from all channels', function () use ($connectionId) {
    Redis::shouldReceive('keys')->andReturn([
        'presence_channel:channel1:user:1',
        'presence_channel:channel1:user:2',
        'presence_channel:channel2:user:1',
        'presence_channel:channel2:user:3',
    ]);

    Redis::shouldReceive('hget')->with('presence_channel:channel1:user:1', 'connections')->andReturn(json_encode([$connectionId]));
    Redis::shouldReceive('hget')->with('presence_channel:channel1:user:2', 'connections')->andReturn(json_encode(['another-connection-id']));
    Redis::shouldReceive('hget')->with('presence_channel:channel2:user:1', 'connections')->andReturn(json_encode([$connectionId]));
    Redis::shouldReceive('hget')->with('presence_channel:channel2:user:3', 'connections')->andReturn(json_encode(['another-connection-id']));

    Redis::shouldReceive('hget')->with('presence_channel:channel1:user:1', 'user_info')->andReturn(json_encode(['email' => 'test1@example.com']));
    Redis::shouldReceive('hget')->with('presence_channel:channel2:user:1', 'user_info')->andReturn(json_encode(['email' => 'test1@example.com']));

    Redis::shouldReceive('hset')->with('presence_channel:channel1:user:2', 'connections', json_encode(['another-connection-id']));
    Redis::shouldReceive('hset')->with('presence_channel:channel2:user:3', 'connections', json_encode(['another-connection-id']));

    Redis::shouldReceive('del')->with('presence_channel:channel1:user:1');
    Redis::shouldReceive('del')->with('presence_channel:channel2:user:1');

    $removedConnections = $this->repository->removeConnection($connectionId);

    expect($removedConnections)->toEqual([
        [
            'channel' => 'channel1',
            'user_info' => ['email' => 'test1@example.com'],
        ],
        [
            'channel' => 'channel2',
            'user_info' => ['email' => 'test1@example.com'],
        ],
    ]);
});
