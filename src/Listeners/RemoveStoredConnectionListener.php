<?php

namespace Qruto\LaravelWave\Listeners;

use Illuminate\Support\Facades\Broadcast;
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

        $fullyExitedChannels = $this->store->removeConnection($event->user, $event->connectionId);

//        ray(
//            'exited channels',
//            Broadcast::socket(),
//            $event->user->name,
//            $fullyExitedChannels,
//        )->color(request()->user()->id === 1 ? 'blue' : 'green')->label(Broadcast::socket());

        foreach ($fullyExitedChannels as $exitInfo) {
            broadcast(new PresenceChannelLeaveEvent($event->user->getAuthIdentifierForBroadcasting(), $exitInfo['user_info'], Str::after($exitInfo['channel'], 'presence-')))->toOthers();
        }
    }
}
