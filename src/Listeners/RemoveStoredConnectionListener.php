<?php

namespace Qruto\LaravelWave\Listeners;

use Qruto\LaravelWave\Events\PresenceChannelLeaveEvent;
use Qruto\LaravelWave\Events\SseConnectionClosedEvent;
use Qruto\LaravelWave\PresenceChannelUsersRepository;

class RemoveStoredConnectionListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(private PresenceChannelUsersRepository $store)
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\SseConnectionClosedEvent  $event
     * @return void
     */
    public function handle(SseConnectionClosedEvent $event)
    {
        $this->store->getChannels($event->user)->each(function ($channel) use ($event) {
            if ($this->store->leave($channel, $event->user, $event->connectionId)) {
                event(new PresenceChannelLeaveEvent($event->user, $channel));
            }
        });
    }
}
