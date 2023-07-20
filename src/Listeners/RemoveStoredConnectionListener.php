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

        $fullyExitedChannels = $this->store->removeConnection($event->connectionId);

        foreach ($fullyExitedChannels as $exitInfo) {
            broadcast(new PresenceChannelLeaveEvent($exitInfo['user_info'], Str::after($exitInfo['channel'], 'presence-')))->toOthers();
        }
    }
}
