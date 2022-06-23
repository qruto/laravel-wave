<?php

use Qruto\LaravelWave\Tests\Events\SomeEvent;
use Qruto\LaravelWave\Tests\Events\SomePresenceEvent;
use Qruto\LaravelWave\Tests\Events\SomePrivateEvent;
use Qruto\LaravelWave\Tests\Support\User;

it('successfully subscribes to event stream', function () {
    $connection = waveConnection();

    $connection->assertConnected();
});

it('successfully received public event', function () {
    $connection = waveConnection();
    SomeEvent::dispatch();

    $connection->assertEventReceived(SomeEvent::class);
});

it('successfully received private event', function () {
    $connection = waveConnection();
    SomePrivateEvent::dispatch();

    $connection->assertEventReceived(SomePrivateEvent::class);
});

it('successfully received presence event', function () {
    $connection = waveConnection();
    SomePresenceEvent::dispatch();

    $connection->assertEventReceived(SomePresenceEvent::class);
});

test('event not received when broadcasting to others', function () {
    $connection = waveConnection();
    $event = new SomeEvent();

    $event->socket = $connection->response->headers->get('X-Socket-Id');

    broadcast($event);

    $connection->assertEventNotReceived(SomeEvent::class);
});

test('others received an event when broadcasting to others', function () {
    $connection = waveConnection();
    $event = new SomeEvent();

    $rick = User::factory()->create();
    $connectionRick = waveConnection($rick);

    $event->socket = $connection->response->headers->get('X-Socket-Id');
    broadcast($event);

    $connection->assertEventNotReceived(SomeEvent::class);
    $connectionRick->assertEventReceived(SomeEvent::class);
});
