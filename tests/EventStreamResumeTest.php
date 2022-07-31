<?php

use Qruto\LaravelWave\Tests\Events\PublicEvent;
use Qruto\LaravelWave\Tests\Events\SomePrivateEvent;

it('not received event fired before connect', function () {
    PublicEvent::dispatch();
    $connection = waveConnection();

    $connection->assertEventNotReceived(PublicEvent::class);
});

it('resumes after reconnection', function () {
    $connection = waveConnection();
    PublicEvent::dispatch();

    $connection->assertEventNotReceived(PublicEvent::class);

    SomePrivateEvent::dispatch();
    PublicEvent::dispatch();
    SomePrivateEvent::dispatch();

    $connection = waveConnection(lastEventId: $connection->lastEventId());

    $connection->assertEventReceived(SomePrivateEvent::class);
    $connection->assertEventReceived(PublicEvent::class);
    $connection->assertEventReceived(SomePrivateEvent::class);
});
