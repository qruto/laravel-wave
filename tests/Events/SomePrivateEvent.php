<?php

namespace Qruto\LaravelWave\Tests\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class SomePrivateEvent implements ShouldBroadcast
{
    use InteractsWithSockets;
    use SerializesModels;

    public $name = 'private';

    public $foo = 'foo';

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-private');
    }
}
