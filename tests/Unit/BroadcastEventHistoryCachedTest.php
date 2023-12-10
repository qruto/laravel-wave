<?php

use Illuminate\Support\Carbon;
use Qruto\Wave\Storage\BroadcastEventHistory;
use Qruto\Wave\Storage\BroadcastingEvent;

beforeEach(function () {
    $this->history = app(BroadcastEventHistory::class);
});

it('successfully pushes event to Redis history', function () {
    $event = BroadcastingEvent::fake();

    $this->freezeTime(function (Carbon $time) use ($event) {
        expect($this->history->pushEvent($event))
            ->toBe($time->getPreciseTimestamp(3).'-0')
            ->and(\Illuminate\Support\Facades\Redis::xRange('broadcasted_events', '0', '+'))
            ->toEqual([
                $time->getPreciseTimestamp(3).'-0' => [
                    'id' => '',
                    'name' => $event->name,
                    'channel' => $event->channel,
                    'data' => json_encode($event->data),
                    'socket' => $event->socket,
                ],
            ]);
    });
    //        ->and(cache()->get('broadcasted_events'))
    //        ->toEqual(collect([$event]));
});

it('removes outdated events from Redis history', function () {
    $event1 = BroadcastingEvent::fake();

    $this->history->pushEvent($event1);

    $this->travel(61)->seconds();

    $event2 = BroadcastingEvent::fake();
    $event3 = BroadcastingEvent::fake();

    $this->history->pushEvent($event2);
    $this->history->pushEvent($event3);

    expect(\Illuminate\Support\Facades\Redis::xRange('broadcasted_events', '0', '+'))->toEqual([
        $event2->id => [
            'id' => '',
            'name' => $event2->name,
            'channel' => $event2->channel,
            'data' => json_encode($event2->data),
            'socket' => $event2->socket,
        ],
        $event3->id => [
            'id' => '',
            'name' => $event3->name,
            'channel' => $event3->channel,
            'data' => json_encode($event3->data),
            'socket' => $event3->socket,
        ],
    ]);
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

    expect($this->history->lastEventTimestamp())->toEqual(explode('-', $event3->id)[0]);
});

it('returns 0 when there are no events', function () {
    // No events have been pushed, so the timestamp of the last event should be 0
    expect($this->history->lastEventTimestamp())->toBe(0);
});
