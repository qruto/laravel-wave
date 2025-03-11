<?php

use Qruto\Wave\Tests\Support\Events\PublicEvent;
use Qruto\Wave\Tests\Support\Events\SomePresenceEvent;
use Qruto\Wave\Tests\Support\Events\SomePrivateEvent;
use Qruto\Wave\Tests\Support\User;

it('successfully receives events for guest user', function () {
    auth()->logout();

    $connection = waveConnection();

    event(new PublicEvent);

    $connection->assertEventReceived(PublicEvent::class);
});

it('successfully subscribes to event stream', function () {
    $connection = waveConnection();

    $connection->assertConnected();
});

it('successfully received public event', function () {
    $connection = waveConnection();
    PublicEvent::dispatch();

    $connection->assertEventReceived(PublicEvent::class);
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
    $event = new PublicEvent;

    $event->socket = $connection->response->headers->get('X-Socket-Id');

    broadcast($event);

    $connection->assertEventNotReceived(PublicEvent::class);
});

test('others received an event when broadcasting to others', function () {
    $connection = waveConnection();
    $event = new PublicEvent;

    $rick = User::factory()->create();
    $connectionRick = waveConnection($rick);

    $event->socket = $connection->response->headers->get('X-Socket-Id');
    broadcast($event);

    $connection->assertEventNotReceived(PublicEvent::class);
    $connectionRick->assertEventReceived(PublicEvent::class);
});
