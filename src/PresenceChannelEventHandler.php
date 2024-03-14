<?php

namespace Qruto\Wave;

use Illuminate\Contracts\Auth\Authenticatable;
use Qruto\Wave\Storage\BroadcastingEvent;

class PresenceChannelEventHandler implements PresenceChannelEvent
{
    public function isLeaveEvent(BroadcastingEvent $event, Authenticatable $user): bool
    {
        return $event->name === 'leave' && $this->fromPresenceChannel($event);
    }

    public function isSelfLeaveEvent(BroadcastingEvent $event, Authenticatable $user): bool
    {
        return $this->isLeaveEvent($event, $user) && $event->data['userId'] == $user->getAuthIdentifierForBroadcasting();
    }

    public function fromPresenceChannel(BroadcastingEvent $event): bool
    {
        return str_starts_with($event->channel, 'presence-');
    }

    public function formatLeaveEventForSending(BroadcastingEvent $event)
    {
        $event->data = $event->data['userInfo'];
    }
}
