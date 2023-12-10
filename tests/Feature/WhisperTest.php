<?php

use Illuminate\Support\Facades\Event;
use Qruto\Wave\Events\ClientEvent;
use Qruto\Wave\Tests\Support\User;

use function Pest\Laravel\post;

it('successfully send whisper event', function () {
    Event::fake([ClientEvent::class]);

    $response = post(route('wave.whisper'), [
        'event_name' => 'typing',
        'channel_name' => 'private-test-channel',
        'data' => 'some-data',
    ]);

    Event::assertDispatched(ClientEvent::class, fn ($event) => $event->data === 'some-data');
});

it('successfully received whisper event', function () {
    $connection = waveConnection();

    $connectionTwo = waveConnection(User::factory()->create());

    post(route('wave.whisper'), [
        'event_name' => 'typing',
        'channel_name' => 'private-private-channel',
        'data' => [
            'name' => 'John',
        ],
    ], ['X-Socket-Id' => $connection->id()]);

    $connectionTwo->assertEventReceived('private-private-channel.client-typing');
});
