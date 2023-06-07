<?php

namespace Qruto\LaravelWave\Listeners;

use Illuminate\Support\Str;
use Qruto\LaravelWave\Events\PresenceChannelLeaveEvent;
use Qruto\LaravelWave\Events\SseConnectionClosedEvent;
use Qruto\LaravelWave\Storage\PresenceChannelUsersRepository;

class RemoveStoredConnectionListener
{
    public function __construct(private PresenceChannelUsersRepository $store)
    {
    }

    public function handle(SseConnectionClosedEvent $event)
    {
        if (! $event->user instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            return;
        }

        $this->store->getChannels($event->user)->each(function ($channel) use ($event) {
            if ($this->store->leave($channel, $event->user, $event->connectionId)) {
                event(new PresenceChannelLeaveEvent($event->user, Str::after($channel, 'presence-')));
            }
        });
    }
}
