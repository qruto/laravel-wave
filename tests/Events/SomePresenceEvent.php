<?php


namespace Qruto\LaravelWave\Tests\Events;


use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class SomePresenceEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $someData = [
        'name' => 'presence',
        'data' => 2,
    ];

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
        return new PresenceChannel('channel-presence');
    }
}
