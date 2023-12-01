<?php

namespace Qruto\LaravelWave;

use Illuminate\Contracts\Auth\Authenticatable;
use Qruto\LaravelWave\Storage\BroadcastingEvent;

interface PresenceChannelEvent
{
    public function isLeaveEvent(BroadcastingEvent $event, Authenticatable $user): bool;

    public function isSelfLeaveEvent(BroadcastingEvent $event, Authenticatable $user): bool;

    public function fromPresenceChannel(BroadcastingEvent $event): bool;

    public function formatLeaveEventForSending(BroadcastingEvent $event);
}
