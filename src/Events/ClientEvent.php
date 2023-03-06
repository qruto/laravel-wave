<?php

namespace Qruto\LaravelWave\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(protected string $name, protected string $channel, public $data)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $channelName = str($this->channel);

        return $channelName->startsWith('presence-')
                ? new PresenceChannel($channelName->after('presence-'))
                : new PrivateChannel($channelName->after('private-'));
    }

    public function broadcastWith()
    {
        return is_scalar($this->data) ? [$this->data] : $this->data;
    }

    public function broadcastAs(): string
    {
        return 'client-'.$this->name;
    }
}
