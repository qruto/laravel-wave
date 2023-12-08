<?php

use Qruto\LaravelWave\Storage\BroadcastingEvent;
use Qruto\LaravelWave\Tests\Support\Events\PublicEvent;
use Qruto\LaravelWave\Tests\Support\Events\SomePrivateEvent;

it('not received event, fired before connect', function () {
    PublicEvent::dispatch();
    $connection = waveConnection();

    $connection->assertEventNotReceived(PublicEvent::class);
});

it('resumes after reconnection', function () {
    PublicEvent::dispatch();
    $connection = waveConnection();

    $connection->assertEventNotReceived(PublicEvent::class);

    SomePrivateEvent::dispatch();
    PublicEvent::dispatch();
    SomePrivateEvent::dispatch();

    $connection = waveConnection(lastEventId: $connection->lastEventId());

    $connection->assertEventReceived(SomePrivateEvent::class);
    $connection->assertEventReceived(PublicEvent::class);
    $connection->assertEventReceived(SomePrivateEvent::class);
});

it('not received others connection events', function () {
    cache()->put('broadcasted_events', collect([
        new BroadcastingEvent(
            'general',
            'connected',
            'random-id',
            'socket.id',
            null,
        ),
        new BroadcastingEvent(
            'community',
            'SomeEvent',
            'random-id2',
            'socket.id2',
            null,
        ),
        new BroadcastingEvent(
            'general',
            'connected',
            'random-id3',
            'socket.id3',
            null,
        ),
    ]));
    PublicEvent::dispatch();
    $connection = waveConnection();

    $connection->assertEventNotReceived(PublicEvent::class);

    $connection = waveConnection(lastEventId: $connection->lastEventId());

    $connection->assertEventNotReceived(PublicEvent::class);
});
