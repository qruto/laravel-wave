<?php

namespace Qruto\Wave\Listeners;

use Illuminate\Support\Str;
use Qruto\Wave\Events\PresenceChannelLeaveEvent;
use Qruto\Wave\Events\SseConnectionClosedEvent;
use Qruto\Wave\Storage\PresenceChannelUsersRepository;

class RemoveStoredConnectionListener
{
    public function __construct(private PresenceChannelUsersRepository $store) {}

    public function handle(SseConnectionClosedEvent $event)
    {
        if (! $event->user instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            return;
        }

        $fullyExitedChannels = $this->store->removeConnection($event->user, $event->connectionId);

        foreach ($fullyExitedChannels as $exitInfo) {
            broadcast(new PresenceChannelLeaveEvent($event->user->getAuthIdentifierForBroadcasting(), $exitInfo['user_info'], Str::after($exitInfo['channel'], 'presence-')))->toOthers();
        }
    }
}
