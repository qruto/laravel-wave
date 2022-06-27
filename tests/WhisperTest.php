<?php

use Illuminate\Support\Facades\Event;
use function Pest\Laravel\post;
use Qruto\LaravelWave\Events\ClientEvent;

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

    post(route('wave.whisper'), [
        'event_name' => 'typing',
        'channel_name' => 'private-private-channel',
        'data' => 'some-data',
    ]);

    $connection->assertEventReceived('private-private-channel.client-typing');
});
