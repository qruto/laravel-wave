<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;

use function Pest\Laravel\actingAs;

use Qruto\LaravelWave\Events\PresenceChannelJoinEvent;
use Qruto\LaravelWave\Tests\Events\SomePresenceEvent;
use Qruto\LaravelWave\Tests\Events\SomePrivateEvent;
use Qruto\LaravelWave\Tests\Support\User;

it('send join event', function () {
    Event::fake([PresenceChannelJoinEvent::class]);

    $connection = waveConnection();

    joinRequest('presence-channel', $this->user, $connection->id());

    Event::assertDispatched(PresenceChannelJoinEvent::class);
});


it('stores user in redis presence channel pool', function () {
    $connection = waveConnection();

    $key = 'presence_channel:presence-presence-channel:user:'.auth()->user()->getAuthIdentifier();

    joinRequest('presence-channel', $this->user, $connection->id());

    expect((bool) Redis::exists($key))->toBeTrue();
    expect(unserialize(Redis::get($key))->first())->toBe($connection->id());
});


test('join respond with actual count of channel users', function () {
    $connection = waveConnection();
    joinRequest('presence-channel', $this->user, $connection->id());

    /** @var \Illuminate\Contracts\Auth\Authenticatable */
    $rick = User::factory()->create(['name' => 'Rick']);
    $connectionRick = waveConnection($rick);
    $response = joinRequest('presence-channel', $rick, $connectionRick->id());

    $response->assertJson([
        $this->user->toArray(),
        $rick->toArray(),
    ]);
});

test('join channel event sent', function () {
    /** @var \Illuminate\Contracts\Auth\Authenticatable */
    $rick = User::factory()->create(['name' => 'Rick']);

    /** @var \Illuminate\Contracts\Auth\Authenticatable */
    $morty = User::factory()->create(['name' => 'Morty']);

    $connectionRick = waveConnection($rick);

    $connectionMorty = waveConnection($morty);
    joinRequest('presence-channel', $morty, $connectionMorty->id());

    actingAs($rick);
    ray($connectionRick->getSentEvents());
    $connectionRick->assertEventReceived('presence-presence-channel.join');
});

it('not receiving access events without access', function () {
    Broadcast::channel('presence-channel', fn () => false);
    $connection = waveConnection();
    event(new SomePresenceEvent());

    $connection->assertEventNotReceived(SomePrivateEvent::class);
});

// TODO: test 'leave' event
// TODO: test different presence channel names join event

function joinRequest($channelName, Authenticatable $user, string $connectionId)
{
    return actingAs($user)->post('presence-channel-users', ['channel_name' => 'presence-'.$channelName], ['X-Socket-Id' => $connectionId]);
}
