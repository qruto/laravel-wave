<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Redis;
use Qruto\LaravelWave\Storage\PresenceChannelUsersRedisRepository;
use Qruto\LaravelWave\Tests\InteractsWithRedis;
use Qruto\LaravelWave\Tests\Support\User;

uses(InteractsWithRedis::class);

beforeEach(function () {
    $this->setUpRedis();
    Redis::partialMock()->shouldReceive('connection')->once()->andReturn($this->connection());
    $this->repository = new PresenceChannelUsersRedisRepository();
});

afterEach(function () {
    $this->tearDownRedis();
});

$connectionId = 'random-connection-id';
$channel = 'presence-community';

function channelMemberKey(string $channel, string ...$suffixes): string
{
    return implode(':', array_merge(["broadcasting_channels:$channel"], $suffixes));
}

function userChannelsKey(Authenticatable $user): string
{
    return implode(':', ['broadcasting_channels', $user->getAuthIdentifier(), 'user_channels']);
}

it('can add a new user to a presence channel', function () use ($connectionId, $channel) {
    $userKey = $this->user->getAuthIdentifierForBroadcasting();
    $usersHashKey = channelMemberKey($channel, 'users');
    $socketsSetKey = channelMemberKey($channel, $userKey, 'user_sockets');
    $userChannelsKey = userChannelsKey($this->user);

    expect($this->repository->join(
        $channel,
        $this->user,
        ['email' => $this->user->email],
        $connectionId
    ))->toBeTrue()
        ->and((bool) $this->connection()->exists($socketsSetKey))->toBeTrue()
        ->and((bool) $this->connection()->exists($userChannelsKey))->toBeTrue()
        ->and((bool) $this->connection()->exists($usersHashKey))->toBeTrue()
        ->and((bool) $this->connection()->sismember($socketsSetKey, $connectionId))->toBeTrue()
        ->and((bool) $this->connection()->hexists($usersHashKey, $userKey))->toBeTrue()
        ->and($this->connection()->hget($usersHashKey, $userKey))->toBe(json_encode(['email' => $this->user->email]))
        ->and((bool) $this->connection()->sismember($userChannelsKey, $channel))->toBeTrue();
});

it('successfully saves second user connection', function () use ($connectionId, $channel) {
    $this->repository->join(
        $channel,
        $this->user,
        ['email' => $this->user->email],
        $connectionId
    );

    expect($this->repository->join(
        $channel,
        $this->user,
        ['email' => $this->user->email],
        'another-connection-id',
    ))->toBeFalse()->and(
        (bool) $this->connection()->sismember(channelMemberKey($channel, $this->user->getAuthIdentifierForBroadcasting(), 'user_sockets'), $connectionId)
    )->toBeTrue()->and(
        (bool) $this->connection()->sismember(channelMemberKey($channel, $this->user->getAuthIdentifierForBroadcasting(), 'user_sockets'), 'another-connection-id')
    )->toBeTrue();
});

it('can remove a user connection from a presence channel', function () use ($connectionId, $channel) {
    $this->repository->join($channel, $this->user, ['email' => $this->user->email],'first-connection-id');
    $this->repository->join($channel, $this->user, ['email' => $this->user->email], $connectionId);

    expect($this->repository->leave($channel, $this->user, $connectionId))->toBeFalse();
});

it('removes a user from a presence channel list when last connection is removed', function () use ($connectionId, $channel) {
    $this->repository->join($channel, $this->user, ['email' => $this->user->email], $connectionId);

    expect($this->repository->leave($channel, $this->user, $connectionId))
        ->toBeTrue()
        ->and((bool) $this->connection()->exists(channelMemberKey($channel, $this->user->getAuthIdentifierForBroadcasting(), 'user_sockets')))
        ->toBeFalse();
});

it('will do nothing if a non-existent user tries to leave a presence channel', function () use ($connectionId, $channel) {
    expect($this->repository->leave($channel, $this->user, $connectionId))->toBe(false);
});

it('can return all users for a specific channel', function () use ($connectionId, $channel) {
    $secondUser = User::factory()->create();

    $this->repository->join($channel, $this->user, ['email' => $this->user->email], $connectionId);
    $this->repository->join($channel, $secondUser, ['email' => $secondUser->email], 'another-connection-id');

    $result = $this->repository->getUsers($channel);

    expect($result)->toBe([
        ['email' => $this->user->email],
        ['email' => $secondUser->email],
    ]);
});

it('can handle when there are no users in a specific channel', function () use ($channel) {
    expect($this->repository->getUsers($channel))->toBe([]);
});

it('can remove a connection from all channels', function () use ($connectionId) {
    $this->repository->join('channel1', $this->user, ['email' => $this->user->email], $connectionId);
    $this->repository->join('channel2', $this->user, ['email' => $this->user->email], $connectionId);

    $removedConnections = $this->repository->removeConnection($this->user, $connectionId);

    expect($removedConnections)->toEqual([
        [
            'channel' => 'channel1',
            'user_info' => ['email' => $this->user->email],
        ],
        [
            'channel' => 'channel2',
            'user_info' => ['email' => $this->user->email],
        ],
    ]);
});
