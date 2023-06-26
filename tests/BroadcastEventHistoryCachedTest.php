<?php

use Illuminate\Support\Facades\Cache;
use Qruto\LaravelWave\Storage\BroadcastEventHistory;
use Qruto\LaravelWave\Storage\BroadcastingEvent;
use Hamcrest\Core\IsEqual;

beforeEach(function () {
    $this->history = app(BroadcastEventHistory::class);
});

it('successfully pushes event to Redis history', function () {
    Cache::shouldReceive('get')->once()->andReturn(collect());

    $event = BroadcastingEvent::fake();

    Cache::shouldReceive('put')->once()->with('broadcasted_events', IsEqual::equalTo(collect([$event])), 60);

    expect($this->history->pushEvent($event))->toBe($event->timestamp);
});

it('removes outdated events from Redis history', function () {
    $event1 = BroadcastingEvent::fake();

    $this->history->pushEvent($event1);

    $this->travel(61)->seconds();

    $event2 = BroadcastingEvent::fake();
    $event3 = BroadcastingEvent::fake();

    $this->history->pushEvent($event2);
    $this->history->pushEvent($event3);

    expect(cache()->get('broadcasted_events'))->toEqual(collect([$event2, $event3]));
});

it('gets events from the given id', function () {
    // Push some events into the history
    $event1 = BroadcastingEvent::fake();
    $event2 = BroadcastingEvent::fake();
    $event3 = BroadcastingEvent::fake();

    $this->history->pushEvent($event1);
    $this->history->pushEvent($event2);
    $this->history->pushEvent($event3);

    // Get events from the id of the second event
    $eventsFrom = $this->history->getEventsFrom($event2->id);

    // The returned collection should contain only the third event
    expect($eventsFrom)->toEqual(collect([$event3]));
});

it('returns the timestamp of the last event', function () {
    $event1 = BroadcastingEvent::fake();
    $event2 = BroadcastingEvent::fake();
    $event3 = BroadcastingEvent::fake();

    $this->history->pushEvent($event1);
    $this->history->pushEvent($event2);
    $this->history->pushEvent($event3);

    expect($this->history->lastEventTimestamp())->toBe($event3->timestamp);
});

it('returns 0 when there are no events', function () {
    // No events have been pushed, so the timestamp of the last event should be 0
    expect($this->history->lastEventTimestamp())->toBe(0);
});
