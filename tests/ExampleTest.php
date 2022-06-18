<?php

use Qruto\LaravelWave\Tests\Events\SomeEvent;

it('successfully subscribes to event stream', function () {
    $connection = waveConnection();

    $connection->assertConnected();
});


it('successfully received public event', function () {
    $connection = waveConnection();
    SomeEvent::dispatch();

    $connection->assertEventReceived(SomeEvent::class);
});
